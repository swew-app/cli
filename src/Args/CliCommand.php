<?php

declare(strict_types=1);

namespace Swew\Cli\Args;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class CliCommand
{
    public function __construct(
        public string $name,
        public string $desc = '',
    ) {
    }
}
