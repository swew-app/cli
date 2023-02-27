# SWEW/CLI

A PHP CLI library that solves the fatal flaw of all others.

---

# Packages developed by SWEW

> - [swew/cli](https://packagist.org/packages/swew/cli) - A command-line interface program with formatting and text entry functions.
> - [swew/test](https://packagist.org/packages/swew/test) - A test framework that is designed to fix the fatal flaw of other test frameworks.
> - [swew/db](https://packagist.org/packages/swew/db) - A lightweight, fast, and secure PHP library for interacting with databases, creating migrations, and running queries.
> - [swew/dd](https://packagist.org/packages/swew/dd) - The simplest way to debug variables. As in Laravel.

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
