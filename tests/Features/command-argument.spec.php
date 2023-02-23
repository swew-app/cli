<?php

declare(strict_types=1);

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Command\ArgType;

//*
it('CommandArgument isArgument=true', function () {
    $str = '--count|-C=1 (int) : Count of mails 1';
    $arg = new CommandArgument($str);

    expect($arg->isArgument())->toBe(true);
});

it('CommandArgument isArgument=false', function () {
    $str = 'count (int) : Count of mails 2';
    $arg = new CommandArgument($str);

    expect($arg->isArgument())->toBe(false);
});

it('CommandArgument getDescription 1', function () {
    $str = '--count|-C=1 (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    expect($arg->getDescription())->toBe('Count of mails: 3');
});

it('CommandArgument getDescription 2', function () {
    $str = '--count|-C=1 (int)';
    $arg = new CommandArgument($str);

    expect($arg->getDescription())->toBe('');
});
// */
//*
it('CommandArgument getType 1', function () {
    $str = '--count|-C=1 (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    expect($arg->getType())->toBe(ArgType::Int);
});

it('CommandArgument getType 2', function () {
    $str = '--count';
    $arg = new CommandArgument($str);

    expect($arg->getType())->toBe(ArgType::Str);
});
// */

//*
it('CommandArgument getValue 1', function () {
    $str = '--count|-C= (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    expect($arg->getValue())->toBe(0);
});

it('CommandArgument getValue 2', function () {
    $str = '--count|-C (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    expect($arg->getValue())->toBe(0);
});

it('CommandArgument getValue 3', function () {
    $str = '--count|-C=3 (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    expect($arg->getValue())->toBe(3);
});
//*/

//*
it('CommandArgument isRequired 1', function () {
    $str = '--count|-C= (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    expect($arg->isRequired())->toBe(false);
});

it('CommandArgument isRequired 2', function () {
    $str = '--count';
    $arg = new CommandArgument($str);

    expect($arg->isRequired())->toBe(true);
});
// */

//*
it('CommandArgument is 1', function () {
    $str = '--count|-C=123 (int) : Description';
    $arg = new CommandArgument($str);

    expect($arg->is('count'))->toBe(true);
    expect($arg->is('C'))->toBe(true);
    expect($arg->is('other'))->toBe(false);
});

it('CommandArgument is 2', function () {
    $str = '--count|-C (int):Description';
    $arg = new CommandArgument($str);

    expect($arg->is('count'))->toBe(true);
    expect($arg->is('C'))->toBe(true);
    expect($arg->is('other'))->toBe(false);
});

it('CommandArgument is 3', function () {
    $str = '--count|-C';
    $arg = new CommandArgument($str);

    expect($arg->is('count'))->toBe(true);
    expect($arg->is('C'))->toBe(true);
    expect($arg->is('other'))->toBe(false);
});

it('CommandArgument is 4', function () {
    $str = '--count|-C : Desc';
    $arg = new CommandArgument($str);

    expect($arg->is('count'))->toBe(true);
    expect($arg->is('C'))->toBe(true);
    expect($arg->is('other'))->toBe(false);
});
// */

//*
it('CommandArgument isArray 1', function () {
    $str = '--id=[] : Desc';
    $arg = new CommandArgument($str);

    expect($arg->isArray())->toBe(true);
});

it('CommandArgument isArray 2', function () {
    $str = '--id=';
    $arg = new CommandArgument($str);

    expect($arg->isArray())->toBe(false);
});

it('CommandArgument isArray 3', function () {
    $str = '--id ';
    $arg = new CommandArgument($str);

    expect($arg->isArray())->toBe(false);
});
// */

//*
it('CommandArgument parseInput 1.1', function () {
    $str = 'id';
    $arg = new CommandArgument($str);

    $arg->parseInput(['123']);

    expect($arg->getValue())->toBe('123');
});

it('CommandArgument parseInput 1.2', function () {
    $str = '--id';
    $arg = new CommandArgument($str);

    $arg->parseInput(['some text', '--id', '123', '-c=2']);

    expect($arg->getValue())->toBe('123');
});

it('CommandArgument parseInput 2.1', function () {
    $str = 'id=[]';
    $arg = new CommandArgument($str);

    $arg->parseInput(['456']);

    expect($arg->getValue())->toBe(['456']);
});

it('CommandArgument parseInput 2.2', function () {
    $str = '-id=[]';
    $arg = new CommandArgument($str);

    $arg->parseInput(['-id', '456']);

    expect($arg->getValue())->toBe(['456']);
});

it('CommandArgument parseInput 3.1', function () {
    $str = '-s (bool)';
    $arg = new CommandArgument($str);

    $arg->parseInput(['--id', '123', '-s']);

    expect($arg->getValue())->toBe(true);
});

it('CommandArgument parseInput 3.2', function () {
    $str = '-s (bool)';
    $arg = new CommandArgument($str);

    $arg->parseInput(['--id', '123']);

    expect($arg->getValue())->toBe(false);
});

it('CommandArgument parseInput 4.1', function () {
    $str = '-ls=[] (bool)';
    $arg = new CommandArgument($str);

    $arg->parseInput(['--ls']);

    expect($arg->getValue())->toBe([true]);
});
// */
