<?php

declare(strict_types=1);

namespace Swew\Cli\Terminal;

class Output
{
    /** @var resource */
    private mixed $input;
    /** @var resource */
    private mixed $output;
    private readonly bool $ownsInput;
    private readonly bool $ownsOutput;
    private bool $ansi = true;
    private bool|string|null $sttyMode = null;
    private readonly bool $useExec;
    private static array $formats = [];

    public function __construct(
        mixed $stdin = null,
        mixed $stdout = null,
        bool $useExec = true,
    ) {
        $this->useExec = $useExec;
        $this->ownsInput = $stdin === null;
        $this->ownsOutput = $stdout === null;
        $input = $stdin ?? fopen('php://stdin', 'r');
        $output = $stdout ?? fopen('php://output', 'w');

        if (!is_resource($output) || !is_resource($input)) {
            throw new \Exception("Wrong type for \$output:($output) or \$input:($input) stream");
        }

        $this->input = $input;
        $this->output = $output;
        $this->ansi =
            stream_isatty($stdout === null && defined('STDOUT') ? STDOUT : $output) && getenv('NO_COLOR') === false;
        if ($this->isInteractiveInput($this->input)) {
            $this->sttyMode = $this->exec('stty -g');
        }
    }

    public function __destruct()
    {
        if (is_string($this->sttyMode)) {
            $this->exec('stty ' . escapeshellarg(trim($this->sttyMode)));
        }
        if ($this->ownsInput && is_resource($this->input)) {
            fclose($this->input);
        }
        if ($this->ownsOutput && is_resource($this->output)) {
            fclose($this->output);
        }
    }

    public function clear(): void
    {
        if (!$this->ansi) {
            return;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            $this->write("\x1B[2J\x1B[0f");
        } else {
            $this->write("\x1B[2J\x1B[3J\x1B[H");
        }
    }

    public function getLink(string $url, string $text): string
    {
        return $this->ansi ? "\e]8;;$url\x07$text\e]8;;\x07" : $text;
    }

    public function write(string|int|float $text, string $format = '%s'): void
    {
        $text = sprintf($format, $text);

        $text = $this->format($text);

        fwrite($this->output, $text);
    }

    public function writeLn(string|int|float $text, string $format = '%s'): void
    {
        $this->write($text, $format . "\n");
    }

    public function info(string|int|float $text): void
    {
        $format = '<bgGreen> INFO </> %s</>';
        $this->writeLn($text, $format);
    }

    public function warn(string|int|float $text): void
    {
        $format = '<bgYellow> WARN </> %s</>';
        $this->writeLn($text, $format);
    }

    public function error(string|int|float $text): void
    {
        $format = '<bgRed> ERROR </> %s</>';
        $this->writeLn($text, $format);
    }

    public function newLine(int $countLines = 1): void
    {
        $nl = str_repeat("\n", max(0, $countLines));
        $this->write($nl);
    }

    /**
     * @param array<int,string> $titles
     * @param array<int,array<int,mixed>> $lines
     */
    public function table(array $titles, array $lines): void
    {
        // Pre-calculate column widths
        $columnWidths = [];
        $data = [$titles, ...$lines];

        foreach ($data as $row) {
            foreach ($row as $columnIndex => $value) {
                $columnWidths[$columnIndex] = max(strlen(strval($value)) + 1, $columnWidths[$columnIndex] ?? 0);
            }
        }

        // Build and output header
        $header = '';
        foreach ($titles as $columnIndex => $value) {
            $header .= sprintf("%-{$columnWidths[$columnIndex]}s ", " $value");
        }
        $this->writeLn($header, '<bgBlue><b><i>%s</>');

        // Build and output rows
        foreach ($lines as $i => $row) {
            $line = '';
            foreach ($row as $columnIndex => $value) {
                $line .= sprintf("%-{$columnWidths[$columnIndex]}s ", " $value");
            }
            $format = ($i % 2) === 0 ? '<blue>%s</>' : '<white>%s</>';
            $this->writeLn($line, $format);
        }
    }

    public function createProgressBar(int $count = 100): ProgressBar
    {
        return new ProgressBar($this, $count);
    }

    public function setAnsi(bool $ansi): self
    {
        $this->ansi = $ansi;

        return $this;
    }

    public function isAnsi(): bool
    {
        return $this->ansi;
    }

    public function ask(string $question, string $default = ''): string
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write('<cyan>❯</> ');

        $answer = trim(strval(fgets($this->input)));
        return $answer === '' ? $default : $answer;
    }

    public function askYesNo(string $question, bool $answer = false, string $yes = 'Yes', string $no = 'No'): bool
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $answer;
        }

        // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
        $this->exec('stty -icanon -echo');

        $isShowed = false;

        while (true) {
            if ($isShowed) {
                $this->write('<up_2><eraseToBottom>');
            }

            $this->writeLn($question, '  <cyan>%s</>');

            if ($answer) {
                $line = "  <green><u><b>$yes</>  <gray>$no</>";
            } else {
                $line = "  <gray>$yes</>  <green><u><b>$no</>";
            }
            $this->writeLn($line);

            $isShowed = true;

            // Wait for user input
            $key = $this->readKeyPress();

            // Move the selection up or down based on user input
            switch ($key) {
                case 'UP': // Up arrow
                case 'DOWN': // Down arrow
                case 'RIGHT': // Right arrow
                case 'LEFT': // Left arrow
                    $answer = !$answer;
                    break;
                case 'SPACE': // Space
                case 'ENTER': // Enter key
                    break 2;
            }
        }

        if (is_string($this->sttyMode)) {
            $this->exec('stty ' . escapeshellarg(trim($this->sttyMode)));
        }
        $this->write('<up_2><eraseToBottom>');

        $this->write($answer ? '<green>✓</>' : '<red>✘</>');
        $this->writeLn($question, ' <cyan>%s</>');

        return $answer;
    }

    public function secret(string $question, string $default = ''): string
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write('<yellow>❯</> ');

        $this->exec('stty -echo');
        try {
            $answer = trim(strval(fgets($this->input)));
        } finally {
            $this->exec(is_string($this->sttyMode) ? 'stty ' . escapeshellarg(trim($this->sttyMode)) : 'stty echo');
            $this->newLine();
        }

        return $answer === '' ? $default : $answer;
    }

    /** @param list<string> $options */
    public function choice(string $text, array $options, bool $isRequired = true): string
    {
        $val = $this->select($text, $options, [], $isRequired, false);

        return (string) current($val);
    }

    /**
     * @param list<string> $options
     * @param list<int> $selectedIndex
     * @return array<int, string>
     */
    public function select(
        string $text,
        array $options,
        array $selectedIndex = [],
        bool $isRequired = true,
        bool $isMultiple = true,
    ): array {
        if ($options === []) {
            return [];
        }
        $isInteractive = $this->isInteractiveInput($this->input);
        $selected = array_fill_keys($selectedIndex, true);

        if (!$isInteractive) {
            return array_filter($options, fn(int $index) => isset($selected[$index]), ARRAY_FILTER_USE_KEY);
        }

        $this->writeLn($text, '<cyan>%s</>');
        $this->write('<eraseToBottom>');

        // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
        $this->exec('stty -icanon -echo');

        $cursorIndex = 0;
        $numberOfLinesDrawnLAST = 0;

        while (true) {
            if ($numberOfLinesDrawnLAST) {
                $this->write("<up_$numberOfLinesDrawnLAST><eraseToBottom>");
            }

            $n = 7; // Count of showed options
            $i = max(0, $cursorIndex - 3);
            $count = count($options);
            $numberOfLinesDrawn = 0;

            // Output the options list
            for (; $i < $count && $numberOfLinesDrawn < $n; $i++) {
                if ($i === $cursorIndex) {
                    $this->write('🞊 ');
                } else {
                    $this->write('🞅 ');
                }

                $isSelected = isset($selected[$i]);

                if ($isMultiple) {
                    $format = $isSelected ? '<green>✔</> <b>%s</>' : '<yellow>☐</> %s';
                } else {
                    $format = '%s';
                }

                $this->writeLn($options[$i], $format);
                $numberOfLinesDrawn++;
            }

            $numberOfLinesDrawnLAST = $numberOfLinesDrawn;

            // Wait for user input
            $key = $this->readKeyPress();

            // Move the selection up or down based on user input
            switch ($key) {
                case 'UP': // Up arrow
                    $cursorIndex = max(0, $cursorIndex - 1);
                    break;
                case 'DOWN': // Down arrow
                    $cursorIndex = min(count($options) - 1, $cursorIndex + 1);
                    break;
                case 'SPACE': // Space
                    if (isset($selected[$cursorIndex])) {
                        unset($selected[$cursorIndex]);
                    } else {
                        $selected[$cursorIndex] = true;

                        if (!$isMultiple) {
                            break 2;
                        }
                    }
                    break;
                case 'ENTER': // Enter key
                    if (!$isMultiple) {
                        $selected[$cursorIndex] = true;
                        break 2;
                    }
                    if ($isRequired && count($selected) === 0) {
                        break;
                    }
                    break 2;
            }
        }

        if ($numberOfLinesDrawnLAST) {
            $numberOfLinesDrawnLAST += 1;
            $this->write("<up_$numberOfLinesDrawnLAST><eraseToBottom>");
        }

        if (is_string($this->sttyMode)) {
            $this->exec('stty ' . escapeshellarg(trim($this->sttyMode)));
        }

        return array_filter($options, fn(int $index) => isset($selected[$index]), ARRAY_FILTER_USE_KEY);
    }

    public function width(): int
    {
        $width = (int) $this->exec('tput cols');
        return $width > 0 ? $width : 80;
    }

    public function height(): int
    {
        $height = (int) $this->exec('tput lines');
        return $height > 0 ? $height : 24;
    }

    private function exec(string $command): bool|string|null
    {
        if ($this->useExec === false) {
            return null;
        }

        return __execCommand($command . (PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null'));
    }

    /** @param resource $inputStream */
    private function isInteractiveInput(mixed $inputStream): bool
    {
        return $this->useExec && PHP_OS_FAMILY !== 'Windows' && stream_isatty($inputStream);
    }

    private function readKeyPress(): ?string
    {
        // Read 3 or 4 bytes for the escape-sequence of the arrows
        $key = fread($this->input, 4);
        if ($key === false || $key === '') {
            if (is_string($this->sttyMode)) {
                $this->exec('stty ' . escapeshellarg(trim($this->sttyMode)));
            }
            throw new \RuntimeException('Input stream closed while waiting for a key');
        }

        $k0 = ord($key[0]);

        // Handle single character keys
        if ($k0 === 32) { // Space
            return 'SPACE';
        }
        if ($k0 === 10) { // Enter
            return 'ENTER';
        }

        // Handle arrow keys and other special keys
        if ($k0 === 27 && strlen($key) >= 3) { // ESC sequence
            $k1 = ord($key[1]);
            $k2 = ord($key[2]);

            if ($k1 === 91) { // [
                return match ($k2) {
                    65 => 'UP',
                    66 => 'DOWN',
                    67 => 'RIGHT',
                    68 => 'LEFT',
                    32 => 'SPACE',
                    10 => 'ENTER',
                    default => null,
                };
            }
        }

        return null;
    }

    public function format(string $text): string
    {
        $isTerm = getenv('TERM_PROGRAM') === 'Apple_Terminal';

        self::$formats = [
            // reset
            '</>' => "\e[m",
            '<br>' => PHP_EOL,

            // Text styles
            '<b>' => "\e[1m",
            '<f>' => "\e[2m",
            '<i>' => "\e[3m",
            '<u>' => "\e[4m",
            '<blink>' => "\e[5m",
            '<hidden>' => "\e[8m",
            '<s>' => "\e[9m",

            // Colors
            '<black>' => "\e[38;5;16m",
            '<gray>' => "\e[38;5;8m",
            '<white>' => "\e[38;5;7m",
            '<red>' => "\e[38;5;1m",
            '<green>' => "\e[38;5;2m",
            '<yellow>' => "\e[38;5;3m",
            '<blue>' => "\e[38;5;24m",
            '<purple>' => "\e[38;5;13m",
            '<cyan>' => "\e[38;5;6m",

            // Background colors
            '<bgBlack>' => "\e[48;5;16m",
            '<bgGray>' => "\e[48;5;8m",
            '<bgWhite>' => "\e[48;5;7m",
            '<bgRed>' => "\e[48;5;1m",
            '<bgGreen>' => "\e[48;5;2m",
            '<bgYellow>' => "\e[48;5;3m",
            '<bgBlue>' => "\e[48;5;24m",
            '<bgPurple>' => "\e[48;5;13m",
            '<bgCyan>' => "\e[48;5;6m",

            // Cursor and screen control
            '<saveCursor>' => $isTerm ? "\e[8n" : "\e[s",
            '<restoreCursor>' => $isTerm ? "\e[7n" : "\e[u",
            '<eraseToEndLine>' => "\e[K",
            '<eraseToStartLine>' => "\e[1K",
            '<eraseLine>' => "\e[2K",
            '<eraseToBottom>' => "\e[J",
            '<down>' => "\eJ",
            '<eraseToTop>' => "\e[1J",
            '<up>' => "\e[1J",
            '<screen>' => "\e[2J",
            '<show>' => "\e?25h",
            '<hide>' => "\e?25l",
            '<getPosition>' => "\e6n",

            // Up movement
            '<up_1>' => "\e[1A",
            '<up_2>' => "\e[2A",
            '<up_3>' => "\e[3A",
            '<up_4>' => "\e[4A",
            '<up_5>' => "\e[5A",
            '<up_6>' => "\e[6A",
            '<up_7>' => "\e[7A",
            '<up_8>' => "\e[8A",
            '<up_9>' => "\e[9A",
        ];

        $text = str_replace('<br>', PHP_EOL, $text);

        return str_replace(array_keys(self::$formats), $this->ansi ? array_values(self::$formats) : '', $text);
    }

    /**
     * Remove bash color symbols from string
     */
    public static function clearColor(string $str): string
    {
        $patterns = "/\e\[[\d;]*m/";

        return (string) preg_replace($patterns, '', $str);
    }
}
