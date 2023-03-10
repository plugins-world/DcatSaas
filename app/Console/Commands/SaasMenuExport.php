<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;

class SaasMenuExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:menu-export {tenant=foo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出菜单';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("正在导出 平台 的菜单信息");
        $this->call('admin:export-seed', [
            'classname' => 'AdminTablesSeeder',
        ]);

        $tenant = \App\Models\Tenant::find($tenantId = $this->argument('tenant'));

        if (is_null($tenant)) {
            $this->warn("未找到租户 {$tenantId} 的菜单信息");
        }
        
        $tenant?->run(function ($tenant) {
            $this->info("正在导出租户 {$tenant->id} 的菜单信息");
            $this->call('admin:export-seed', [
                'classname' => 'TenantAdminTablesSeeder',
            ]);
        });

        return 0;
    }
}
