<?php

declare(strict_types=1);

namespace Swew\Cli\Terminal;

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
        if ($this->output->isAnsi()) {
            $this->output->write("\r<eraseLine>");
        }
    }

    public function increment(): void
    {
        $this->index++;
        if (!$this->output->isAnsi()) {
            return;
        }

        $progress = $this->total > 0 ? min(100, (int) (($this->index / $this->total) * 100)) : 100;
        $filled = max(0, min(50, (int) ($progress / 2)));

        $line = "\r <green>" . str_repeat('▓', $filled) . '</>' . str_repeat('░', max(0, 50 - $filled)) . " $progress%";

        $this->output->write($line);
    }
}
