<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Terminal\Output;

abstract class Command
{
    public const SUCCESS = 0;
    public const ERROR = 1;

    public function __invoke(): int
    {
        return self::SUCCESS;
    }

    private ?Output $output = null;

    public function init(): void
    {
    }

    final public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    final public function isValid(): bool
    {
        return true;
    }

    final public function getErrorMessage(): string
    {
        return '';
    }

    public function getHelpMessage(): string
    {
        return '';
    }

    final public function call(): void
    {
        // TODO
        return;
    }

    final public function callSilent(): void
    {
        // TODO
        return;
    }
}
