<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

use Closure;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;
use Swew\Cli\CommandCtx;
use Swew\Cli\SwewCommander;
use Swew\Cli\Terminal\Output;

/** @internal */
final class CommandDefinition
{
    public readonly CliCommand $attribute;

    /** @var ReflectionClass<object> */
    private readonly ReflectionClass $class;

    /** @var array<string, ArgumentDefinition> */
    private array $arguments = [];

    /** @var array<string, ArgumentDefinition> */
    private array $options = [];

    /** @var array<int, ArgumentDefinition> */
    private array $positionals = [];

    /** @var list<ReflectionProperty> */
    private array $contexts = [];

    /** @param class-string $className */
    public function __construct(
        public readonly string $className,
    ) {
        $this->class = new ReflectionClass($className);
        $attributes = $this->class->getAttributes(CliCommand::class);
        if (count($attributes) !== 1) {
            throw new LogicException("Command '$className' needs exactly one #[CliCommand]");
        }
        $this->attribute = $attributes[0]->newInstance();
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9:_.-]*$/D', $this->attribute->name) !== 1) {
            throw new LogicException("Invalid command name '{$this->attribute->name}'");
        }
        if (
            !$this->class->isInstantiable()
            || ($this->class->getConstructor()?->getNumberOfRequiredParameters() ?? 0) > 0
        ) {
            throw new LogicException("Command '$className' must be constructible without arguments");
        }
        if (!$this->class->hasMethod('__invoke')) {
            throw new LogicException("Command '$className' needs public __invoke(): int");
        }
        $invoke = $this->class->getMethod('__invoke');
        $returnType = $invoke->getReturnType();
        if (
            !$invoke->isPublic()
            || $invoke->isStatic()
            || $invoke->getNumberOfParameters() !== 0
            || !$returnType instanceof ReflectionNamedType
            || $returnType->getName() !== 'int'
            || $returnType->allowsNull()
        ) {
            throw new LogicException("Command '$className' needs public __invoke(): int");
        }

        $class = $this->class;
        do {
            foreach ($class->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() === $class->getName()) {
                    $this->addProperty($property);
                }
            }
            $class = $class->getParentClass();
        } while ($class !== false);

        ksort($this->positionals);
        $position = 0;
        foreach ($this->positionals as $index => $argument) {
            if ($index !== $position++ || $argument->isArray && $position !== count($this->positionals)) {
                throw new LogicException('Positions must be consecutive from zero; an array must be last');
            }
        }
    }

    private function addProperty(ReflectionProperty $property): void
    {
        $contexts = $property->getAttributes(CliCtx::class);
        $arguments = $property->getAttributes(Argument::class);
        if ($contexts !== []) {
            $type = $property->getType();
            if (
                count($contexts) !== 1
                || $arguments !== []
                || !$type instanceof ReflectionNamedType
                || $type->getName() !== CommandCtx::class
                || $property->isStatic()
                || $property->isReadOnly()
            ) {
                throw new LogicException('#[CliCtx] needs a mutable CommandCtx instance property without #[Argument]');
            }
            $contexts[0]->newInstance();
            $this->contexts[] = $property;
        }
        if ($arguments === []) {
            return;
        }
        if (count($arguments) !== 1) {
            throw new LogicException('A property can have only one #[Argument]');
        }
        $argument = new ArgumentDefinition($property, $arguments[0]->newInstance());
        if (isset($this->arguments[$argument->name])) {
            throw new LogicException("Duplicate argument '{$argument->name}'");
        }
        $this->arguments[$argument->name] = $argument;
        $position = $argument->attribute->position;
        if ($position !== null) {
            if (isset($this->positionals[$position])) {
                throw new LogicException("Duplicate argument position '$position'");
            }
            $this->positionals[$position] = $argument;
        } else {
            foreach ([$argument->name, ...$argument->attribute->aliases] as $name) {
                if (isset($this->options[$name])) {
                    throw new LogicException("Duplicate option '$name'");
                }
                $this->options[$name] = $argument;
            }
        }
        foreach ($this->options as $name => $option) {
            if (isset($this->arguments[$name]) && $this->arguments[$name] !== $option) {
                throw new LogicException("Ambiguous argument alias '$name'");
            }
        }
    }

    public function option(string $name): ?ArgumentDefinition
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @param list<string> $args
     * @return array<string, mixed>
     */
    private function parse(array $args): array
    {
        /** @var array<string, list<string|int|float|bool>> $values */
        $values = [];
        $positionals = [];
        $optionsEnded = false;
        for ($i = 0, $count = count($args); $i < $count; $i++) {
            $input = $args[$i];
            if (!$optionsEnded && $input === '--') {
                $optionsEnded = true;
                continue;
            }
            if ($optionsEnded || !self::isOption($input)) {
                $positionals[] = $input;
                continue;
            }
            $parts = explode('=', ltrim($input, '-'), 2);
            $argument = $this->option($parts[0]);
            // Undeclared tokens remain available through CommandCtx::getArgs().
            if ($argument === null) {
                continue;
            }
            $value = $parts[1] ?? null;
            if ($value === null) {
                if ($argument->type === ArgType::Bool) {
                    $value = true;
                } else {
                    $next = $args[$i + 1] ?? null;
                    if ($next === null || $next === '--' || self::isOption($next)) {
                        throw new InvalidArgumentException("Option '{$parts[0]}' needs a value");
                    }
                    $value = $next;
                    $i++;
                }
            }
            $converted = $argument->convert($value);
            if ($argument->isArray) {
                $values[$argument->name][] = $converted;
            } else {
                $values[$argument->name] = [$converted];
            }
        }
        foreach ($this->positionals as $position => $argument) {
            if (isset($positionals[$position])) {
                $values[$argument->name] = array_map(
                    $argument->convert(...),
                    $argument->isArray ? array_slice($positionals, $position) : [$positionals[$position]],
                );
            }
        }
        $resolved = [];
        foreach ($this->arguments as $name => $argument) {
            if (!isset($values[$name])) {
                if ($argument->required) {
                    throw new InvalidArgumentException("Argument '$name' is required");
                }
                $resolved[$name] = $argument->default;
            } else {
                $resolved[$name] = $argument->isArray ? $values[$name] : $values[$name][0];
            }
        }
        return $resolved;
    }

    public static function isOption(string $input): bool
    {
        return strlen($input) > 1 && str_starts_with($input, '-') && !is_numeric($input);
    }

    /**
     * @param list<string> $args
     * @return Closure(): int
     */
    public function prepare(SwewCommander $commander, Output $output, array $args): Closure
    {
        $values = $this->parse($args);
        /** @var object&callable(): int $command */
        $command = $this->class->newInstance();
        $arguments = [];
        foreach ($this->arguments as $name => $definition) {
            $definition->property->setValue($command, $values[$name]);
            $argument = new CommandArgument($values[$name]);
            foreach ([$name, ...$definition->attribute->aliases] as $alias) {
                $arguments[$alias] = $argument;
            }
        }
        $context = new CommandCtx($output, $commander, $arguments, [$this->attribute->name, ...$args]);
        foreach ($this->contexts as $property) {
            $property->setValue($command, $context);
        }
        /** @var Closure(): int $handler */
        $handler = $command(...);
        return $handler;
    }

    public function getHelpMessage(string $template = ''): string
    {
        if ($template === '') {
            $template = "<yellow>Description:</>\n {desc}\n\n<yellow>Usage:</>\n {name} {usage}\n\nOptions:\n{options}";
        }
        $options = [];
        $maxLength = 0;
        foreach ($this->arguments as $argument) {
            $names = $argument->getNames();
            $options[$names] = $argument->attribute->desc;
            $maxLength = max($maxLength, strlen($names));
        }
        $formattedOptions = [];
        foreach ($options as $names => $desc) {
            $formattedOptions[] = $desc === ''
                ? " <green>$names</>"
                : sprintf(' <green>%s</>%s', str_pad($names, $maxLength + 2), $desc);
        }
        $usage = [];
        foreach ($this->positionals as $argument) {
            $name = $argument->name . ($argument->isArray ? '...' : '');
            $usage[] = $argument->required ? "<$name>" : "[$name]";
        }
        $usage[] = '[options]';
        return str_replace(
            ['{desc}', '{name}', '{usage}', '{options}'],
            [$this->attribute->desc, $this->attribute->name, implode(' ', $usage), implode("\n", $formattedOptions)],
            $template,
        );
    }
}
