<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

class SwewCommander
{
    protected array $commands = [];

    private string $helpPrefix = '';

    public function __construct(
        private readonly array $argList,
        private readonly Output $output = new Output(),
        private readonly bool $stopByStatus = true
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

        if ($this->stopByStatus) {
            exit($status);
        }
    }

    public function setHelpPrefix(string $helpPrefix): void
    {
        $this->helpPrefix = $helpPrefix;
    }

    public function setCommands(array $commands): void
    {
        $this->commands = [];

        foreach ($commands as $commandClass) {
            $name = $this->parseName($commandClass);
            $this->commands[$name] = $commandClass;
        }
    }

    public function getCommand(string $name): object
    {
        if (isset($this->commands[$name])) {
            /** @var Command */
            $command = new $this->commands[$name]();

            $this->fillCommandArguments($command, array_slice($this->argList, 1));

            if (!$command->isValid()) {
                $errorMessage = $command->getErrorMessage();
                $this->output->error($errorMessage);

                if ($this->stopByStatus) {
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
        if (count($this->argList) === 0) {
            return true;
        }

        return in_array('-h', $this->argList) || in_array('-help', $this->argList) || in_array('--help', $this->argList);
    }

    protected function showHelp(): void
    {
        $name = '';

        if (count($this->argList) === 2) {
            $name = $this->argList[0];
        }

        if (isset($this->commands[$name])) {
            /** @var Command */
            $class = new $this->commands[$name]();

            $this->fillCommandArguments($class, []);
        } else {
            $class = $this;
        }

        $helpMessage = $class->getHelpMessage();

        $this->output->writeLn($helpMessage);
    }

    protected function getHelpMessage(): string
    {
        $result = [];

        if ($this->helpPrefix) {
            $result[] = $this->helpPrefix;
        }

        $result[] = '<yellow>Available commands:</>';

        // проходим по списку комманд и собираем название и десприпшен
        foreach ($this->commands as $commandClass) {
            $name = $this->parseName($commandClass);
            $description = $commandClass::DESCRIPTION;

            $result[] = " <yellow>$name</>: $description";
        }

        return implode("\n", $result);
    }

    private function parseName(string $str): string
    {
        if (class_exists($str)) {
            $str = constant($str . '::NAME');
        }

        $spacePos = strpos($str, ' ');
        if ($spacePos === false) {
            return $str;
        }
        return substr($str, 0, $spacePos);
    }
}
