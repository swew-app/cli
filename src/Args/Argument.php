<?php

declare(strict_types=1);

namespace Swew\Cli\Args;

use Attribute;
use Swew\Cli\Command\ArgType;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Argument
{
    /** @param list<string> $aliases */
    public function __construct(
        public ?string $name = null,
        public ?bool $required = null,
        public string $desc = '',
        public array $aliases = [],
        public ?int $position = null,
        public ?ArgType $itemType = null,
    ) {
    }
}
