<?php

declare(strict_types=1);

use Swew\Cli\Lib\Output;

it('Run', function () {
    $output = new Output();

    // for ($i = 0; $i <= 255; $i++) {
    //     $output->writeLn($i, "\e[38;5;${i}m ${i} </>");
    // }

    // $output->setAnsi(false);

    // $output->writeLn('New Ln');
    // $output->info('Hi info');
    // $output->warn('Hi warning');
    // $output->error('Hi error');
    // $output->newLine();
    // $output->writeLn('<s> highlight </>');

    //*
    $answer = $output->ask('How are you?');

    $output->writeLn($answer, '<red>%s</>');
    // */

    /*
    $output->table(
        ['Name', 'Age', 'Weapon'],
        [
            ['Leo', 22, 'Swords'],
            ['Mike', 21.6, 'Nunchaks'],
            ['Don', 21.9, 'Bo'],
            ['Raphael', 21.5, 'Saii'],
        ],
    );
    // */

    /*
    $total = 159;
    $bar = $output->createProgressBar($total);

    $bar->start();
    for ($i = 0; $i <= $total; $i++) {
        $bar->increment();
        usleep(10000);
    }
    $bar->finish();
    // */
});
