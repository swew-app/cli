<?php

declare(strict_types=1);

namespace Swew\Cli;

use Closure;
use InvalidArgumentException;
use LogicException;
use Swew\Cli\Command\ArgType;
use Swew\Cli\Command\CommandDefinition;
use Swew\Cli\Terminal\Output;

class SwewCommander
{
    /** @var array<class-string> */
    protected array $commands = [];

    /** @var array<string, CommandDefinition> */
    private array $commandMap = [];

    private string $helpPrefix = '';

    /** @var list<string> */
    protected readonly array $argList;

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

    protected function init(): void
    {
    }

    public function run(): void
    {
        if ($this->isNeedHelp() || $this->argList === []) {
            $this->showHelp();
            return;
        }

        $name = $this->argList[0];
        if (!isset($this->commandMap[$name])) {
            $this->output->error("Command '$name' not found");
            $status = 1;
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
        try {
            $command = $this->getCommand($commandName, $args);
        } catch (InvalidArgumentException $error) {
            $this->output->error($error->getMessage());
            return 1;
        }
        return $command();
    }

    public function setHelpPrefix(string $helpPrefix): void
    {
        $this->helpPrefix = $helpPrefix;
    }

    /** @param array<class-string> $commands */
    protected function setCommands(array $commands): void
    {
        $this->commandMap = [];
        foreach ($commands as $commandClass) {
            $definition = new CommandDefinition($commandClass);
            $name = $definition->attribute->name;
            if (isset($this->commandMap[$name])) {
                throw new LogicException("Duplicate command '$name'");
            }
            $this->commandMap[$name] = $definition;
        }
    }

    /**
     * @param list<string> $args
     * @return Closure(): int
     */
    protected function getCommand(string $name, array $args = []): Closure
    {
        return $this->getDefinition($name)->prepare($this, $this->output, $args);
    }

    public function getCommandHelpMessage(string $name, string $template = ''): string
    {
        return $this->getDefinition($name)->getHelpMessage($template);
    }

    private function getDefinition(string $name): CommandDefinition
    {
        if (isset($this->commandMap[$name])) {
            return $this->commandMap[$name];
        }
        foreach ($this->commandMap as $definition) {
            if ($definition->className === $name) {
                return $definition;
            }
        }
        throw new LogicException("Command '$name' not found");
    }

    protected function isNeedHelp(): bool
    {
        $name = $this->argList[0] ?? '';
        $definitions = isset($this->commandMap[$name]) ? [$this->commandMap[$name]] : $this->commandMap;
        for ($i = 0, $count = count($this->argList); $i < $count; $i++) {
            $arg = $this->argList[$i];
            if ($arg === '--') {
                break;
            }
            if (CommandDefinition::isOption($arg)) {
                $parts = explode('=', ltrim($arg, '-'), 2);
                foreach ($definitions as $definition) {
                    $option = $definition->option($parts[0]);
                    if ($option !== null) {
                        $next = $this->argList[$i + 1] ?? null;
                        if (
                            count($parts) === 1
                            && $option->type !== ArgType::Bool
                            && $next !== null
                            && $next !== '--'
                            && !CommandDefinition::isOption($next)
                        ) {
                            $i++;
                        }
                        continue 2;
                    }
                }
            }
            if (in_array($arg, ['-h', '-help', '--help', 'help'], true)) {
                return true;
            }
        }
        return false;
    }

    protected function showHelp(): void
    {
        $name = $this->argList[0] ?? '';
        $this->output->writeLn(
            isset($this->commandMap[$name]) ? $this->getCommandHelpMessage($name) : $this->getHelpMessage(),
        );
    }

    protected function getHelpMessage(): string
    {
        $result = [];
        if ($this->helpPrefix !== '') {
            $result[] = $this->helpPrefix;
        }
        $result[] = '<yellow>Available commands:</>';
        foreach ($this->commandMap as $name => $definition) {
            $result[] = " <green>$name</>: {$definition->attribute->desc}";
        }
        return implode("\n", $result);
    }
}
