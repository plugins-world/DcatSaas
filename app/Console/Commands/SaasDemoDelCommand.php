<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;

class SaasDemoDelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:demo-del {tenant=foo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除租户';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenant1 = \App\Models\Tenant::find($id = $this->argument('tenant'));
        $tenant1?->delete();

        $this->info("{$id} 删除成功");
        return 0;
    }
}
