<?php

declare(strict_types=1);

function execCommand(string $command): bool|null|string
{
    return shell_exec($command);
}
