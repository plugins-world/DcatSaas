<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;

class SaasDemoAddCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:demo-add {tenant=foo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建租户';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenant1 = \App\Models\Tenant::create(['id' => $id = $this->argument('tenant')]);
        $tenant1->domains()->create(['domain' => "{$id}.".str_replace(['http://', 'https://'], '', config('app.url'))]);

        $this->info("{$id} 创建成功");
        return 0;
    }
}
