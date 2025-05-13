<?php

namespace Plugins\DcatSaas\Http\Middleware;

use Plugins\DcatSaas\Helpers\StrHelper;
use Closure;
use Illuminate\Http\Request;

class InitializeTenancyConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $topDomain = StrHelper::extractDomainByUrl(\request()->root());

        if (tenant()) {
            config([
                'app.url' => \request()->root(),
                'admin.name' => sprintf('%s %s', tenant('name'), config('admin.name')),
                'admin.title' => sprintf('%s %s', tenant('name'), config('admin.title')),
                'admin.logo' => str_replace(config('admin.name'), tenant('name') . ' ' . config('admin.name'), config('admin.logo')),
                'sanctum.stateful' => array_merge(config('sanctum.stateful'), [\request()->host()]),
                'session.domain' => ".{$topDomain}",
            ]);
        }

        return $next($request);
    }
}
