# SWEW/CLI

A PHP CLI library that solves the fatal flaw of all others.

---

## Install

```sh
composer require swew/cli
```

## Example of use

```php
<?php

namespace My\Cli;

use Swew\Cli\SwewCommander;
use My\Cli\Commands\ShowTimeCommand;

class MySuperCli extends SwewCommander
{
    protected array $commands = [
        ShowTimeCommand::class,
    ];
}
```

```php
<?php

namespace My\Cli\Commands;

use Swew\Cli\Command;

class ShowTimeCommand extends Command {
    const NAME = 'show:time {prefix}';

    const DESCRIPTION = 'The function shows the current time.';

    public function __invoke(): int
    {
        $prefix = $this->arg('prefix')->getValue();

        $time = $prefix . ' ' . date();

        $this->output->writeLn($time);

        return self::SUCCESS;
    }
}
```
```php
<?php
// index.php

require __DIR__.'/vendor/autoload.php';

use My\Cli\MySuperCli;

(new MySuperCli())->run();
```
```sh
php index.php 'Hello at'
```
