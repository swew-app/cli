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

    private array $commandArguments = [];

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

    final public function getName(): string
    {
        $str = $this::NAME;

        $spacePos = strpos($str, ' ');
        if ($spacePos === false) {
            return $str;
        }
        return substr($str, 0, $spacePos);
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
        $result = [];

        $result[] = '<yellow>Description:</>';
        $result[] = ' ' . $this::DESCRIPTION;
        $result[] = '';
        $result[] = '<yellow>Usage:</>';
        $result[] = ' ' . $this->getName() . " [options]";
        $result[] = '';
        $result[] = '<yellow>Options:</>';

        $options = [];
        $optionKeyMaxLengths = 0;

        foreach ($this->commandArguments as $arg) {
            /** @var CommandArgument $arg */
            $name = $arg->getNames();
            $options[$name] = $arg->getDescription();

            if ($optionKeyMaxLengths < strlen($name)) {
                $optionKeyMaxLengths = strlen($name);
            }
        }

        foreach ($options as $name => $desc) {
            if (strlen($desc) > 0) {
                $str = ' <green>' . str_pad($name, $optionKeyMaxLengths + 2, ' ') . '</>' . $desc;
            } else {
                $str = ' <green>' . $name . '</>';
            }

            $result[] = $str;
        }

        return implode("\n", $result);
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
