<?php

namespace Plugins\DcatSaas\Providers;

use App\Providers\TenancyServiceProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class DcatSaasServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        $this->loadMigrationsFrom(dirname(__DIR__, 2) . '/database/migrations');

        \Route::aliasMiddleware('tenant-init', \Plugins\DcatSaas\Http\Middleware\InitializeTenancy::class);
        \Route::aliasMiddleware('tenant-init-config', \Plugins\DcatSaas\Http\Middleware\InitializeTenancyConfig::class);
        \Route::middlewareGroup('tenant', [
            'tenant-init',
            'tenant-init-config',
        ]);

        $this->app->register(RouteServiceProvider::class);

        if (class_exists(\App\Providers\ApplicationRouteServiceProvider::class)) {
            $this->app->register(\App\Providers\ApplicationRouteServiceProvider::class);
        }

        // Event::listen(UserCreated::class, UserCreatedListener::class);

        if ($this->app->runningInConsole()) {
            $this->app->register(CommandServiceProvider::class);
        }

        $this->macro();
    }

    public function macro()
    {
        \Illuminate\Support\Facades\URL::macro('tenantFile', function (?string $file = '') {
            if (is_null($file)) {
                return null;
            }

            // file is the url information, which is returned directly. No tenant domain name splicing.
            if (str_contains($file, '://')) {
                return $file;
            }

            $prefix = 'storage';
            if (tenant()) {
                $prefix = str_replace(
                    '%tenant_id%',
                    tenant()->getKey(),
                    config('tenancy.filesystem.url_override.public', 'tenants/public-%tenant_id%')
                );
            }

            return url($prefix . '/' . $file);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (class_exists(TenancyServiceProvider::class)) {
            try {
                $this->app->register(TenancyServiceProvider::class);
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/config/dcat-saas.php',
            'dcat-saas'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $this->loadViewsFrom(dirname(__DIR__, 2) . '/resources/views', 'DcatSaas');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadTranslationsFrom(dirname(__DIR__, 2) . '/resources/lang', 'DcatSaas');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
