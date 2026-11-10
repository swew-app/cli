<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../vendor/autoload.php';
require_once 'stubs/Commands/SendMailCommand.php';

use Swew\Cli\SwewCommander;
use Swew\Cli\Testing\TestHelper;

class TestSwewCommander extends SwewCommander
{
    protected array $commands = [
        \SendMailCommand::class,
        CaptureCommand::class,
    ];

    public function __call(string $name, array $args): mixed
    {
        return $this->$name(...$args);
    }
}

class CaptureCommand extends \Swew\Cli\Command
{
    public const NAME = "capture\n{--quiet= (bool)} {value=} {--count= (int)}";
    public const DESCRIPTION = 'Capture arguments';

    public function __invoke(): int
    {
        $this->output->write($this->argv('value'));
        return 7;
    }
}

function testFactory(array $args): array
{
    $helper = new TestHelper();
    $sc = new TestSwewCommander($args, $helper->getOutput(), false);

    $sc->run();

    return [
        'SwewCommander' => $sc,
        'TestHelper' => $helper,
    ];
}

// = = = = = = = = = = = = =

it('SwewCommander :setCommands :getCommand', function () {
    $args = ['script.php', 'send:mail', 'user@mail.com', '--count', '1', '-silent'];

    [
        'SwewCommander' => $sc,
        'TestHelper' => $helper,
    ] =
        testFactory($args);

    expect($helper->getOutputContentAndClear())->toBe('Email sended');
});

it('SwewCommander :setCommands :getCommand - with error', function () {
    $args = ['script.php', 'send:mail', 'user@mail.com', '--count', '1'];

    [
        'SwewCommander' => $sc,
        'TestHelper' => $helper,
    ] =
        testFactory($args);

    expect($helper->getOutputContentAndClear())
        ->toBe(" ERROR  Get error for command 'send:mail': -silent,-S - is required\n");
});

it('SwewCommander :isNeedHelp :showHelp - Commander', function () {
    $args = ['script.php', '-h'];

    [
        'SwewCommander' => $sc,
        'TestHelper' => $helper,
    ] =
        testFactory($args);

    $msg = <<<'MSG'
        Available commands:
         send:mail: Command to send email
         capture: Capture arguments

        MSG;

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('SwewCommander :isNeedHelp :showHelp - Command', function () {
    $args = ['script.php', 'send:mail', '-h'];

    [
        'SwewCommander' => $sc,
        'TestHelper' => $helper,
    ] =
        testFactory($args);

    $msg = <<<'MSG'
        Description:
         Command to send email

        Usage:
         send:mail [options]

        Options:
         email
         -count      Count of mails
         -id         User ids
         -silent,-S  No log message

        MSG;

    expect($helper->getOutputContentAndClear())->toBe($msg);
});

it('nested calls forward arguments and status without inheriting outer arguments', function () {
    $helper = new TestHelper();
    $sc = new TestSwewCommander(['script.php', 'capture', 'outer'], $helper->getOutput(), false);
    $inherited = $sc->getCommand('capture');
    expect($inherited->getArgs())->toBe(['capture', 'outer']);
    expect($inherited->argv('value'))->toBe('outer');
    $command = $sc->getCommand('capture', ['inner']);
    expect($command->getArgs())->toBe(['capture', 'inner']);
    expect($command->call('capture', ['nested']))->toBe(7);
    expect($helper->getOutputContentAndClear())->toBe('nested');
    expect($command->call('capture'))->toBe(7);
    expect($helper->getOutputContentAndClear())->toBe('');
    $sc->run();
    expect($helper->getOutputContentAndClear())->toBe('outer');
});

it('positional arguments work around options and after the end-of-options marker', function () {
    $sc = new TestSwewCommander(['script.php'], TestHelper::createOutput(), false);
    $command = $sc->getCommand('capture', ['--quiet', '--count', '2', 'value']);
    expect($command->argv('value'))->toBe('value');
    expect($command->argv('count'))->toBe(2);
    $command = $sc->getCommand('capture', ['--', '--quiet']);
    expect($command->argv('value'))->toBe('--quiet');
    expect($command->argv('quiet'))->toBe(false);
    $command = $sc->getCommand('capture', ['capture']);
    expect($command->argv('value'))->toBe('capture');
    $helper = new TestHelper();
    (new TestSwewCommander(['script.php', 'capture', '--', '--help'], $helper->getOutput(), false))->run();
    expect($helper->getOutputContentAndClear())->toBe('--help');
});

it('unknown commands report one error without an exception trace', function () {
    $helper = new TestHelper();
    (new TestSwewCommander(['script.php', 'missing'], $helper->getOutput(), false))->run();
    expect($helper->getOutputContentAndClear())->toBe(" ERROR  Command 'missing' not found\n");
});

it('CommandArgument get all arguments', function () {
    $args = ['script.php', 'send:mail', 'user@mail.com', '--count', '1', 'test/file.spec.php'];
    [
        'SwewCommander' => $sc,
        'TestHelper' => $helper,
    ] =
        testFactory($args);
});
