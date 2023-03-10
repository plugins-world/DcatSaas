<?php

namespace Plugins\DcatSaas\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Plugins\DcatSaas\Support\Process;
use Plugins\DcatSaas\Support\Config\GenerateConfigReader;

class SaasInstallCommand extends Command
{
    use Traits\StubTrait;

    protected $signature = 'saas:install';

    protected $description = 'Init saas';

    /**
     * The laravel filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Execute the console command.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function handle()
    {
        $result = $this->confirm("Are your sure reset saas application?");
        if (!$result) {
            return 0;
        }

        $this->filesystem = $this->laravel['files'];

        try {
            DB::selectOne('select now()');
        } catch (\Throwable $e) {
            $this->error("连接数据库失败，请检查数据库配置");
            $this->info('');
            $this->error($e->getMessage());
            return 0;
        }

        if (!Schema::hasTable('plugins')) {
            $this->error("Base table plugins doesn't exist.");
            return 0;
        }

        $this->generateFolders();
        $this->generateFiles();

        // 3. 执行初始化 `php artisan tenancy:install`
        $this->call('tenancy:install');
        $this->initTenancyForProject();

        // 8. 执行 `admin:publish` 发布 `config/admin.php` 配置
        $this->call('admin:publish');
        // 9. 执行 `admin:install` 初始化

        // set admin config
        $config = app('config');
        $config->set('admin', array_merge(
            require config_path('admin.php'),
            $config->get('admin', []) ?? []
        ));

        $this->call('admin:install');
        $this->initCentralAdminInfo();
        $this->call('admin:app', [
            'name' => 'AdminTenant',
        ]);
        $this->initTenantAdminInfo();

        // copy assets for AdminTenant
        shell_exec(
            sprintf('mkdir -p %s && cp -r %s %s', 
                    public_path('tenancy/assets/'),
                    public_path('vendor'),
                    public_path('tenancy/assets/')
            )
        );

        $this->initApplication();
        $this->initApplicationAppServiceProvider();
        $this->initApplicationRouteServiceProvider();
        $this->initApplicationWebRoute();
        $this->initApplicationApiRoute();
        $this->initApplicationTenantRoute();
        $this->initApplicationGitignore();

        $this->call('saas:menu-export');

        $this->info("Saas init successfully");
    }

    public function initTenancyForProject()
    {
        // 4. 生成 `Tenant` 模型，继承默认的 `Tenant` 模型，然后更新 `config/tenancy.php` 的 `tenant_model` 配置
        $this->replaceTenancyConfig();
        // 5. 修改服务提供者 `app/Providers/TenancyServiceProvider.php` 增加创建租户时初始化数据的功能
        $this->replaceTenancyProvider();
        // 6. 更新 `database/seeders/DatabaseSeeder.php`, 允许增加创建租户时的初始化逻辑
        $this->replaceSeeder();
    }

    public function replaceTenancyConfig()
    {
        $content = File::get($filePath = config_path('tenancy.php'));

        if (str_contains($content, "url_override")) {
            return;
        }

        $newContent = str_replace(
            [
                "use Stancl\Tenancy\Database\Models\Tenant;\n\nreturn [",
                "'tenant_model' => Tenant::class,",
                "'localhost',\n    ],",
                "'prefix' => 'tenant',",
                "_base' => 'tenant',",
                "// '--force' => true,",
                "'public' => '%storage_path%/app/public/',\n        ],",
            ],
            [
                "use Stancl\Tenancy\Database\Models\Tenant;\n\n\$prefix = env('DB_DATABASE') . '_';\n\nreturn [",
                "'tenant_model' => \App\Models\Tenant::class,",
                "'localhost',\n\t\tstr_replace(['http://', 'https://'], '', trim(env('APP_URL', ''), '/')),\n    ],",
                "'prefix' => \$prefix,",
                "_base' => \$prefix,",
                "'--force' => true,",
                "'public' => '%storage_path%/app/public/',
        ],

        /*
        * Use this to support Storage url method on local driver disks.
        * You should create a symbolic link which points to the public directory using command: artisan tenants:link
        * Then you can use tenant aware Storage url: Storage::disk('public')->url('file.jpg')
        *
        * See https://github.com/archtechx/tenancy/pull/689
        */
        'url_override' => [
            // The array key is local disk (must exist in root_override) and value is public directory (%tenant_id% will be replaced with actual tenant id).
            'public' => 'tenants/public-%tenant_id%',
        ],",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function replaceTenancyProvider()
    {
        $content = File::get($filePath = base_path('app/Providers/TenancyServiceProvider.php'));

        $newContent = str_replace(
            [
                "// Jobs\SeedDatabase::class,",
                "send(function (Events\TenantCreated \$event) {
                    return \$event->tenant;
                })",
                "send(function (Events\TenantDeleted \$event) {
                    return \$event->tenant;
                })",
            ],
            [
                "Jobs\SeedDatabase::class,",
                "send(function (Events\TenantCreated \$event) {
                    \$tenant = \$event->tenant;
                    \$target = base_path(sprintf(\"storage/tenants/%s%s/app/public\", 
                        config('tenancy.filesystem.suffix_base'),
                        \$tenant->id));
                    \$link = str_replace('%tenant_id%', \$tenant->id, config('tenancy.filesystem.url_override.public', 'public-%tenant_id%'));

                    chdir(public_path());
                    \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname(\$target));
                    \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname(\$link));
                    \Illuminate\Support\Facades\File::link(\$target, \$link);
                    return \$event->tenant;
                })",
                "send(function (Events\TenantDeleted \$event) {
                    \$tenant = \$event->tenant;
                    \$target = base_path(sprintf(\"storage/tenants/%s%s/app/public\", 
                        config('tenancy.filesystem.suffix_base'),
                        \$tenant->id));
                    \$link = str_replace('%tenant_id%', \$tenant->id, config('tenancy.filesystem.url_override.public', 'public-%tenant_id%'));

                    chdir(public_path());
                    \Illuminate\Support\Facades\File::delete(\$link);
                    \Illuminate\Support\Facades\File::deleteDirectory(dirname(dirname(\$target)));
                    return \$event->tenant;
                })",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function replaceSeeder()
    {
        $content = File::get($filePath = database_path('seeders/DatabaseSeeder.php'));

        $newContent = str_replace(
            [
                "// \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()",
            ],
            [
                "// \App\Models\User::factory(10)->create();

        \$this->call(TenantInitSeeder::class);

        // \App\Models\User::factory()",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initCentralAdminInfo()
    {
        // 10. 将账号、菜单等相关数据库复制一份给到 `database/migrations/tenant`
        $this->copyMigrations();
        // 11. 开启 `admin` 多后台模式
        $this->enableMultiAdmin();
        $this->initCentralAdminRoute();
    }

    public function initTenantAdminInfo()
    {
        $this->initTenantAdmin();
        $this->initTenantRoute();
    }

    public function copyMigrations()
    {
        $files = File::glob(database_path("migrations/*admin*"));

        foreach ($files as $file) {
            File::copy($file, database_path("migrations/tenant/" . basename($file)));
        }
    }

    public function enableMultiAdmin()
    {
        $content = File::get($filePath = config_path('admin.php'));

        $newContent = str_replace(
            [
                "<?php\n\nreturn [\n\n    /*",
                "Dcat Admin'",
                "''.",
            ],
            [
                "<?php\n\nreturn [\n\t'multi_app' => [\n\t\t// 与新应用的配置文件名称一致\n\t\t// 设置为true启用，false则是停用\n\t\t'admin-tenant' => true,\n\t],\n\n    /*",
                "'.env('APP_NAME', '')",
                "",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initCentralAdminRoute()
    {
        $content = File::get($filePath = app_path('Admin/routes.php'));

        $newContent = str_replace(
            [
                "<?php\n\nuse Illuminate\Routing\Router;",
                "\$router->get('/', 'HomeController@index');",
            ],
            [
                "<?php\n\nuse App\Admin\Controllers\HomeController;\nuse Illuminate\Routing\Router;",
                "\$router->get('/', [HomeController::class, 'index']);",
            ],
            $content
        );

        $newContent = str_replace([
            "
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router \$router) {

    \$router->get('/', [HomeController::class, 'index']);

});
"
        ], [
            "
use Dcat\Admin\Admin;

foreach (config('tenancy.central_domains', []) as \$domain) {
    Admin::routes();

    Route::group([
        'prefix'     => config('admin.route.prefix'),
        // 'namespace'  => config('admin.route.namespace'),
        'middleware' => config('admin.route.middleware'),
        'domain' => \$domain,
    ], function (Router \$router) {
        \$router->get('/', [HomeController::class, 'index']);
    });
}
",
        ], $newContent);
        File::put($filePath, $newContent);
    }

    public function initTenantAdmin()
    {
        $content = File::get($filePath = config_path('admin-tenant.php'));

        $newContent = str_replace(
            [
                "Dcat Admin'",
                "''.",
                "'middleware' => ['web', 'admin'],",
                "'prefix' => 'admin-tenant',",
            ],
            [
                "'.env('APP_NAME', '')",
                "",
                "'middleware' => [\n\t\t\t'tenant',\n\t\t\t\Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,\n\t\t\t'web', 'admin',\n\t\t],\n",
                "'prefix' => 'manage',",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initTenantRoute()
    {
        $content = File::get($filePath = app_path('AdminTenant/routes.php'));

        if (str_contains($content, "use App\Admin\Controllers\HomeController;")) {
            return;
        }

        $newContent = str_replace(
            [
                "    'namespace'  => config('admin.route.namespace'),",
                "<?php\n\nuse Illuminate\Routing\Router;",
                "\$router->get('/', 'HomeController@index');",
            ],
            [
                "    // 'namespace'  => config('admin.route.namespace'),",
                "<?php\n\nuse App\AdminTenant\Controllers\HomeController;\nuse Illuminate\Routing\Router;",
                "\$router->get('/', [HomeController::class, 'index']);",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initApplication()
    {
        $content = File::get($filePath = config_path('app.php'));

        $newContent = str_replace(
            [
                "'timezone' => 'UTC',",
                "'locale' => 'en',",
            ],
            [
                "'timezone' => 'PRC',",
                "'locale' => 'zh_CN',",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initApplicationAppServiceProvider()
    {
        $content = File::get($filePath = app_path('Providers/AppServiceProvider.php'));

        $newContent = str_replace(
            [
                "
    public function register()
    {
        //
    }
",
            ],
            [
                "
    public function register()
    {
        if (\\request()->secure() && in_array(\\request()->getHost(), config('tenancy.central_domains', []))) {
            config([
                'admin.https' => true
            ]);
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Database\Eloquent\Builder::macro('result', \$paginate = function () {
            \$namespace = request()->route()->getAction('namespace');

            if (request('all') || request('export')) {
                return \$this->get(request('columns', ['*']));
            }

            if (\Illuminate\Support\Str::contains(\$namespace, 'App\Http\Controllers\Admin')) {
                return \$this->paginate(request('per_page', 20) <= 100 ? request('per_page', 20) : 100, request('columns', ['*']));
            }

            return \$this->paginate(request('per_page', 20) <= 100 ? request('per_page', 20) : 100, request('columns', ['*']));
        });
        \Illuminate\Database\Query\Builder::macro('result', \$paginate);
    }
",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initApplicationRouteServiceProvider()
    {
        $content = File::get($filePath = app_path('Providers/RouteServiceProvider.php'));

        $newContent = str_replace(
            [
                "\$this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });",
            ],
            [
                "\$this->routes(function () {
            foreach (config('tenancy.central_domains', []) as \$domain) {
                Route::middleware('api')
                    ->domain(\$domain) // <- 这里
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));

                Route::middleware('web')
                    ->domain(\$domain) // <- 这里
                    ->group(base_path('routes/web.php'));
            }
        });",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initApplicationWebRoute()
    {
        $content = File::get($filePath = base_path('routes/web.php'));

        $newContent = str_replace(
            [
                "
    return view('welcome');
",
            ],
            [
                "
    return admin_redirect('/');
    // return view('welcome');
",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initApplicationApiRoute()
    {
        $content = File::get($filePath = base_path('routes/api.php'));

        $newContent = str_replace(
            [
                "
*/

Route::middleware('auth:sanctum')->get('/user', function (Request \$request) {
    return \$request->user();
});

",
            ],
            [
                "
*/

Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth:api-admin'],
], function () {
    Route::post('login', [LaravelJwtAuth\AdministratorAuthController::class, 'login'])->withoutMiddleware(['auth:api-admin']);
    Route::post('logout', [LaravelJwtAuth\AdministratorAuthController::class, 'logout']);
    Route::post('refresh', [LaravelJwtAuth\AdministratorAuthController::class, 'refresh']);
    Route::get('me', [LaravelJwtAuth\AdministratorAuthController::class, 'me']);

    Route::prefix('tenants')->group(function () {
        Route::get('/', [Admin\TenantController::class, 'index']);
        Route::post('/', [Admin\TenantController::class, 'store']);
        Route::delete('/{tenantId}', [Admin\TenantController::class, 'destroy']);
    });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request \$request) {
//     return \$request->user();
// });

",
            ],
            $content
        );
        File::put($filePath, $newContent);
    }

    public function initApplicationTenantRoute()
    {
        $content = File::get($filePath = base_path('routes/tenant.php'));

        $newContent = str_replace(
            [
                "
Route::middleware([
    'tenant',
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
    });
});
",
            ],
            [
                "
// Route::middleware([
//     'tenant',
//     'web',
//     // InitializeTenancyByDomain::class,
//     // PreventAccessFromCentralDomains::class,
// ])->group(function () {
//     Route::get('/', function () {
//         return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
//     });
// });
",
            ],
            $content
        );
        File::put($filePath, $newContent);

        if (str_contains($newContent, '// InitializeTenancyByDomain::class,')) {
            return;
        }

        //         file_put_contents($filePath, "
        // Route::middleware([
        //     'tenant',
        //     'api',
        // ])->prefix('api')->group(function () {
        //     Route::get('oem-info', [Tenant\OemController::class, 'detail']);
        // });

        // Route::middleware([
        //     'tenant',
        //     'api',
        // ])->prefix('{tenant?}/api')->group(function () {
        //     Route::get('/', function () {
        //         return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id') . '. Request from api';
        //     });
        // });
        // ", FILE_APPEND);
    }

    public function initApplicationGitignore()
    {
        $content = File::get($filePath = base_path('.gitignore'));

        if (str_contains($content, '# tenant storage')) {
            return;
        }

        $prefix = config('tenancy.filesystem.suffix_base');

        file_put_contents($filePath, "
# tenant storage
extensions/backups/
{$prefix}*
!database/migrations/tenant
public-*
", FILE_APPEND);
    }

    /**
     * Get the list of folders will created.
     *
     * @return array
     */
    public function getFolders()
    {
        return config('dcat-saas.paths.generator');
    }

    /**
     * Generate the folders.
     */
    public function generateFolders()
    {
        foreach ($this->getFolders() as $key => $folder) {
            $folder = GenerateConfigReader::read($key);

            if ($folder->generate() === false) {
                continue;
            }

            $path = config('dcat-saas.paths.saas', base_path()) . '/' . $folder->getPath();

            $this->filesystem->makeDirectory($path, 0755, true);
            if (config('dcat-saas.stubs.gitkeep')) {
                $this->generateGitKeep($path);
            }
        }
    }

    /**
     * Generate git keep to the specified path.
     *
     * @param  string  $path
     */
    public function generateGitKeep($path)
    {
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Remove git keep from the specified path.
     *
     * @param  string  $path
     */
    public function removeParentDirGitKeep(string $path)
    {
        if (config('dcat-saas.stubs.gitkeep')) {
            $dirName = dirname($path);
            if (count($this->filesystem->glob("$dirName/*")) >= 1) {
                $this->filesystem->delete("$dirName/.gitkeep");
            }
        }
    }

    /**
     * Get the list of files will created.
     *
     * @return array
     */
    public function getFiles()
    {
        return config('dcat-saas.stubs.files');
    }

    /**
     * Generate the files.
     */
    public function generateFiles()
    {
        foreach ($this->getFiles() as $stub => $file) {

            $path = config('dcat-saas.paths.saas', base_path()) . '/' . $file;

            if ($keys = $this->getReplaceKeys($path)) {
                $file = $this->getReplacedContent($file, $keys);
                $path = $this->getReplacedContent($path, $keys);
            }

            if (!$this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
                $this->removeParentDirGitKeep($dir);
            }

            $this->filesystem->put($path, $this->getStubContents($stub));
            $this->removeParentDirGitKeep($dir);

            $this->info("Created : {$path}");
        }
    }
}
