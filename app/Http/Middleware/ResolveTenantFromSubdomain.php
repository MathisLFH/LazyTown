<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $rootDomain = strtolower(Tenant::rootDomain());
        $subdomainSuffix = '.'.$rootDomain;

        if (! str_ends_with($host, $subdomainSuffix)) {
            return $next($request);
        }

        $subdomain = substr($host, 0, -strlen($subdomainSuffix));
        $tenant = Tenant::query()
            ->where('subdomain', $subdomain)
            ->firstOrFail();

        if ($tenant->isAwaitingPayment()) {
            return redirect()->to(rtrim((string) config('app.url'), '/').route('register.payment', absolute: false));
        }

        $request->attributes->set(Tenant::class, $tenant);

        return $next($request);
    }
}
