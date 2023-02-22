<?php

declare(strict_types=1);

use Swew\Cli\Lib\Output;

it('Run', function () {
    $output = new Output();

    // for ($i = 0; $i <= 255; $i++) {
    //     $output->writeLn($i, "\e[38;5;${i}m ${i} </>");
    // }

    // $output->writeLn('New Ln');
    $output->info('Hi info');
    $output->warn('Hi warning');
    $output->error('Hi error');
    $output->newLine();
    $output->writeLn('<s> highlight </>');
});
