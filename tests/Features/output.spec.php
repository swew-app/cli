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
    $output->writeLn($str);

    expect($helper->getOutputContentAndClear())->toBe($str . "\n");
});

it('progress reports intermediate percentages and handles empty totals', function () {
    $helper = new TestHelper();
    $output = $helper->getOutput(true);
    $bar = $output->createProgressBar(4);
    $bar->start();
    $bar->increment();
    expect(str_contains($helper->getOutputContentAndClear(), '25%'))->toBe(true);
    $bar = $output->createProgressBar(0);
    $bar->increment();
    expect(str_contains($helper->getOutputContentAndClear(), '100%'))->toBe(true);
    $bar->finish();
    expect($helper->getOutputContentAndClear())->toBe("\r\e[2K");
    $output->setAnsi(false);
    $bar->increment();
    $bar->finish();
    $output->clear();
    expect($helper->getOutputContentAndClear())->toBe('');
    expect($output->getLink('https://example.com', 'Example'))->toBe('Example');
    expect($output->ask('Name?', 'default'))->toBe('default');
    expect($output->secret('Secret?', 'default'))->toBe('default');
    expect($output->select('Empty?', []))->toBe([]);
    expect($output->width())->toBe(80);
    expect($output->height())->toBe(24);
});

it('clearing colors removes resets but preserves ordinary bracketed text', function () {
    expect(\Swew\Cli\Terminal\Output::clearColor("\e[31mRed\e[m [31m"))->toBe('Red [31m');
});

it('output does not close streams supplied by its caller', function () {
    $input = fopen('php://memory', 'r+');
    $stream = fopen('php://memory', 'w+');
    $output = new \Swew\Cli\Terminal\Output($input, $stream, false);
    unset($output);
    expect(is_resource($input))->toBe(true);
    expect(is_resource($stream))->toBe(true);
    fclose($input);
    fclose($stream);
});
