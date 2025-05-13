<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;
use Fresns\PluginManager\Plugin;

class TenantPluginRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:plugin-rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration for per tenant';

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
                $this->call('plugin:migrate-rollback', [
                    'name' => $pluginName,
                ]);
            }
        });        
        
        return 0;
    }
}
