<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

class SwewCommander
{
    /** @var array<string, class-string<Command>> */
    protected array $commands = [];

    /** @var array<string, class-string<Command>> */
    private array $commandMap = [];

    private string $helpPrefix = '';

    /** @var array<int, string> */
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
     */
    protected function init(): void
    {
    }

    public function run(): void
    {
        if ($this->isNeedHelp() || empty($this->argList)) {
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
        $command = $this->getCommand($commandName, $args);

        if ($command->isValid()) {
            $command->init();
            return $command();
        }

        $this->output->error($command->getErrorMessage());
        return Command::ERROR;
    }

    public function setHelpPrefix(string $helpPrefix): void
    {
        $this->helpPrefix = $helpPrefix;
    }

    /**
     * @param array<class-string<Command>> $commands
     */
    protected function setCommands(array $commands): void
    {
        $this->commandMap = array_reduce(
            $commands,
            fn (array $map, string $commandClass) => $map + [$this->parseName($commandClass) => $commandClass],
            []
        );
    }

    /**
     * @param array<string> $args
     */
    protected function getCommand(string $name, array $args = []): Command
    {
        if (class_exists($name)) {
            $name = $this->parseName($name);
        }

        if (!isset($this->commandMap[$name])) {
            throw new \LogicException("Command '$name' not found");
        }

        $args = empty($args) ? $this->argList : $args;
        $command = new $this->commandMap[$name]();
        $command->setOutput($this->output);
        $command->setArgs($this->argList);

        if (isset($args[0]) && $args[0] === $name) {
            array_shift($args);
        }

        $this->fillCommandArguments($command, $args);

        return $command;
    }

    /**
     * @param array<string> $argsForCommand
     */
    protected function fillCommandArguments(Command &$command, array $argsForCommand): void
    {
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
        $helpFlags = ['-h', '-help', '--help', 'help'];
        return !empty(array_intersect($helpFlags, $this->argList));
    }

    protected function showHelp(): void
    {
        $name = $this->argList[0] ?? '';
        $class = isset($this->commandMap[$name])
            ? new $this->commandMap[$name]()
            : $this;

        if ($class instanceof Command) {
            $this->fillCommandArguments($class, []);
        }

        $this->output->writeLn($class->getHelpMessage());
    }

    protected function getHelpMessage(): string
    {
        $result = [];

        if ($this->helpPrefix) {
            $result[] = $this->helpPrefix;
        }

        $result[] = '<yellow>Available commands:</>';

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
            $str = constant($str.'::NAME');
        }

        $str = str_replace("\n", '', $str);
        $spacePos = strpos($str, ' ');

        return $spacePos === false ? $str : substr($str, 0, $spacePos);
    }
}
