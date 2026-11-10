<?php

declare(strict_types=1);

namespace Swew\Cli;

use Swew\Cli\Command\ArgType;
use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

class SwewCommander
{
    /** @var array<class-string<Command>> */
    protected array $commands = [];

    /** @var array<string, class-string<Command>> */
    private array $commandMap = [];

    private string $helpPrefix = '';

    /** @var list<string> */
    private readonly array $argList;

    /** @param list<string> $argv */
    public function __construct(
        array $argv,
        private readonly Output $output = new Output(),
        private readonly bool $stopByStatus = true,
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

        $name = $this->argList[0];
        if (!isset($this->commandMap[$name])) {
            $this->output->error("Command '$name' not found");
            $status = Command::ERROR;
        } else {
            $status = $this->call($name, array_slice($this->argList, 1));
        }

        if ($this->stopByStatus) {
            exit($status);
        }
    }

    /** @param list<string> $args */
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
        $this->commandMap = [];
        foreach ($commands as $commandClass) {
            $this->commandMap[$this->parseName($commandClass)] ??= $commandClass;
        }
    }

    /**
     * @param list<string>|null $args
     */
    protected function getCommand(string $name, ?array $args = null): Command
    {
        if (class_exists($name)) {
            $name = $this->parseName($name);
        }

        if (!isset($this->commandMap[$name])) {
            throw new \LogicException("Command '$name' not found");
        }

        $command = new $this->commandMap[$name]();
        $command->setOutput($this->output);

        $command->setArgs($args === null ? $this->argList : [$name, ...$args]);
        if ($args === null) {
            $args = $this->argList;
            if (($args[0] ?? null) === $name) {
                array_shift($args);
            }
        }
        $this->fillCommandArguments($command, $args);

        return $command;
    }

    /**
     * @param list<string> $argsForCommand
     */
    protected function fillCommandArguments(Command &$command, array $argsForCommand): void
    {
        $matches = [];
        preg_match_all('/{([^}]+)}/', $command::NAME, $matches);

        $commandArguments = [];
        $position = 0;
        foreach ($matches[1] as $arg) {
            $cmdArg = new CommandArgument($arg, $position);
            $commandArguments[] = $cmdArg;
            if (!$cmdArg->isArgument()) {
                $position++;
            }
        }

        $positionals = [];
        $optionsEnded = false;
        for ($i = 0, $count = count($argsForCommand); $i < $count; $i++) {
            $value = $argsForCommand[$i];
            if (!$optionsEnded && $value === '--') {
                $optionsEnded = true;
            } elseif (!$optionsEnded && str_starts_with($value, '-') && !is_numeric($value)) {
                foreach ($commandArguments as $argument) {
                    if (
                        $argument->isArgument()
                        && $argument->is(ltrim($value, '-'))
                        && $argument->getType() !== ArgType::Bool
                    ) {
                        $next = $argsForCommand[$i + 1] ?? null;
                        if ($next !== null && (!str_starts_with($next, '-') || is_numeric($next))) {
                            $i++;
                        }
                        break;
                    }
                }
            } else {
                $positionals[] = $value;
            }
        }

        foreach ($commandArguments as $argument) {
            $argument->parseInput($argument->isArgument() ? $argsForCommand : $positionals);
        }

        $command->setCommandArguments($commandArguments);
        $command->setCommanderContext($this);
    }

    protected function isNeedHelp(): bool
    {
        $helpFlags = ['-h', '-help', '--help', 'help'];
        foreach ($this->argList as $arg) {
            if ($arg === '--') {
                break;
            }
            if (in_array($arg, $helpFlags, true)) {
                return true;
            }
        }
        return false;
    }

    protected function showHelp(): void
    {
        $name = $this->argList[0] ?? '';
        $class = isset($this->commandMap[$name]) ? new $this->commandMap[$name]() : $this;

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
        if (is_a($str, Command::class, true)) {
            $str = $str::NAME;
        }

        return preg_split('/\s+/', trim($str), 2)[0] ?? '';
    }
}
