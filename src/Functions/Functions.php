<?php

declare(strict_types=1);

if (!function_exists('__execCommand')) {
    function __execCommand(string $command): bool|string|null
    {
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return false;
        }

        /** @var array{1: resource} $pipes */
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($process);

        return $output === '' ? null : $output;
    }
}
