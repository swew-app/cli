<?php

declare(strict_types=1);

namespace Swew\Cli\Lib;

class ProgressBar
{
    private int $index = 0;

    public function __construct(
        private readonly Output $output,
        private int $total = 100,
    ) {
    }

    public function start(): void
    {
        $this->index = 0;
    }

    public function finish(): void
    {
        $this->output->write("\e[2K\r");
    }

    public function increment()
    {
        $this->index++;

        $progress = intval($this->index / $this->total * 100);

        if ($progress > 100) {
            $progress = 100;
        }

        $line = "\r[" . str_repeat('▓', intval($progress / 2)) . str_repeat('░', 50 - intval($progress / 2)) . "] $progress%";

        $this->output->write($line);
    }
}
