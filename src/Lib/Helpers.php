<?php

declare(strict_types=1);

namespace Swew\Cli\Lib;

class Helpers
{
    public static function isInteractiveInput(mixed $inputStream): bool
    {
        if ('php://stdin' !== (stream_get_meta_data($inputStream)['uri'] ?? null)) {
            return false;
        }

        if (\function_exists('stream_isatty')) {
            return @stream_isatty(fopen('php://stdin', 'r'));
        }

        if (\function_exists('posix_isatty')) {
            return @posix_isatty(fopen('php://stdin', 'r'));
        }

        if (!\function_exists('exec')) {
            return true;
        }

        exec('stty 2> /dev/null', $output, $status);

        return 1 !== $status;
    }
}
