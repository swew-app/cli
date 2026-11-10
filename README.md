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
composer require swew/cli:^2.0
```

## Development

This branch targets v2. Requires PHP 8.3+. For development, place `swew/test`
beside this checkout as `../test`, then run `composer install`. Composer uses the
source test runner so tests exercise this CLI, not the v1 CLI embedded in the PHAR.
The `main` branch is aliased to `2.0.x-dev` for local consumers.

Development commands:

- `composer test` — tests (or `composer test -- 'output\.spec\.php$'`).
- `composer static` — Mago static analysis.
- `composer lint` — correctness-focused lint checks.
- `composer code-check` — check formatting without changing files.
- `composer format` — apply formatting.
- `composer check` — all of the above checks, including tests.

Mago replaces Psalm, PHPStan, and Pint; Composer uses the pinned local binary.
Its first run downloads the platform binary, so it needs network access.
Style and class-size suggestions are advisory; use `vendor/bin/mago lint` to see them.
The analyzer checks `src`; tests are linted and executed with swew/test.
Test output omits the logo and per-test success messages.

## Example of use

### Create a command

```php
<?php

declare(strict_types=1);

namespace My\Cli\Commands;

use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;
use Swew\Cli\CommandCtx;

#[CliCommand(name: 'show:time', desc: 'Show the current time.')]
class ShowTimeCommand
{
    #[CliCtx]
    private CommandCtx $ctx;

    #[Argument(name: 'prefix', required: false)]
    public string $prefix = '';

    public function __invoke(): int
    {
        $this->ctx->output->writeLn($this->prefix . ' ' . date('H:i:s'));
        return 0;
    }
}
```

### Register and run commands

```php
<?php
// index.php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use My\Cli\Commands\ShowTimeCommand;
use Swew\Cli\SwewCommander;

class MySuperCli extends SwewCommander
{
    protected array $commands = [ShowTimeCommand::class];
}

(new MySuperCli($argv))->run();
```

```sh
php index.php show:time --prefix at
php index.php show:time --help
```

## Command API

Commands are ordinary classes with exactly one `#[CliCommand(name: ..., desc: ...)]`
and a public, parameterless `__invoke(): int`. No base class or interface is needed.
Return `0` for success or a nonzero exit code for failure. Constructors cannot
require arguments. Injection happens **after construction**, before invocation;
use injected properties in `__invoke()`, not in the constructor.

`#[Argument]` and `#[CliCtx]` work on public, protected, and private mutable instance
properties. Static and readonly properties cannot be injection targets.
Command declarations are validated at registration. Help reads metadata only:
it neither constructs commands nor validates required input.

### Arguments and options

```php
use Swew\Cli\Args\Argument;
use Swew\Cli\Command\ArgType;

// A required named option: --email user@example.com
#[Argument]
public string $email;

// Optional, inferred from the property default: --count 2 or -c=2
#[Argument(aliases: ['c'], desc: 'Number of messages')]
public int $count = 1;

// A flag: --quiet, --quiet=true, --quiet=false, --quiet=1, --quiet=0
#[Argument]
public bool $quiet = false;

// Optional nullable value; nullable alone does not initialize a property.
#[Argument]
public ?string $label = null;

// A positional argument
#[Argument(position: 0)]
public string $file;

// Repeated named option: --id=1 --id=2
#[Argument(name: 'id', itemType: ArgType::Int)]
public array $ids = [];

// A final positional array consumes all remaining positional values.
#[Argument(position: 1, itemType: ArgType::Str)]
public array $otherFiles = [];
```

- Names default to property names. Specify names and aliases **without dashes**.
- Scalar types come from PHP properties: `string`, `int`, `float`, `bool`,
  including nullable forms. Other types and unions are rejected.
- Arrays require `itemType: ArgType::Str|Int|Float|Bool`. Supplied values replace,
  rather than append to, the property default. Repeated scalar options use the last value.
- Without an initializer, an argument is required. Explicit `required: true`
  requires supplied input even with a default. `required: false` requires an
  initializer, including `= null` when appropriate.
- Positional indexes must be consecutive from zero; a positional array must be last.
  Positional arguments cannot have aliases.
- Invalid values and missing option values produce one error and exit code `1`,
  without invoking the command. Optional does not mean a supplied option may lack a value.
- Integer input must pass PHP's decimal integer validation; floats must be finite.
  Boolean values accept only `true`, `false`, `1`, and `0`.
- `--name=value`, `--name value`, aliases, negative numeric values, and the
  `--` end-of-options marker are supported. Use `--name=-text` for dash-prefixed strings.
- Undeclared options and extra positional values are not rejected. Raw input remains
  accessible through the context, preserving applications that share input across commands.
- `help`, `-h`, `-help`, and `--help` request help before `--`. Declared options
  take precedence (for example, a host alias `-h`); consumed option values do not trigger help.

### Runtime context

```php
$this->ctx->output->writeLn('Hello');
$this->ctx->arg('prefix')->getValue();
$this->ctx->argv('prefix'); // Same value; aliases also work.
$this->ctx->getArgs();     // Command name followed by its own raw arguments.
$this->ctx->getCommander();
$status = $this->ctx->call('show:time', ['--prefix', 'at']);
```

Context values are immutable snapshots of resolved input, including defaults.
Changing a command property does not change its context. Each call creates a fresh
command and context; nested calls share output but never inherit outer arguments.
`call()` accepts a registered command name or class name, returns its status, and
never exits the process. `run()` retains its existing process-exit behavior; pass
`stopByStatus: false` to the commander when embedding it.

### Migrating from v1

This is a breaking v2 API, with no legacy adapter.

| v1 | v2 |
|---|---|
| `extends Command`, `NAME`, `DESCRIPTION` | Plain class with `#[CliCommand]` |
| `{--count=1 (int)}` declarations | `#[Argument] public int $count = 1;` |
| `$this->output`, `arg()`, `argv()`, `call()`, `getArgs()`, `getCommander()` | Access through an injected `CommandCtx` |
| `self::SUCCESS`, `self::ERROR` | `0`, `1` (or your own constants) |
| Command `init()` | Move input-dependent setup into `__invoke()` |
| Command `getHelpMessage($template)` | `$commander->getCommandHelpMessage($nameOrClass, $template)` |
| `isValid()`, `getErrorMessage()`, argument setters | Input validation is performed before invocation |
| `fillCommandArguments()` | Removed; use `call()` to execute prepared commands |

Commander subclasses keep `init()`, `showHelp()`, and `getHelpMessage()`.
Their protected `getCommand($name, $args = [])` now returns a prepared
`Closure(): int`, not a `Command` object. It no longer implicitly inherits input.
Subclasses implementing pipelines can explicitly pass the protected `$argList`
to `call()`. Command help templates support `{name}`, `{desc}`, `{usage}`, and `{options}`.

---

# Writing Output

## write / writeLn / info / warn / error

![write demo](assets/write-img.avif)

```php
$this->ctx->output->write('Hello ');
$this->ctx->output->write('world', '<b>%s <br></>'); // formatting

$this->ctx->output->writeLn('Hello'); // write with new line
$this->ctx->output->writeLn('world', '<b>%s <br></>');

$this->ctx->output->info('Some good news');
$this->ctx->output->warn('A little attention');
$this->ctx->output->error('Something has gone wrong');

$this->ctx->output->clear(); // Reset terminal window
```

## Width / Height
```php
$this->ctx->output->width();
$this->ctx->output->height();
```

## Formatting

```php
$this->ctx->output->write('<b><black><bgRed>Hello world</>');

// OR
$format = '<b><black><bgRed>%s</>';
$this->ctx->output->write('Hello world', $format);
```

### Symbols

- `</>` - escape
- `<br>` - new line
- `<b>` - bold
- `<u>` - underline

### Colors

| Color| Background Color|
|---|---|
| `<black>`  | `<bgBlack>`
| `<gray>`   | `<bgGray>`
| `<white>`  | `<bgWhite>`
| `<red>`    | `<bgRed>`
| `<green>`  | `<bgGreen>`
| `<yellow>` | `<bgYellow>`
| `<blue>`   | `<bgBlue>`
| `<purple>` | `<bgPurple>`
| `<cyan>`   | `<bgCyan>`

### Cursors

- `<saveCursor>`
- `<restoreCursor>`
- `<eraseToEndLine>`
- `<eraseToStartLine>`
- `<eraseLine>`
- `<eraseToBottom>`
- `<eraseToTop>`

## Without formatting

Redirected output and `NO_COLOR` disable ANSI formatting automatically. Progress
animations and screen clearing are skipped when ANSI is disabled. You can override
this with `setAnsi(true)` or disable it explicitly:

```php
$this->ctx->output->setAnsi(false);
```

## Clear bash colors

Remove bash color symbols from string

```php
$this->ctx->output->clearColor($text); // no color string
```

## Empty lines
```php
// Write a single blank line...
$this->ctx->output->newLine(); 
// Write three blank lines...
$this->ctx->output->newLine(3);
```

## Link
```php
$this->ctx->output->getLink($url, $text);
```

# Prompting For Input

## ask
```php
public function __invoke(): int {
	$name = $this->ctx->output->ask('What is your name?');
	// ...
	return 0;
}
```

## askYesNo
```php
public function __invoke(): int {
	$name = $this->ctx->output->askYesNo('Is this the best CLI utility?', true);
	// ...
	return 0;
}
```
![askYesNo](assets/askYesNo.avif)


## secret
```php
public function __invoke(): int {
	$password = $this->ctx->output->secret('What is the password?');
	// ...
	return 0;
}
```

## Choice Question

|||
|---|---|
![choice-name](assets/choice-name.avif) | ![choice-name-result](assets/choice-name-result.avif)

```php
public function __invoke(): int {
	/** @var string */
	$answer = $this->ctx->output->choice(
		'What is your name?',
		['Leo', 'Mike', 'Don', 'Raph'],
		$isRequired = true // optional
	);

    $this->ctx->output->writeLn($answer);

	// ...
	return 0;
}
```

## Choice Multiple

![select-name](assets/select-name.avif)

```php
public function __invoke(): int {
	/** @var array */
	$answer = $this->ctx->output->select(
		'What is your name?',
		['Leo', 'Mike', 'Don', 'Raph'],
		$selectedIndex = [], // optional
		$isRequired = true,  // optional
		$isMultiple = true,  // optional
	);

	// ...
	return 0;
}
```

## Tables

![table](assets/table.avif)

```php
$title = ['Name', 'Age', 'Weapon'];

$list = [
	['Leo', 22, 'Swords'],
	['Mike', 21.6, 'Nunchaks'],
	['Don', 21.9, 'Bo'],
	['Raphael', 21.5, 'Saii'],
];

$this->ctx->output->table($title, $list);
```

## Progress bar

![Progress bar](assets/progress-bar.avif)

```php
$bar = $this->ctx->output->createProgressBar(count($users)); 

$bar->start(); // show progress bar

foreach ($users as $user) {
	$this->someTask($user);

	$bar->increment(); // progress
}

$bar->finish(); // remove progressbar
```


# Output

If you only need output to the console, you can use the `Output` class separately.

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Swew\Cli\Terminal\Output;

$output = new Output();

$output->writeLn('Hello world!');


```
