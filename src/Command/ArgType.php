<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

enum ArgType
{
    case Int;
    case Str;
    case Bool;

    public function default(): mixed
    {
        return match ($this) {
            ArgType::Int => 0,
            ArgType::Str => '',
            ArgType::Bool => false,
        };
    }

    public function val(mixed $val): mixed
    {
        if (is_array($val)) {
            return array_map(
                fn ($v) => $this->val($v),
                $val
            );
        }

        return match ($this) {
            ArgType::Int => intval($val),
            ArgType::Str => strval($val),
            ArgType::Bool => $val === 'false' ? false : boolval($val),
        };
    }
}
