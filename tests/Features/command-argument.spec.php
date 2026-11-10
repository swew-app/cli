<?php

declare(strict_types=1);

use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;
use Swew\Cli\Command\ArgumentDefinition;
use Swew\Cli\Command\ArgType;
use Swew\Cli\Command\CommandDefinition;
use Swew\Cli\CommandCtx;
use Swew\Cli\SwewCommander;
use Swew\Cli\Testing\TestHelper;

it('invalid argument declarations fail during registration', function () {
    $properties = new class {
        #[Argument(required: false)]
        public string $missingDefault;

        #[Argument]
        public string|int $union;

        #[Argument]
        public static string $static;

        #[Argument]
        public readonly int $readonly;

        #[Argument]
        public array $missingItemType = [];

        #[Argument(itemType: ArgType::Int)]
        public array $wrongDefault = ['1'];

        #[Argument]
        public $untyped;

        #[Argument(position: -1)]
        public string $negative;

        #[Argument(name: '--name')]
        public string $invalidName;
    };
    foreach ((new ReflectionClass($properties))->getProperties() as $property) {
        $attribute = $property->getAttributes(Argument::class)[0]->newInstance();
        expect(fn() => new ArgumentDefinition($property, $attribute))->toThrow(LogicException::class);
    }
});

it('invalid command declarations and duplicate command names are rejected', function () {
    $classes = [
        new class {
            public function __invoke(): int
            {
                return 0;
            }
        },
        new
            #[CliCommand('bad-invoke')]
            class {
                public function __invoke(): void
                {
                }
            },
        new
            #[CliCommand('bad-alias')]
            class {
                #[Argument(aliases: ['n'])]
                public int $count = 0;
                #[Argument]
                public string $n = '';

                public function __invoke(): int
                {
                    return 0;
                }
            },
        new
            #[CliCommand('bad-context')]
            class {
                #[CliCtx]
                private string $ctx;

                public function __invoke(): int
                {
                    return 0;
                }
            },
        new
            #[CliCommand('bad-position')]
            class {
                #[Argument(position: 1)]
                public string $value;

                public function __invoke(): int
                {
                    return 0;
                }
            },
    ];
    foreach ($classes as $class) {
        expect(fn() => new CommandDefinition($class::class))->toThrow(LogicException::class);
    }

    $valid = new
        #[CliCommand('duplicate')]
        class {
            public function __invoke(): int
            {
                return 0;
            }
        };
    $cli = new class(['script.php'], TestHelper::createOutput(), false) extends SwewCommander {
        public function register(array $classes): void
        {
            $this->setCommands($classes);
        }
    };
    expect(fn() => $cli->register([$valid::class, $valid::class]))->toThrow(LogicException::class);
});

it('private properties, required overrides and variadic positionals are supported', function () {
    $fixture = new
        #[CliCommand('files')]
        class {
            #[CliCtx]
            private CommandCtx $ctx;

            #[Argument(required: true)]
            private string $prefix = 'default';

            #[Argument(position: 0, itemType: ArgType::Str)]
            private array $files = [];

            public function __invoke(): int
            {
                $this->ctx->output->write(json_encode([$this->prefix, $this->files], JSON_THROW_ON_ERROR));
                return 0;
            }
        };
    $helper = new TestHelper();
    $cli = new class(['script.php'], $helper->getOutput(), false) extends SwewCommander {
        public function register(string $class): void
        {
            $this->setCommands([$class]);
        }
    };
    $cli->register($fixture::class);
    expect($cli->call('files'))->toBe(1);
    $helper->clearOutput();
    expect($cli->call('files', ['a', '--prefix=', 'b', '--', '-c']))->toBe(0);
    expect($helper->getOutputContentAndClear())->toBe('["",["a","b","-c"]]');
});
