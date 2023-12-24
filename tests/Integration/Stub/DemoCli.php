<?php

declare(strict_types=1);

namespace Demo\Cli;

require_once __DIR__.'/../../../vendor/autoload.php';
require_once 'Commands/ShowTimeCommand.php';

use Integration\Stub\Commands\ShowTimeCommand;
use Swew\Cli\SwewCommander;

class DemoCli extends SwewCommander
{
    protected array $commands = [
        ShowTimeCommand::class,
    ];
}
