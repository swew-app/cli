<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Swew\Cli\Lib\Output;

$output = new Output();
// $answer = $output->ask('How are you?');
$answer = $output->secret('What You password?');

$output->writeLn($answer, '<red>%s</>');
