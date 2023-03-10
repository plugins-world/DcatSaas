<?php

namespace Plugins\DcatSaas\Console\Commands;

use Fresns\PluginManager\Plugin;
use Illuminate\Console\Command;

class TenantPluginMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:plugin-migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the plugin database migrations for per tenant';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \App\Models\Tenant::all()->runForEach(function ($tenant) {
            $plugin = new Plugin();

            foreach ($plugin->all() as $pluginName) {
                $this->call('plugin:migrate', [
                    'name' => $pluginName,
                ]);
            }
        });        
        
        return 0;
    }
}
