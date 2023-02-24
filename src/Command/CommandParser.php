<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

class CommandParser
{
    // private readonly array $argList;

    // private array $commands = [];

    // function __construct(array $argList = [])
    // {
    //     if (count($argList) > 0) {
    //         $this->argList = $argList;
    //     } else {
    //         $this->argList = array_slice($_SERVER['argv'], 1);
    //     }
    // }

    public static function parseName(string $str): string
    {
        if (class_exists($str)) {
            $str = constant($str . '::NAME');
        }

        $spacePos = strpos($str, ' ');
        if ($spacePos === false) {
            return $str;
        }
        return substr($str, 0, $spacePos);
    }
}
