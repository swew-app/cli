<?php

declare(strict_types=1);

use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;
use Swew\Cli\Command\ArgType;
use Swew\Cli\CommandCtx;

#[CliCommand(name: 'send:mail', desc: 'Command to send email')]
class SendMailCommand
{
    #[CliCtx]
    private CommandCtx $ctx;

    #[Argument(position: 0)]
    public string $email;

    #[Argument(desc: 'Count of mails')]
    public int $count = 1;

    #[Argument(name: 'id', desc: 'User ids', itemType: ArgType::Str)]
    public array $ids = [];

    #[Argument(aliases: ['S'], desc: 'No log message')]
    public bool $silent;

    public string $lastArgument = '';

    public function __invoke(): int
    {
        $this->ctx->output->write('Email sended');
        return 0;
    }
}
