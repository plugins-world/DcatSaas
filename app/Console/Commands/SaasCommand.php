<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class SaasCommand extends Command
{
    protected $signature = 'saas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available commands';

    /**
     * @var string
     */
    public static $logo = <<<LOGO
    ____             __     _____             _____
   / __ \_________ _/ /_   / ___/____ _____ _/ ___/
  / / / / ___/ __ `/ __/   \__ \/ __ `/ __ `/\__ \ 
 / /_/ / /__/ /_/ / /_    ___/ / /_/ / /_/ /___/ / 
/_____/\___/\__,_/\__/   /____/\__,_/\__,_//____/  
LOGO;

    public function handle(): void
    {
        $this->info(static::$logo);

        $this->comment('');
        $this->comment('Available commands:');

        $this->comment('');
        $this->comment('saas');
        $this->listAdminCommands();
    }

    protected function listAdminCommands(): void
    {
        $commands = collect(Artisan::all())->mapWithKeys(function ($command, $key) {
            if (
                Str::startsWith($key, 'saas')
                || Str::startsWith($key, 'tenants')
            ) {
                return [$key => $command];
            }

            return [];
        })->toArray();

        \ksort($commands);

        $width = $this->getColumnWidth($commands);

        /** @var Command $command */
        foreach ($commands as $command) {
            $this->info(sprintf(" %-{$width}s %s", $command->getName(), $command->getDescription()));
        }
    }

    private function getColumnWidth(array $commands): int
    {
        $widths = [];

        foreach ($commands as $command) {
            $widths[] = static::strlen($command->getName());
            foreach ($command->getAliases() as $alias) {
                $widths[] = static::strlen($alias);
            }
        }

        return $widths ? max($widths) + 2 : 0;
    }

    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @param  string  $string  The string to check its length
     * @return int The length of the string
     */
    public static function strlen($string): int
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }
}
