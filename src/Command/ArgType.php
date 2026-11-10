<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

use InvalidArgumentException;

enum ArgType: string
{
    case Int = 'int';
    case Float = 'float';
    case Str = 'string';
    case Bool = 'bool';

    public function val(string|bool $value): string|int|float|bool
    {
        $result = match ($this) {
            self::Int => is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null,
            self::Float => is_string($value) && is_numeric($value) && is_finite((float) $value) ? (float) $value : null,
            self::Str => is_string($value) ? $value : null,
            self::Bool => match ($value) {
                true, 'true', '1' => true,
                false, 'false', '0' => false,
                default => null,
            },
        };

        if ($result === null) {
            throw new InvalidArgumentException("Expected {$this->value}");
        }

        return $result;
    }
}
