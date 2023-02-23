<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

class CommandArgument
{
    private array $names = [];

    private readonly string $declaration;

    public function __construct(string $declaration)
    {
        $this->declaration = trim($declaration);

        $part = current(explode('=', $this->getFirstPart(), 2));

        $this->names = array_map(
            fn (string $name) => trim(trim($name), '-'),
            explode('|', $part)
        );
    }

    private function getFirstPart(): string
    {
        $part = current(explode(':', $this->declaration, 2));
        return trim(current(explode('(', $part, 2)));
    }

    public function is(string $name): bool
    {
        return in_array($name, $this->names, true);
    }

    public function isRequired(): bool
    {
        $str = current(explode(':', $this->declaration, 2));
        return strpos($str, '=') === false;
    }

    public function getValue(): mixed
    {
        $type = $this->getType();
        $part = $this->getFirstPart();

        if (strpos($part, '=') === false) {
            return $type->default();
        }

        $v = explode('=', $part, 2);
        $part = end($v);

        return $type->val($part);
    }

    public function getType(): ArgType
    {
        $part = current(explode(':', $this->declaration, 2));

        preg_match('/\((.*?)\)/', $part, $matches);

        if (isset($matches[1])) {
            return match ($matches[1]) {
                'int' => ArgType::Int,
                'str' => ArgType::Str,
                'bool' => ArgType::Bool,
            };
        }
        return ArgType::Str;
    }

    public function getDescription(): string
    {
        $parts = explode(':', $this->declaration, 2);

        if (count($parts) === 2) {
            return trim(end($parts));
        }
        return '';
    }

    public function isArgument(): bool
    {
        return $this->declaration[0] === '-';
    }
}
