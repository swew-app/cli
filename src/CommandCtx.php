<?php

declare(strict_types=1);

namespace Swew\Cli;

use LogicException;
use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

final readonly class CommandCtx
{
    /**
     * @param array<string, CommandArgument> $arguments
     * @param list<string> $args
     */
    public function __construct(
        public Output $output,
        private SwewCommander $commander,
        private array $arguments,
        private array $args,
    ) {
    }

    public function arg(string $name): CommandArgument
    {
        return $this->arguments[$name] ?? throw new LogicException("Can't find argument '$name'");
    }

    public function argv(string $name): mixed
    {
        return $this->arg($name)->getValue();
    }

    /** @return list<string> */
    public function getArgs(): array
    {
        return $this->args;
    }

    /** @param list<string> $args */
    public function call(string $commandName, array $args = []): int
    {
        return $this->commander->call($commandName, $args);
    }

    public function getCommander(): SwewCommander
    {
        return $this->commander;
    }
}
