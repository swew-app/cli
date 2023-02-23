<?php

declare(strict_types=1);

use Swew\Cli\Command\CommandParser;

it('CommandParser::parseName', function () {
    $raw = 'send:email {userID} {--type=}';

    $name = (new CommandParser())->parseName($raw);

    expect($name)->toBe('send:email');
})->skip();

it('CommandParser::getRequiredArguments', function () {
    $raw = 'send:email {userName} {--count=1} {-S|--silent=true} {--id=[]}';

    $arr = (new CommandParser())->getRequiredArguments($raw);

    expect($arr)->toBe([
        'userName' => '',
        '--count' => '1',
        '-S|--silent' => '',
        '--id' => [],
    ]);
})->skip();

it('CommandParser::parseArgumentKeyVal', function (string $str, array $expected) {
    $res = (new CommandParser())->parseArgumentKeyVal($str);

    expect($res)->toBe($expected);
})->with([
        ['userName', ['userName', '']],
        ['--count=1', ['count', '1']],
        ['--id=[]', ['id', []]],
    ])->skip();
