<?php

declare(strict_types=1);

require_once('stubs/Commands/SendMailCommand.php');

use Swew\Cli\SwewCommander;
use Swew\Cli\Testing\TestHelper;
use TestSwew\Commands\SendMailCommand;

class TestSwewCommander extends SwewCommander
{
    public function __call(string $name, array $args): mixed
    {
        return $this->$name(...$args);
    }
}

//

it('SwewCommander :setCommands :getCommand', function () {
    $args = ['send:mail', 'user@mail.com', '--count', '1', '-silent'];
    $helper = new TestHelper();
    $sc = new TestSwewCommander($args, $helper->getOutput());

    $sc->setCommands([
        SendMailCommand::class,
    ]);

    // expect(fn() => $sc->getCommand('send:mail', false))->not()->toThrow("Get error for command 'send:mail': <b>-tax</> - is required");

    $command = $sc->getCommand('send:mail', false);
    $command();

    expect($helper->getOutputContentAndClear())->toBe('Email sended');
});

it('SwewCommander :setCommands :getCommand - with error', function () {
    $args = ['send:mail', 'user@mail.com', '--count', '1'];
    $helper = new TestHelper();
    $sc = new TestSwewCommander($args, $helper->getOutput());

    $sc->setCommands([
        SendMailCommand::class,
    ]);

    expect(fn () => $sc->getCommand('send:mail', false))->not()->toThrow("Get error for command 'send:mail': <b>-tax</> - is required");
});


it('SwewCommander :isNeedHelp 1', function () {
    //
})->todo();
it('SwewCommander :showHelp 1', function () {
    //
})->todo();
it('SwewCommander :getHelpMessage 1', function () {
    //
})->todo();
