<?php

declare(strict_types=1);

require_once 'Stub/DemoCli.php';

use Demo\Cli\DemoCli;
use Swew\Cli\Testing\TestHelper;

it('SwewCommander help', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'help'];

    (new DemoCli($argv, $output, false))->run();

    $msg = "Available commands:
 show:time: The function shows the current time.
";

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander command help', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'show:time', 'help'];

    (new DemoCli($argv, $output, false))->run();

    $msg = "Description:
 The function shows the current time.

Usage:
 show:time [options]

Options:
 prefix
";

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander command ERROR', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'show:time'];

    (new DemoCli($argv, $output, false))->run();

    $msg = " ERROR  Get error for command 'show:time': prefix - is required\n";

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander command GOOD', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'show:time', 'A'];

    (new DemoCli($argv, $output, false))->run();

    $msg = "A_A 2023-05-03 06:35";

    expect($helper->getOutputContentAndClear())->toBe($msg);
});
