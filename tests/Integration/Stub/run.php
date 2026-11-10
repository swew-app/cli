<?php

declare(strict_types=1);

require_once __DIR__ . '/DemoCli.php';

(new Demo\Cli\DemoCli($argv))->run();
