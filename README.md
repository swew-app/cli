# SWEW/CLI

The package is a PHP library for building console applications and commands in an object-oriented manner. It provides a simple and intuitive API for defining commands and handling arguments and options. It also includes utilities for interacting with the console, such as coloring output and prompting for user input. With this package, you can quickly create powerful command-line tools and automate repetitive tasks in your development workflow.

---

# Packages developed by SWEW

> - [swew/cli](https://packagist.org/packages/swew/cli) - A command-line interface program with formatting and text entry functions.
> - [swew/test](https://packagist.org/packages/swew/test) - A test framework that is designed to fix the fatal flaw of other test frameworks.
> - [swew/db](https://packagist.org/packages/swew/db) - A lightweight, fast, and secure PHP library for interacting with databases, creating migrations, and running queries.
> - [swew/dd](https://packagist.org/packages/swew/dd) - The simplest way to debug variables. As in Laravel.

---


# Install

```sh
composer require swew/cli
```

## Example of use


### Create command
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

### Create cli-program

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

### Run cli-program
```php
<?php
// index.php

require __DIR__.'/vendor/autoload.php';

use My\Cli\MySuperCli;

(new MySuperCli())->run();
```
```sh
php index.php 'show:time at'
```

# API / DOC

# Console — User Commands

> An example of creating a command with SWEW-CLI.

The command must be inherited from the `Swew\Cli\Command` class and the class must be callable via the `__invoke` method.

The class must also have two obligatory constants.
- `NAME` - necessary to call the command, it can also contain arguments.
- The `DESCRIPTION` is a description of the command and is used to call the `help` message.

As a result of the execution of the command should return the result - the status code. If all was successful, it is `0`.

Translated with www.DeepL.com/Translator (free version)

```php
class SendEmail extends Command
{
    const NAME = 'send:email';

    const DESCRIPTION = 'Command to send email';

    public function __invoke(): int
    {
        // ...
        return self::SUCCESS;
    }
}
```

By inheriting from the class `Command` - we also get auxiliary methods defined in it.

- `init(): void` - will be run before execution (can be overridden)
- `arg(string $key): `CommandArgument` - getting the argument passed to the command
- `argv(string $key): mixed` - get value
- `getHelpMessage(): string` - get help message for the command (can be overridden)
- `isValid(): bool` - check if all required arguments are passed to the command correctly
- `getErrorMessage():string` - Get an error message
- `call(string $command, ?array $args:) int` - TODO: call another command
- `callSilent(string $command, ?array $args): int` - TODO: call another command without outputting messages from it

# Working with command parameters, description of arguments

```php
// Only command name, no arguments
const NAME = 'send:email';

// One required argument userId, the argument is always wrapped in curly braces
const NAME = 'send:email {--userId}';

// Optional argument userId is empty string
const NAME = 'send:email {--userId=}';
// Optional argument with default value is string
const NAME = 'send:email {--userId=1}';
// Optional argument, userId as array
const NAME = 'send:email {--userId=[]}';

/** Typing */
// Argument with typing
const NAME = 'send:email {--userId (int)}';

// Argument with typing, default userId === 0
const NAME = 'send:email {--userId= (int)}';

// Argument with typing, default userId === ''
const NAME = 'send:email {--userId= (str)}';

// Argument with typing, default userId === false
const NAME = 'send:email {--userId= (bool)}';

// Argument with typing, default:     userId === false
// if command: `send:email`           userId === true
// if command: `send:email --userId`  userId === true
const NAME = 'send:email {--userId= (bool)}';

// Alias for argument
const NAME = 'send:email {-S|--silent}';

/** Description */
const NAME = 'send:email {mail : The description, will be reflected in help}';

/** Example command with one argument in the full description */
const NAME = 'send:email {--count|-C=1          (int) : Count of mails}';
          // command | argument|alias|default | type | description
```

# Prompting For Input

## ask
```php
public function __invoke(): int {
	$name = $this->output->ask('What is your name?'); 
	// ...
	return self::SUCCESS;
}
```

## secret
```php
public function __invoke(): int {
	$password = $this->output->secret('What is the password?');
	// ...
	return self::SUCCESS;
}
```

## Choice Question
```php
public function __invoke(): int {
	/** @var string */
	$answer = $this->output->choice(
		'What is your name?',
		['Leo', 'Mike', 'Don', 'Raph'],
		$isRequired = true // optional
	);

	// ...
	return self::SUCCESS;
}
```

## Choice Multiple
```php
public function __invoke(): int {
	/** @var array */
	$answer = $this->output->select(
		'What is your name?',
		['Leo', 'Mike', 'Don', 'Raph'],
		$selectedIndex = [], // optional
		$isRequired = true,  // optional
		$isMultiple = true,  // optional
	);

	// ...
	return self::SUCCESS;
}
```

## Tables
```php
$title = ['Name', 'Age', 'Weapon'];

$list = [
	['Leo', 22, 'Swords'],
	['Mike', 21.6, 'Nunchaks'],
	['Don', 21.9, 'Bo'],
	['Raphael', 21.5, 'Saii'],
];

$this->output->table($title, $list);
```

## Progress bar
```php
$bar = $this->output->createProgressBar(count($users)); 

$bar->start(); 

foreach ($users as $user) {
	$this->someTask($user);

	$bar->increment();
}

$bar->finish();
```


# Writing Output

## write / writeLn / info / warn / error
```php
$this->output->write('Hello');

$this->output->writeLn('world'); // write with new line

$this->output->info('Some good news');

$this->output->warn('A little attention');

$this->output->error('Something has gone wrong');

$this->output->clear(); // Reset terminal window
```

## Formatting
```php
$this->output->write('<b><black><bgRed>Hello world</>');

// OR
$format = '<b><black><bgRed>%s</>';
$this->output->write('Hello world', $format);

```

### Symbols

- `</>` - escape
- `<b>` - bold
- `<u>` - underline
- `<href=https://github.com/swew-app>` - link

### Colors

- `<black>`
- `<white>`
- `<red>`
- `<green>`
- `<yellow>`
- `<blue>`
- `<purple>`
- `<cyan>`

## Empty lines
```php
// Write a single blank line...
$this->output->newLine(); 
// Write three blank lines...
$this->output->newLine(3);
```
