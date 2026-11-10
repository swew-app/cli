<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

final readonly class CommandArgument
{
    public function __construct(
        private mixed $value,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
