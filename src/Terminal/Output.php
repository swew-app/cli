<?php

declare(strict_types=1);

namespace Swew\Cli\Terminal;

class Output
{
    private mixed $input;
    private mixed $output;
    private bool $ansi = true;
    private bool|null|string $sttyMode = null;
    private readonly bool $useExec;
    private static array $formats = [];

    public function __construct(
        mixed $stdin = null,
        mixed $stdout = null,
        bool $useExec = true
    ) {
        $this->useExec = $useExec;
        $input = $stdin ?? fopen('php://stdin', 'r');
        $output = $stdout ?? fopen('php://output', 'r');

        if (!is_resource($output) || !is_resource($input)) {
            throw new \Exception("Wrong type for \$output:($output) or \$input:($input) stream");
        }

        $this->input = $input;
        $this->output = $output;
        if (stream_isatty($this->output)) {
            $this->sttyMode = $this->exec('stty -g');
        }
    }

    public function __destruct()
    {
        if (is_resource($this->input)) {
            fclose($this->input);
        }
        if (is_resource($this->output)) {
            fclose($this->output);
        }
        if ($this->sttyMode && is_string($this->sttyMode)) {
            $this->exec('stty ' . $this->sttyMode);
        }
    }

    public function clear(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->write("\x1B[2J\x1B[0f");
        } else {
            $this->write("\x1B[2J\x1B[3J\x1B[H");
        }
    }

    public function getLink(string $url, string $text): string
    {
        return "\e]8;;$url\a$text\e]8;;\a";
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

    public function info(mixed $text): void
    {
        $format = '<bgGreen> INFO </> %s</>';
        $this->writeLn($text, $format);
    }

    public function warn(mixed $text): void
    {
        $format = '<bgYellow> WARN </> %s</>';
        $this->writeLn($text, $format);
    }

    public function error(mixed $text): void
    {
        $format = '<bgRed> ERROR </> %s</>';
        $this->writeLn($text, $format);
    }

    public function newLine(int $countLines = 1): void
    {
        $nl = str_repeat("\n", $countLines);
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
                $columnWidths[$columnIndex] = max(
                    strlen(strval($value)) + 1,
                    $columnWidths[$columnIndex] ?? 0
                );
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
            $format = $i % 2 === 0 ? '<blue>%s</>' : '<white>%s</>';
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

    public function ask(string $question, string $default = ''): string
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write('<cyan>❯</> ');

        return trim(strval(fgets($this->input)));
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
            $this->exec('stty ' . $this->sttyMode);
        }
        $this->write('<up_2><eraseToBottom>');

        $this->write($answer ? '<green>✓</>' : '<red>✘</>');
        $this->writeLn($question, ' <cyan>%s</>');

        return $answer;
    }

    public function secret(string $question, mixed $default = ''): string
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write('<yellow>❯</> ');

        $this->exec('stty -echo');
        $answer = trim(strval(fgets($this->input)));
        $this->exec('stty echo');

        return $answer;
    }

    public function choice(string $text, array $options, bool $isRequired = true): string
    {
        $val = $this->select($text, $options, [], $isRequired, false);

        return (string) current($val);
    }

    public function select(
        string $text,
        array $options,
        array $selectedIndex = [],
        bool $isRequired = true,
        bool $isMultiple = true
    ): array {
        $isInteractive = $this->isInteractiveInput($this->input);
        $selected = array_fill_keys($selectedIndex, true);

        if (!$isInteractive) {
            return array_filter(
                $options,
                fn (int $index) => isset($selected[$index]),
                ARRAY_FILTER_USE_KEY
            );
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
            $this->exec('stty ' . $this->sttyMode);
        }

        return array_filter(
            $options,
            fn (int $index) => isset($selected[$index]),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function width(): int
    {
        return (int) trim(strval($this->exec('tput cols')));
    }

    public function height(): int
    {
        return (int) trim(strval($this->exec('tput lines')));
    }

    private function exec(string $command): bool|null|string
    {
        if ($this->useExec === false) {
            return null;
        }

        return __execCommand($command);
    }

    private function isInteractiveInput(mixed $inputStream): bool
    {
        // Check if the stream is stdin
        if ('php://stdin' !== (stream_get_meta_data($inputStream)['uri'])) {
            return false;
        }

        // Try stream_isatty first as it's the most reliable
        if (\function_exists('stream_isatty')) {
            return @stream_isatty($inputStream);
        }

        // Fallback to posix_isatty if available
        if (\function_exists('posix_isatty')) {
            return @posix_isatty($inputStream);
        }

        // Last resort - check if stty command works
        if (!\function_exists('exec')) {
            return true;
        }

        exec('stty 2> /dev/null', $output, $status);
        return $status !== 1;
    }

    private function readKeyPress(): ?string
    {
        // Read 3 or 4 bytes for the escape-sequence of the arrows
        $key = fread($this->input, 4);
        if (empty($key)) {
            return null;
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
        if ($k0 === 27 && isset($key[1], $key[2])) { // ESC sequence
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
                    default => null
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

        return str_replace(
            array_keys(self::$formats),
            $this->ansi ? array_values(self::$formats) : '',
            $text
        );
    }

    /**
     * Remove bash color symbols from string
     */
    public static function clearColor(string $str): string
    {
        $patterns = "/\e?\[[\d;]+m/";

        return (string) preg_replace($patterns, '', $str);
    }
}
