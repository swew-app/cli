<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/stubs/Commands/SendMailCommand.php';

use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;
use Swew\Cli\Command\ArgType;
use Swew\Cli\CommandCtx;
use Swew\Cli\SwewCommander;
use Swew\Cli\Testing\TestHelper;

class TestSwewCommander extends SwewCommander
{
    protected array $commands = [SendMailCommand::class, CaptureCommand::class, HelpOnlyCommand::class];
}

#[CliCommand(name: 'capture', desc: 'Capture arguments')]
class CaptureCommand
{
    public static ?self $last = null;

    #[CliCtx]
    private CommandCtx $ctx;

    #[Argument]
    public bool $quiet = false;

    #[Argument(position: 0)]
    public string $value = '';

    #[Argument(aliases: ['c'])]
    public int $count = 0;

    #[Argument]
    public ?string $label = null;

    #[Argument]
    public float $ratio = 0.5;

    #[Argument(itemType: ArgType::Int)]
    public array $ids = [9];

    public function context(): CommandCtx
    {
        return $this->ctx;
    }

    public function __invoke(): int
    {
        self::$last = $this;
        $this->ctx->output->write($this->value);
        return 7;
    }
}

#[CliCommand(name: 'help-only')]
class HelpOnlyCommand
{
    #[Argument]
    private string $token;

    public function __construct()
    {
        throw new RuntimeException('Help must not construct commands');
    }

    public function __invoke(): int
    {
        return 0;
    }
}

it('plain commands accept named arguments, aliases and positionals', function () {
    expect((new ReflectionClass(SwewCommander::class))->getFileName())
        ->toBe(dirname(__DIR__, 2) . '/src/SwewCommander.php');
    $helper = new TestHelper();
    $cli = new TestSwewCommander(['script.php'], $helper->getOutput(), false);
    expect($cli->call('send:mail', ['--count', '1', 'user@mail.com', '-S']))->toBe(0);
    expect($helper->getOutputContentAndClear())->toBe('Email sended');
    expect($cli->call(SendMailCommand::class, ['user@mail.com', '--silent=false']))->toBe(0);
});

it('missing required input reports one error before invocation', function () {
    $helper = new TestHelper();
    $cli = new TestSwewCommander(['script.php'], $helper->getOutput(), false);
    expect($cli->call('send:mail', ['user@mail.com']))->toBe(1);
    expect($helper->getOutputContentAndClear())->toBe(" ERROR  Argument 'silent' is required\n");
});

it('typed values and defaults agree between properties and context', function () {
    $cli = new TestSwewCommander(['script.php'], TestHelper::createOutput(), false);
    $args = ['--quiet=false', '-c', '-12', '--ratio=1.25', '--ids=1', '--ids', '2', 'value'];
    expect($cli->call('capture', $args))->toBe(7);
    $command = CaptureCommand::$last;
    $ctx = $command->context();
    expect([$command->quiet, $command->count, $command->ratio, $command->ids, $command->label])->toBe([
        false,
        -12,
        1.25,
        [1, 2],
        null,
    ]);
    expect($ctx->argv('count'))->toBe($command->count);
    expect($ctx->arg('c'))->toBe($ctx->arg('count'));
    expect($ctx->argv('ids'))->toBe($command->ids);
    expect($ctx->argv('label'))->toBe(null);
    expect($ctx->getArgs())->toBe(['capture', ...$args]);
    expect($ctx->getCommander())->toBe($cli);
    $command->count = 99;
    expect($ctx->argv('count'))->toBe(-12);
    expect(fn() => $ctx->arg('missing'))->toThrow(LogicException::class);
});

it('nested and repeated calls use fresh commands and do not inherit arguments', function () {
    $helper = new TestHelper();
    $cli = new TestSwewCommander(['script.php', 'capture', 'outer'], $helper->getOutput(), false);
    $cli->run();
    $outer = CaptureCommand::$last;
    expect($helper->getOutputContentAndClear())->toBe('outer');
    expect($outer->context()->call('capture', ['inner', '--ids=3']))->toBe(7);
    expect($helper->getOutputContentAndClear())->toBe('inner');
    expect(CaptureCommand::$last === $outer)->toBe(false);
    expect($outer->value)->toBe('outer');
    expect($outer->context()->call('capture'))->toBe(7);
    expect($helper->getOutputContentAndClear())->toBe('');
    expect(CaptureCommand::$last->ids)->toBe([9]);
    expect(CaptureCommand::$last->context()->argv('ids'))->toBe([9]);
});

it('invalid supplied values never fall back to defaults or invoke the command', function (
    array $args,
    string $message,
) {
    $helper = new TestHelper();
    $cli = new TestSwewCommander(['script.php'], $helper->getOutput(), false);
    CaptureCommand::$last = null;
    expect($cli->call('capture', $args))->toBe(1);
    expect($helper->getOutputContentAndClear())->toBe(" ERROR  $message\n");
    expect(CaptureCommand::$last)->toBe(null);
})->with([
    [['--count=abc'], "Argument 'count' expects int"],
    [['--count=1.2'], "Argument 'count' expects int"],
    [['--ratio=1e999'], "Argument 'ratio' expects float"],
    [['--quiet=maybe'], "Argument 'quiet' expects bool"],
    [['--ids=bad'], "Argument 'ids' expects int"],
    [['--count'], "Option 'count' needs a value"],
    [['--label', '--quiet'], "Option 'label' needs a value"],
    [['--label', '--'], "Option 'label' needs a value"],
]);

it('help uses metadata without constructing or validating command input', function () {
    $helper = new TestHelper();
    (new TestSwewCommander(['script.php', 'help-only', '--help'], $helper->getOutput(), false))->run();
    expect($helper->getOutputContentAndClear())->toContain('--token');
    $cli = new TestSwewCommander(['script.php'], $helper->getOutput(), false);
    $cli->setHelpPrefix('Demo');
    $cli->run();
    expect($helper->getOutputContentAndClear())
        ->toBe(
            "Demo\nAvailable commands:\n send:mail: Command to send email\n capture: Capture arguments\n help-only: \n",
        );
    $help = $cli->getCommandHelpMessage(SendMailCommand::class);
    expect($help)->toContain('send:mail <email> [options]');
    expect($help)->toContain('--silent, -S');
});

it('help-looking values, negative numbers and the end-of-options marker are preserved', function () {
    $helper = new TestHelper();
    (new TestSwewCommander(
        ['script.php', 'capture', '--label', 'help', '--quiet', '-12'],
        $helper->getOutput(),
        false,
    ))->run();
    expect($helper->getOutputContentAndClear())->toBe('-12');
    expect(CaptureCommand::$last->label)->toBe('help');
    expect(CaptureCommand::$last->quiet)->toBe(true);
    (new TestSwewCommander(['script.php', 'capture', '--', '--help'], $helper->getOutput(), false))->run();
    expect($helper->getOutputContentAndClear())->toBe('--help');
    expect(CaptureCommand::$last->quiet)->toBe(false);
});

it('unknown commands report one error without a trace', function () {
    $helper = new TestHelper();
    (new TestSwewCommander(['script.php', 'missing'], $helper->getOutput(), false))->run();
    expect($helper->getOutputContentAndClear())->toBe(" ERROR  Command 'missing' not found\n");
});
