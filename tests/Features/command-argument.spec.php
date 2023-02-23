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

it('CommandArgument getValue parse 1', function () {
    $str = '--count|-C=3 (int) : Count of mails: 3';
    $arg = new CommandArgument($str);

    // $arg->parseInput(' -C 2');

    expect($arg->getValue())->toBe(2);
})->todo();

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
