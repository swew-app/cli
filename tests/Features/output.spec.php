<?php

declare(strict_types=1);

use Swew\Cli\Testing\TestHelper;

it('Output ::write', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();

    $str = 'Hello world';
    $output->write($str);

    expect($helper->getOutputContentAndClear())->toBe($str);
});

it('Output ::writeLn', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput();

    $str = 'Hello world 2';
    $output->write($str);

    expect($helper->getOutputContentAndClear())->toBe($str);
});
