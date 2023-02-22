<?php

declare(strict_types=1);

namespace Swew\Cli\Lib;

class Output
{
    private $input;
    private $output;

    private bool $ansi = true;

    private array $formats = [
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
        '<gray>' => "\e[38;5;240m",
        '<white>' => "\e[38;5;7m",

        '<red>' => "\e[38;5;9m",
        '<green>' => "\e[38;5;40m",
        '<yellow>' => "\e[38;5;11m",
        '<blue>' => "\e[38;5;12m",
        '<purple>' => "\e[38;5;13m",
        '<cyan>' => "\e[38;5;14m",

        '<bgRed>' => "\e[48;5;9m",
        '<bgGreen>' => "\e[48;5;40m",
        '<bgYellow>' => "\e[48;5;11m",
        '<bgBlue>' => "\e[48;5;12m",
        '<bgPurple>' => "\e[48;5;13m",
        '<bgCyan>' => "\e[48;5;14m",
    ];

    public function __construct()
    {
        $input = fopen('php://stdin', 'r');
        $output = fopen('php://output', 'r');

        if (!is_resource($output) || !is_resource($input)) {
            throw new \Exception('Wrong type for output or input');
        }

        $this->input = $input;
        $this->output = $output;
    }

    public function __destruct()
    {
        if (is_resource($this->input)) {
            fclose($this->input);
        }
        if (is_resource($this->output)) {
            fclose($this->output);
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

    public function link(string $url, string $text): string
    {
        return "\e]8;;${url}\a${text}\e]8;;\a";
    }

    public function write(mixed $text, $format = '%s'): void
    {
        $text = sprintf($format, $text);

        $text = str_replace(
            array_keys($this->formats),
            $this->ansi ? array_values($this->formats) : '',
            $text
        );

        fwrite($this->output, $text);
    }

    public function writeLn(mixed $text, $format = '%s'): void
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
        $isInteractive = Helpers::isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write("<cyan>❯ </> ");

        return trim(strval(fgets($this->input)));
    }

    public function secret(string $question, mixed $default = ''): string
    {
        $isInteractive = Helpers::isInteractiveInput($this->input);

        if (!$isInteractive) {
            return $default;
        }

        $this->writeLn($question);
        $this->write("<cyan>❯ </> ");

        system('stty -echo');
        $answer = trim(strval(fgets($this->input)));
        system('stty echo');

        return $answer;
    }
}
