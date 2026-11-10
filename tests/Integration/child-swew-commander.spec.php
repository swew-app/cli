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

    $msg = 'Available commands:
 show:time: The function shows the current time.
';

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander command help', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'show:time', 'help'];

    (new DemoCli($argv, $output, false))->run();

    $msg = 'Description:
 The function shows the current time.

Usage:
 show:time [options]

Options:
 --prefix
';

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander command uses its optional default', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'show:time'];

    (new DemoCli($argv, $output, false))->run();

    $msg = '_ 2023-05-03 06:35';

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander command GOOD', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();
    $argv = ['script.php', 'show:time', '--prefix', 'A'];

    (new DemoCli($argv, $output, false))->run();

    $msg = 'A_A 2023-05-03 06:35';

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('CLI processes return success and input-error status codes', function (array $args, int $status, string $message) {
    $process = proc_open(
        [PHP_BINARY, __DIR__ . '/Stub/run.php', ...$args],
        [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
        $pipes,
    );
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    expect(proc_close($process), $output)->toBe($status);
    expect($output)->toContain($message);
})->with([
    [['show:time', '--prefix', 'at'], 0, 'at_at 2023-05-03 06:35'],
    [['show:time', '--prefix'], 1, "Option 'prefix' needs a value"],
    [['missing'], 1, "Command 'missing' not found"],
    [['show:time', '--help'], 0, '--prefix'],
]);
