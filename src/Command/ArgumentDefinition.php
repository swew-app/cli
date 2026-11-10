<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

use InvalidArgumentException;
use LogicException;
use ReflectionNamedType;
use ReflectionProperty;
use Swew\Cli\Args\Argument;

/** @internal */
final readonly class ArgumentDefinition
{
    public string $name;
    public ArgType $type;
    public bool $isArray;
    public bool $required;
    public mixed $default;

    public function __construct(
        public ReflectionProperty $property,
        public Argument $attribute,
    ) {
        $this->name = $attribute->name ?? $property->getName();
        foreach ([$this->name, ...$attribute->aliases] as $name) {
            if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/D', $name) !== 1) {
                throw new LogicException("Invalid argument name '$name'");
            }
        }
        if ($attribute->position !== null && ($attribute->position < 0 || $attribute->aliases !== [])) {
            throw new LogicException("Invalid positional argument '{$this->name}'");
        }

        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType || $property->isStatic() || $property->isReadOnly()) {
            throw new LogicException("Argument '{$this->name}' needs a mutable, typed instance property");
        }

        $this->isArray = $type->getName() === 'array';
        $argumentType = $this->isArray ? $attribute->itemType : ArgType::tryFrom($type->getName());
        if ($argumentType === null || !$this->isArray && $attribute->itemType !== null) {
            throw new LogicException("Unsupported type for '{$this->name}'; arrays need itemType");
        }
        $this->type = $argumentType;
        $this->required = $attribute->required ?? !$property->hasDefaultValue();
        if (!$this->required && !$property->hasDefaultValue()) {
            throw new LogicException("Optional argument '{$this->name}' needs a property default");
        }
        $this->default = $property->getDefaultValue();
        if (
            $this->isArray
            && is_array($this->default)
            && array_filter($this->default, fn(mixed $value) => get_debug_type($value) !== $this->type->value) !== []
        ) {
            throw new LogicException("Default items for '{$this->name}' must be {$this->type->value}");
        }
    }

    public function convert(string|bool $value): string|int|float|bool
    {
        try {
            return $this->type->val($value);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("Argument '{$this->name}' expects {$this->type->value}");
        }
    }

    public function getNames(): string
    {
        if ($this->attribute->position !== null) {
            return $this->name;
        }

        return implode(', ', array_map(fn(string $name) => (strlen($name) === 1 ? '-' : '--') . $name, [
            $this->name,
            ...$this->attribute->aliases,
        ]));
    }
}
