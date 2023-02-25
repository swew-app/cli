<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Command\CommandParser;
use Swew\Cli\Terminal\Output;

class SwewCommander
{
    private array $commands = [];

    public function __construct(
        private readonly array $argList,
        private readonly Output $output = new Output()
    )
    {
    }

    public function run(): void
    {
        if ($this->isNeedHelp() || count($this->argList) === 0) {
            $this->showHelp();
            return;
        }

        /** @var Command */
        $command = $this->getCommand($this->argList[0]);

        $status = $command();

        exit($status);
    }

    public function setCommands(array $commands): void
    {
        $this->commands = [];

        foreach ($commands as $commandClass) {
            $name = CommandParser::parseName($commandClass);
            $this->commands[$name] = $commandClass;
        }
    }

    public function getCommand(string $name, bool $stopOnError = true): object
    {
        if (isset($this->commands[$name])) {
            /** @var Command */
            $command = new $this->commands[$name]();

            $this->fillCommandArguments($command, array_slice($this->argList, 1));

            if (!$command->isValid()) {
                $errorMessage = $command->getErrorMessage();
                $this->output->error($errorMessage);

                if ($stopOnError) {
                    exit($command::ERROR);
                } else {
                    throw new \Exception("Get error for command '$name': $errorMessage");
                }
            }

            $command->setOutput($this->output);
            $command->init();

            return $command;
        }

        throw new \LogicException("Command '$name' not found");
    }

    protected function fillCommandArguments(Command &$command, array $argsForCommand): void
    {
        /** @var string */
        $name = $command::NAME;
        preg_match_all('/{([^}]+)}/', $name, $matches);

        $commandArguments = [];

        foreach ($matches[1] as $index => $arg) {
            $cmdArg = new CommandArgument($arg, $index);
            $cmdArg->parseInput($argsForCommand);
            $commandArguments[] = $cmdArg;
        }

        $command->setCommandArguments($commandArguments);
    }

    protected function isNeedHelp(): bool
    {
        return false;
    }

    protected function showHelp(string $name = ''): void
    {
        $class = empty($name) ? $this : $this->getCommand($name);

        $helpMessage = $class->getHelpMessage();

        $this->output->writeLn($helpMessage);
    }

    protected function getHelpMessage(): string
    {
        return '';
    }
}
