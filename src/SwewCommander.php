<?php

declare(strict_types=1);

namespace Swew\Cli;

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

    public function getCommand(string $name): object
    {
        if (isset($this->commands[$name])) {
            $command = new $this->commands[$name]();
            // TODO: парсим аргументы в массив CommandArguments, передавая массив $args
            // TODO: Валидируем
            $command->setOutput($this->output);
            $command->init();
            return $command;
        }

        throw new \LogicException("Command '$name' not found");
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
