<?php

declare(strict_types=1);

namespace Integration\Stub\Commands;

use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;
use Swew\Cli\CommandCtx;

#[CliCommand(name: 'show:time', desc: 'The function shows the current time.')]
class ShowTimeCommand
{
    #[CliCtx]
    private CommandCtx $ctx;

    #[Argument(name: 'prefix', required: false)]
    public string $prefix = '';

    public function __invoke(): int
    {
        $prefix = $this->ctx->arg('prefix')->getValue();
        $this->ctx->output->write($prefix . '_' . $this->prefix . ' 2023-05-03 06:35');
        return 0;
    }
}
