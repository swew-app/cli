<?php

declare(strict_types=1);

namespace Swew\Cli\Terminal;

class Output
{
    private $input;
    private $output;

    private bool $ansi = true;

    private bool|null|string $sttyMode = null;

    public function __construct(
        mixed $stdin = null,
        mixed $stdout = null,
        private readonly bool $useExec = true
    ) {
        $input = $stdin ?? fopen('php://stdin', 'r');
        $output = $stdout ?? fopen('php://output', 'r');

        if (!is_resource($output) || !is_resource($input)) {
            throw new \Exception("Wrong type for \$output:($output) or \$input:($input) stream");
        }

        $this->input = $input;
        $this->output = $output;
        $this->sttyMode = $this->exec('stty -g');
    }

    public function __destruct()
    {
        if (is_resource($this->input)) {
            fclose($this->input);
        }
        if (is_resource($this->output)) {
            fclose($this->output);
        }
        if ($this->sttyMode) {
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
        return "\e]8;;${url}\a${text}\e]8;;\a";
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

    public function table(array $titles, array $lines): void
    {
        // Define the data to display in the table
        $data = [
            $titles,
            ...$lines,
        ];

        // Determine the maximum width of each column
        $columnWidths = [];
        foreach ($data as $row) {
            foreach ($row as $columnIndex => $value) {
                $columnWidths[$columnIndex] = max(strlen(strval($value)), $columnWidths[$columnIndex] ?? 0);
            }
        }

        // Output the table header
        $line = '';
        foreach ($data[0] as $columnIndex => $value) {
            $line .= sprintf("%-{$columnWidths[$columnIndex]}s  ", $value);
        }
        $this->writeLn($line, '<blue>%s</>');

        // Output the table separator
        $line = '';
        foreach ($columnWidths as $width) {
            $line .= sprintf("%'-{$width}s  ", '');
        }
        $this->writeLn($line, '<blue>%s</>');

        // Output the table rows
        for ($i = 1; $i < count($data); $i++) {
            $line = '';
            foreach ($data[$i] as $columnIndex => $value) {
                $line .= sprintf("%-{$columnWidths[$columnIndex]}s  ", $value);
            }
            $format = $i % 2 === 0 ? '<gray>%s</>' : '<white>%s</>';
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

    public function ask(string $question, mixed $default = ''): string
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write("<cyan>❯</> ");

        return trim(strval(fgets($this->input)));
    }

    public function secret(string $question, mixed $default = ''): string
    {
        $isInteractive = $this->isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write("<yellow>❯</> ");

        $this->exec('stty -echo');
        $answer = trim(strval(fgets($this->input)));
        $this->exec('stty echo');

        return $answer;
    }

    public function choice(string $text, array $options, bool $isRequired = true): string
    {
        $val = $this->select($text, $options, [], $isRequired, false);

        return current($val);
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

        $this->write('<saveCursor><eraseToBottom>');

        // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
        $this->exec('stty -icanon -echo');

        $cursorIndex = 0;

        while (true) {
            $this->write('<restoreCursor><eraseToBottom>');

            $n = 7; // Count of showed options
            $i = max(0, $cursorIndex - 3);
            $count = count($options);
            $numberOfLinesDrawn = 0;

            // Output the options list
            for (; $i < $count && $numberOfLinesDrawn < $n; $i++) {
                if ($i === $cursorIndex) {
                    $this->write('> ');
                } else {
                    $this->write('  ');
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

            // Wait for user input
            $key = ord(fread($this->input, 1));

            // Move the selection up or down based on user input
            switch ($key) {
                case 65: // Up arrow
                    $cursorIndex = max(0, $cursorIndex - 1);
                    break;
                case 66: // Down arrow
                    $cursorIndex = min(count($options) - 1, $cursorIndex + 1);
                    break;
                case 32: // Space
                    if (isset($selected[$cursorIndex])) {
                        unset($selected[$cursorIndex]);
                    } else {
                        $selected[$cursorIndex] = true;

                        if (!$isMultiple) {
                            break 2;
                        }
                    }
                    break;
                case 10: // Enter key
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

        $this->write('<restoreCursor><eraseToBottom>');

        $this->exec('stty ' . $this->sttyMode);

        return array_filter(
            $options,
            fn (int $index) => isset($selected[$index]),
            ARRAY_FILTER_USE_KEY
        );
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
        if ('php://stdin' !== (stream_get_meta_data($inputStream)['uri'])) {
            return false;
        }

        if (\function_exists('stream_isatty')) {
            return @stream_isatty(fopen('php://stdin', 'r'));
        }

        if (\function_exists('posix_isatty')) {
            return @posix_isatty(fopen('php://stdin', 'r'));
        }

        if (!\function_exists('exec')) {
            return true;
        }

        exec('stty 2> /dev/null', $output, $status);

        return 1 !== $status;
    }

    private function format(string $text): string
    {
        $formats = [
            // reset
            '</>' => "\e[m",

            '<b>' => "\e[1m",
            '<f>' => "\e[2m",
            '<i>' => "\e[3m",
            '<u>' => "\e[4m",
            '<blink>' => "\e[5m",
            '<hidden>' => "\e[8m",
            '<s>' => "\e[9m",

            '<black>' => "\e[38;5;0m",
            '<gray>' => "\e[38;5;8m",
            '<white>' => "\e[38;5;7m",

            '<red>' => "\e[38;5;1m",
            '<green>' => "\e[38;5;2m",
            '<yellow>' => "\e[38;5;3m",
            '<blue>' => "\e[38;5;4m",
            '<purple>' => "\e[38;5;5m",
            '<cyan>' => "\e[38;5;6m",

            '<bgRed>' => "\e[48;5;1m",
            '<bgGreen>' => "\e[48;5;2m",
            '<bgYellow>' => "\e[48;5;3m",
            '<bgBlue>' => "\e[48;5;4m",
            '<bgPurple>' => "\e[48;5;5m",
            '<bgCyan>' => "\e[48;5;6m",

            '<saveCursor>' => "\e[s",
            '<restoreCursor>' => "\e[u",
            '<eraseToEndLine>' => "\e[K",
            '<eraseToStartLine>' => "\e[1K",
            '<eraseLine>' => "\e[2K",
            '<eraseToBottom>' => "\e[J",
            '<eraseToTop>' => "\e[1J",
        ];

        return str_replace(
            array_keys($formats),
            $this->ansi ? array_values($formats) : '',
            $text
        );
    }
}
