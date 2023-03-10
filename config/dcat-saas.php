<?php

return [
    'name' => 'DcatSaas',

    'paths' => [
        'saas'          => base_path(),

        'generator' => [
            'config'            => ['path' => 'config', 'generate' => false],
            'command'           => ['path' => 'app/Console', 'generate' => false],
            'migration'         => ['path' => 'database/migrations', 'generate' => false],
            'seeder'            => ['path' => 'database/seeders', 'generate' => false],
            'factory'           => ['path' => 'database/factories', 'generate' => false],
            'model'             => ['path' => 'app/Models', 'generate' => false],
            'routes'            => ['path' => 'routes', 'generate' => false],
            'controller'        => ['path' => 'app/Http/Controllers', 'generate' => false],
            'filter'            => ['path' => 'app/Http/Middleware', 'generate' => false],
            'request'           => ['path' => 'app/Http/Requests', 'generate' => false],
            'provider'          => ['path' => 'app/Providers', 'generate' => false],
            'assets'            => ['path' => 'resources/assets', 'generate' => false],
            'lang'              => ['path' => 'resources/lang', 'generate' => false],
            'views'             => ['path' => 'resources/views', 'generate' => false],
            'test'              => ['path' => 'tests/Unit', 'generate' => false],
            'test-feature'      => ['path' => 'tests/Feature', 'generate' => false],
            'repository'        => ['path' => 'app/Repositories', 'generate' => false],
            'event'             => ['path' => 'app/Events', 'generate' => false],
            'listener'          => ['path' => 'app/Listeners', 'generate' => false],
            'policies'          => ['path' => 'app/Policies', 'generate' => false],
            'rules'             => ['path' => 'app/Rules', 'generate' => false],
            'jobs'              => ['path' => 'app/Jobs', 'generate' => false],
            'emails'            => ['path' => 'app/Mail', 'generate' => false],
            'notifications'     => ['path' => 'app/Notifications', 'generate' => false],
            'resource'          => ['path' => 'app/Http/Resources', 'generate' => false],
        ],
    ],

    'stubs' => [
        'path'         => dirname(__DIR__) . '/src/Commands/stubs',
        'files'        => [
            'tenant_model'                      => 'app/Models/Tenant.php',
            'tenant_init_seeder'                => 'database/seeders/TenantInitSeeder.php',
            'application_api'                   => 'routes/application_api.php.example',
            'application_web'                   => 'routes/application_web.php.example',
            'application_route_provider'        => 'app/Providers/ApplicationRouteServiceProvider.php',
            'admin_tenant_manage_controller'    => 'app/Http/Controllers/Admin/TenantController.php',
            'oem_controller'                    => 'app/Http/Controllers/Tenant/OemController.php',
            'global_controller'                 => 'app/Http/Controllers/Tenant/GlobalController.php',
        ],
        'gitkeep'      => true,
    ],
];
