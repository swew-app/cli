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

    protected mixed $commander = null;

    /** @var Output */
    protected ?Output $output = null;

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
        foreach ($this->commandArguments as $value) {
            /** @var CommandArgument $value */
            if ($value->isValid() === false) {
                $name = $this->getName();
                $msg = $value->getErrorMessage();

                return "Get error for command '<b>$name</>': $msg";
            }
        }

        return '';
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
        /** @var string $str */
        $str = $this::NAME;
        $str = str_replace("\n", ' ', $str);
        $spacePos = strpos($str, ' ');
        if ($spacePos === false) {
            return $str;
        }

        return substr($str, 0, $spacePos);
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

        $optionKeyMaxLengths = 0;

        $options = [];

        foreach ($this->commandArguments as $arg) {
            $name = $arg->getNames();
            $options[$name] = $arg->getDescription();

            if ($optionKeyMaxLengths < strlen($name)) {
                $optionKeyMaxLengths = strlen($name);
            }
        }

        $params = [];

        foreach ($options as $name => $desc) {
            if (strlen($desc) > 0) {
                $params[] = ' <green>'.str_pad($name, $optionKeyMaxLengths + 2, ' ').'</>'.$desc;
            } else {
                $params[] = ' <green>'.$name.'</>';
            }
        }

        $varsForTmp = [
            '{desc}' => $this::DESCRIPTION,
            '{name}' => $this->getName(),
            '{options}' => implode("\n", $params),
        ];

        return str_replace(
            array_keys($varsForTmp),
            array_values($varsForTmp),
            $messageTemplate
        );
    }

    final public function argv(string $key): mixed
    {
        $arg = $this->arg($key);

        return $arg->getValue();
    }

    final public function arg(string $name): CommandArgument
    {
        foreach ($this->commandArguments as $arg) {
            if ($arg->is($name)) {
                return $arg;
            }
        }

        throw new LogicException("Can't find argument '$name'");
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

    final public function getCommander(): mixed
    {
        if ($this->commander instanceof SwewCommander) {
            return $this->commander;
        } else {
            throw new LogicException('SwewCommander instance not transferred');
        }
    }
}
