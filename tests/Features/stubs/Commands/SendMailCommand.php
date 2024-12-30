<?php

declare(strict_types=1);

use Swew\Cli\Command;

class SendMailCommand extends Command
{
    public const NAME = 'send:mail {email} {--count=1 (int) : Count of mails} { --id=[] : User ids} {-silent|-S (bool) : No log message}';
    public const DESCRIPTION = 'Command to send email';

    public string $lastArgument = '';

    public function __invoke(): int
    {
        $this->output->write('Email sended');

        return self::SUCCESS;
    }
}
