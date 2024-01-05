<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Swew\Cli\Command\CommandArgument;
use Swew\Cli\Terminal\Output;

$arg = new CommandArgument('send:mail {mail} {-name=} {-id=}');

// $arg->parseInput(array_slice($argv, 1));


$output = new Output();
$output->writeLn("\n\n");


/*
$bar = $output->createProgressBar(5);
$bar->start(); // show progress bar

foreach ([1,2] as $user) {
    sleep(1);
    $bar->increment(); // progress
}

$bar->finish(); // remove progressbar
$output->writeLn("\n\n");
//*/


// $link = $output->getLink('https://swew.app', 'SWEW Site');
// $output->writeLn($link);



/*
for ($i = 0; $i <= 255; $i++) {
    if ($i > 0 && $i % 8 === 0) {
        $output->newLine();
    }
    $title = str_pad("  $i", 7, ' ');
    $output->write($i, "\e[48;5;${i}m${title}</> ");
}
$output->writeLn("\n");
// */

$list = [
    'Leo',
    'Mike',
    'Raphael',
    'Donatello',
    'Joni',
    'Splinter',
    'April',
    'Shredder',
    'Bi Bop',
    'RockSteady',
    'Ninja',
    'Web',
    'Samurai',
];

// $answer = $output->askYesNo('is a good day?', true);

// $answer = $output->select('Select value', $list);
// $answer = $output->choice('Select value', $list);

// $output->info('PASS');

// $answer = $output->ask('How are you?');
// $answer = $output->secret('What You password?');

// $output->writeLn($answer, '<red>%s</>');


// $output->setAnsi(false);

// $output->writeLn('New Ln');
// $output->info('Hi info');
// $output->warn('Hi warning');
// $output->error('Hi error');
// $output->newLine();
// $output->writeLn('<s> highlight </>');

/*
$answer = $output->ask('How are you?');
$output->writeLn($answer, '<red>%s</>');
// */

// /*
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
$total = 259;
$bar = $output->createProgressBar($total);
$bar->start();
for ($i = 0; $i <= $total; $i++) {
$bar->increment();
usleep(10000);
}
$bar->finish();
// */
