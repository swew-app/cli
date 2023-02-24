<?php

declare(strict_types=1);

use Swew\Cli\Command\CommandParser;

/**
 * - Собираем список классов Command - в хранилище [имя => Command::class]
 * -
 */

class TestParser extends CommandParser
{
    public function __call(string $name, array $args): mixed
    {
        return $this->$name(...$args);
    }
}

it('CommandParser', function () {
    $args = [''];
    $parser = new TestParser($args);
});
