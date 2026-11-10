```php
<?php

namespace My\Cli\Commands;

use Swew\Cli\CommandCtx;
use Swew\Cli\Args\Argument;
use Swew\Cli\Args\CliCommand;
use Swew\Cli\Args\CliCtx;

#[CliCommand(name:'show:time', desc: 'The function shows the current time.')]
class ShowTimeCommand {
    #[CliCtx]
    private CommandCtx $ctx;

    // php index.php show:time --prefix today
    #[Argument(name: 'prefix', required: false)]
    public string $prefix = '';


    public function __invoke(): int
    {
        $prefix = $this->ctx->arg('prefix')->getValue();
        // or
        $prefix = $this->prefix;

        $time = $prefix . ' ' . date('H:i:s');

        $this->ctx->output->writeLn($time);

        return 0;
    }
}
```

```php
<?php
// index.php

require __DIR__ . '/vendor/autoload.php';

use My\Cli\Commands\ShowTimeCommand;
use Swew\Cli\SwewCommander;

class MySuperCli extends SwewCommander
{
    protected array $commands = [
        ShowTimeCommand::class,
    ];
}

(new MySuperCli($argv))->run();
```

```sh
php index.php show:time --prefix at
// at 22:10:45
```
