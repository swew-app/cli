<?php

declare(strict_types=1);

namespace Swew\Cli\Command;

class CommandParser
{
    public function parseName(string $str): string
    {
        $spacePos = strpos($str, ' ');
        if ($spacePos === false) {
            return $str;
        }
        return substr($str, 0, $spacePos);
    }

    public function getRequiredArguments(string $str): array
    {
        preg_match_all('/\{([^\}]+)\}/', $str, $matches);

        $result = array_combine($matches[1], array_fill(0, count($matches[1]), ''));

        return $result;
    }

    public function parseArgumentKeyVal(string $str): array
    {
        $arr = explode('=', $str);
        $val = '';

        if (isset($arr[1])) {
            if ($arr[1] === '[]') {
                $val = [];
            } else {
                $val = $arr[1];
            }
        }

        $name = ltrim($arr[0], '-');

        return [$name, $val];
    }
}
