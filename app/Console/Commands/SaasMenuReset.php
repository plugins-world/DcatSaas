<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;

class SaasMenuReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:menu-reset {--admin : 是否重置中心应用菜单} {--tenant : 是否重置所有租户应用菜单}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重置菜单';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('admin')) {
            $this->info('中心应用菜单重置中');
            $this->call('db:seed', ['--class' => \Database\Seeders\AdminTablesSeeder::class]);
            $this->info('中心应用菜单重置完成');
        }

        if ($this->option('tenant')) {
            \App\Models\Tenant::all()->runForEach(function ($tenant) {
                $this->warn("重置租户 {$tenant->id} 菜单中");
                $this->call('db:seed', ['--class' => \Database\Seeders\TenantAdminTablesSeeder::class]);
                $this->warn("重置租户 {$tenant->id} 菜单已完成");
            });
        }

        return 0;
    }
}
