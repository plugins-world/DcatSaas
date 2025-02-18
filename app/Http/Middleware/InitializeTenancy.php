<?php

declare(strict_types=1);

namespace Plugins\DcatSaas\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;

class InitializeTenancy
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
        // path
        // header or query
        // subdomain or domain
        // domain
        try {
            return app(InitializeTenancyByPath::class)->handle($request, $next);
        } catch (\Throwable $e) {
        }

        try {
            return app(InitializeTenancyByRequestData::class)->handle($request, $next);
        } catch (\Throwable $e) {
        }

        try {
            return app(InitializeTenancyByDomainOrSubdomain::class)->handle($request, $next);
        } catch (\Throwable $e) {
        }

        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}
