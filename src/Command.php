<?php

declare(strict_types=1);

namespace Swew\Cli;

use Exception;
use LogicException;
use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

abstract class Command
{
    public const NAME = '';

    public const DESCRIPTION = '';

    public const SUCCESS = 0;

    public const ERROR = 1;

    protected SwewCommander|null $commander = null;

    /** @var Output */
    protected Output|null $output = null;

    /** @var CommandArgument[] */
    private array $commandArguments = [];

    /** @var array<string> */
    private array $args = [];

    abstract public function __invoke(): int;

    public function init(): void
    {
    }

    final public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    final public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    final public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param  CommandArgument[]  $commandArguments
     */
    final public function setCommandArguments(array $commandArguments): void
    {
        $this->commandArguments = $commandArguments;
    }

    final public function setCommanderContext(SwewCommander $swewCommander): void
    {
        $this->commander = $swewCommander;
    }

    final public function getErrorMessage(): string
    {
        foreach ($this->commandArguments as $argument) {
            if (!$argument->isValid()) {
                return sprintf(
                    "Get error for command '<b>%s</>': %s",
                    $this->getName(),
                    $argument->getErrorMessage()
                );
            }
        }

        return '';
    }

    final public function isValid(): bool
    {
        foreach ($this->commandArguments as $argument) {
            if (!$argument->isValid()) {
                return false;
            }
        }

        return true;
    }

    final public function getName(): string
    {
        $name = static::NAME;
        $name = str_replace("\n", ' ', $name);

        if (is_string(($name))) {
            return strtok($name, ' ') ?: $name;
        }
        return '';
    }

    /**
     * Method for displaying help message for the current command by template
     */
    public function getHelpMessage(string $messageTemplate = ''): string
    {
        if ($messageTemplate === '') {
            $messageTemplate = "<yellow>Description:</>\n {desc}\n\n".
                "<yellow>Usage:</>\n {name} [options]\n\n".
                "Options:\n{options}";
        }

        $options = [];
        $maxLength = 0;

        foreach ($this->commandArguments as $arg) {
            $name = $arg->getNames();
            $options[$name] = $arg->getDescription();
            $maxLength = max($maxLength, strlen($name));
        }

        $formattedOptions = array_map(
            fn (string $name, string $desc) => $desc
                ? sprintf(' <green>%s</>%s', str_pad($name, $maxLength + 2, ' '), $desc)
                : sprintf(' <green>%s</>', $name),
            array_keys($options),
            array_values($options)
        );

        return str_replace(
            ['{desc}', '{name}', '{options}'],
            [static::DESCRIPTION, $this->getName(), implode("\n", $formattedOptions)],
            $messageTemplate
        );
    }

    final public function argv(string $key): mixed
    {
        return $this->arg($key)->getValue();
    }

    final public function arg(string $name): CommandArgument
    {
        foreach ($this->commandArguments as $arg) {
            if ($arg->is($name)) {
                return $arg;
            }
        }

        throw new LogicException(sprintf("Can't find argument '%s'", $name));
    }

    /**
     * @throws Exception
     */
    final public function call(string $commandName, array $args = []): void
    {
        $this->getCommander()->call($commandName);
    }

    final public function callSilent(): void
    {
        // TODO

    }

    final public function getCommander(): SwewCommander
    {
        if (!$this->commander instanceof SwewCommander) {
            throw new LogicException('SwewCommander instance not transferred');
        }

        return $this->commander;
    }
}
