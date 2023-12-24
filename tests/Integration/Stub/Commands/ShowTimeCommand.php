<?php

declare(strict_types=1);

namespace Integration\Stub\Commands;

use Swew\Cli\Command;

class ShowTimeCommand extends Command
{
    public const NAME = "show:time
    {prefix}";

    public const DESCRIPTION = 'The function shows the current time.';

    public function __invoke(): int
    {
        $prefix = $this->arg('prefix')->getValue();
        $prefix2 = $this->argv('prefix');

        $time = $prefix . '_' . $prefix2 . ' 2023-05-03 06:35';

        $this->output->write($time);

        return self::SUCCESS;
    }
}
