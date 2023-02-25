<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

abstract class Command
{
    public const NAME = '';
    public const DESCRIPTION = '';

    public const SUCCESS = 0;
    public const ERROR = 1;

    protected ?Output $output = null;

    private array $commandArguments =[];

    public function __invoke(): int
    {
        return self::SUCCESS;
    }

    public function init(): void
    {
    }

    final public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    final public function setCommandArguments(array $commandArguments): void
    {
        $this->commandArguments = $commandArguments;
    }

    final public function isValid(): bool
    {
        foreach ($this->commandArguments as $value) {
            /** @var CommandArgument $value */
            if ($value->isValid() === false) {
                return false;
            }
        }

        return true;
    }

    final public function getErrorMessage(): string
    {
        foreach ($this->commandArguments as $value) {
            /** @var CommandArgument $value */
            if ($value->isValid() === false) {
                return $value->getErrorMessage();
            }
        }
        return '';
    }

    public function getHelpMessage(): string
    {
        // TODO
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
