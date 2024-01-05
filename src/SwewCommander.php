<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

class SwewCommander
{
    protected array $commands = [];

    private array $commandMap = [];

    private string $helpPrefix = '';

    private readonly array $argList;

    public function __construct(
        array $argv,
        private readonly Output $output = new Output(),
        private readonly bool $stopByStatus = true
    ) {
        $this->argList = array_slice($argv, 1);

        $this->setCommands($this->commands);

        $this->init();
    }

    /**
     * Method called when creating
     *
     * @return void
     */
    protected function init(): void
    {
    }

    public function run(): void
    {
        if ($this->isNeedHelp() || count($this->argList) === 0) {
            $this->showHelp();
            return;
        }

        $status = $this->call($this->argList[0]);

        if ($this->stopByStatus) {
            exit($status);
        }
    }

    public function call(string $commandName, array $args = []): int
    {
        /** @var Command $command */
        $command = $this->getCommand($commandName, $args);

        if ($command->isValid()) {
            $command->init();
            $status = $command();
        } else {
            $status = $command::ERROR;
            $errorMessage = $command->getErrorMessage();
            $this->output->error($errorMessage);
        }

        return $status;
    }

    public function setHelpPrefix(string $helpPrefix): void
    {
        $this->helpPrefix = $helpPrefix;
    }

    protected function setCommands(array $commands): void
    {
        $this->commandMap = [];

        foreach ($commands as $commandClass) {
            $name = $this->parseName($commandClass);
            $this->commandMap[$name] = $commandClass;
        }
    }

    /**
     * @param string $name Command NAME or Command class
     * @param array $args
     * @return Command
     */
    protected function getCommand(string $name, array $args = []): Command
    {
        if (class_exists($name)) {
            $name = $this->parseName($name);
        }

        $args = count($args) === 0 ? $this->argList : $args;

        if (isset($this->commandMap[$name])) {
            /** @var Command $command */
            $command = new $this->commandMap[$name]();

            $command->setOutput($this->output);

            if ($args[0] === $name) {
                array_shift($args);
            }

            $this->fillCommandArguments($command, $args);

            return $command;
        }

        throw new \LogicException("Command '$name' not found");
    }

    /**
     * Fills arguments with "CommandArgument" values that can be worked with
     */
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
        $command->setCommanderContext($this);
    }

    protected function isNeedHelp(): bool
    {
        return in_array('-h', $this->argList)
            || in_array('-help', $this->argList)
            || in_array('--help', $this->argList)
            || in_array('help', $this->argList);
    }

    protected function showHelp(): void
    {
        $name = '';

        if (count($this->argList) === 2) {
            $name = $this->argList[0];
        }

        if (isset($this->commandMap[$name])) {
            /** @var Command */
            $class = new $this->commandMap[$name]();

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

        // проходим по списку команд и собираем название и десприпшен
        foreach ($this->commandMap as $commandClass) {
            $name = $this->parseName($commandClass);
            $description = $commandClass::DESCRIPTION;

            $result[] = " <green>$name</>: $description";
        }

        return implode("\n", $result);
    }

    private function parseName(string $str): string
    {
        if (class_exists($str)) {
            $str = constant($str . '::NAME');
        }

        $str = str_replace("\n", '', $str);
        $spacePos = strpos($str, ' ');
        if ($spacePos === false) {
            return $str;
        }
        return substr($str, 0, $spacePos);
    }
}
