<?php

declare(strict_types=1);

namespace TestSwew\Commands;

use Swew\Cli\Command;

class SendMailCommand extends Command
{
    public const NAME = 'send:mail {email} {--count=1 (int) : Count of mails} { --id=[]} {-silent|-S (bool)}';
    public const DESCRIPTION = 'Command to send email';

    public function __invoke(): int
    {
        $this->output->write('Email sended');

        return self::SUCCESS;
    }
}
