<?php

declare(strict_types=1);

namespace Swew\Cli\Testing;

use Swew\Cli\Terminal\Output;

class TestHelper
{
    private $input;
    private $output;

    public function __construct()
    {
        $this->input = fopen('php://memory', 'r');
        $this->output = fopen('php://memory', 'w+');
    }

    public static function getOutput(bool $ansi = false): Output
    {
        $self = new self();
        return $self->createOutput($ansi);
    }

    public function createOutput(bool $ansi = false): Output
    {
        $output = new Output(
            $this->input,
            $this->output
        );

        $output->setAnsi($ansi);

        return $output;
    }

    public function getOutputContent($clear = true): bool|string
    {
        rewind($this->output);
        return stream_get_contents($this->output);
    }

    public function clearOutput(): void
    {
        ftruncate($this->output, 0);
    }

    public function getOutputContentAndClear(): bool|string
    {
        $content = $this->getOutputContent();
        $this->clearOutput();

        return $content;
    }
}
