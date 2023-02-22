<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Swew\Cli\Lib\Output;

$output = new Output();

$output->info('PASS');

// for ($i = 0; $i <= 255; $i++) {
//     $output->write($i, "\t\e[48;5;${i}m${i}</>");

//     if ($i > 0 && $i % 8 === 0) {
//         $output->newLine();
//     }
// }

// $answer = $output->ask('How are you?');
// $answer = $output->secret('What You password?');

// $output->writeLn($answer, '<red>%s</>');
