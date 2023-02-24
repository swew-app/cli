<?php

declare(strict_types=1);

if (!function_exists('__execCommand')) {
    function __execCommand(string $command): bool|null|string
    {
        return shell_exec($command);
    }
}
