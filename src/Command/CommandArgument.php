<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

class CommandArgument
{
    private array $names = [];

    private mixed $parsedValue = null;

    private ?ArgType $currentType = null;

    private readonly string $declaration;

    public function __construct(
        string $declaration,
        private readonly int $commandIndex = 0
    ) {
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

    public function parseInput(array $args): void
    {
        $isCommand = !$this->isArgument();

        if ($isCommand) {
            $this->setValue($args[$this->commandIndex]);
            return;
        }

        $isFind = false;

        // Loop through the arguments and parse them
        foreach ($args as $arg) {
            if ($isFind) {
                $this->setValue($arg);
                $isFind = false;
                continue;
            }

            // Check if the argument is a command (starts with -- or -)
            if (strpos($arg, '-') === 0) {
                $isFind = false;

                // Remove the -- or - from the beginning of the command
                $command = ltrim($arg, '-');

                // Check if the command has a value (contains =)
                if (strpos($command, '=') !== false) {
                    // Split the command into the command name and value
                    $list = explode('=', $command, 2);

                    $name = $list[0];
                    $value = $list[1] ?? '';

                    if ($this->is($name)) {
                        $this->setValue($value);
                        continue;
                    }
                } else {
                    if ($this->is($command)) {
                        $isFind = true;

                        if ($this->getType() === ArgType::Bool) {
                            $this->setValue(true);
                            $isFind = false;
                        }

                        continue;
                    }
                }
            }
        }
    }

    public function setValue(mixed $val): void
    {
        if ($this->isArray()) {
            if (!is_array($this->parsedValue)) {
                $this->parsedValue = [];
            }
            if (is_array($val)) {
                $this->parsedValue += $val;
            } else {
                $this->parsedValue[] = $val;
            }
        } else {
            $this->parsedValue = $val;
        }
    }

    public function getValue(): mixed
    {
        $type = $this->getType();

        if (!is_null($this->parsedValue)) {
            return $type->val($this->parsedValue);
        }

        $part = $this->getFirstPart();

        if (strpos($part, '=') === false) {
            return $type->default();
        }

        $v = explode('=', $part, 2);
        $part = end($v);

        return $type->val($part);
    }

    public function isArray(): bool
    {
        $part = $this->getFirstPart();

        if (strpos($part, '=') === false) {
            return false;
        }

        $list = explode('=', $part, 2);

        return ($list[1] ?? '') === '[]';
    }

    public function getType(): ArgType
    {
        if (is_null($this->currentType)) {
            $part = current(explode(':', $this->declaration, 2));
            preg_match('/\((.*?)\)/', $part, $matches);

            if (isset($matches[1])) {
                $this->currentType = match ($matches[1]) {
                    'int' => ArgType::Int,
                    'str' => ArgType::Str,
                    'bool' => ArgType::Bool,
                };
            } else {
                $this->currentType = ArgType::Str;
            }
        }

        return $this->currentType;
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
