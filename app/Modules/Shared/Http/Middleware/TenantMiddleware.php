<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Contracts\TenantResolverInterface;
use App\Modules\Shared\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        private readonly TenantResolverInterface $tenantResolver,
        private readonly TenantManager $tenantManager
    ) {

    }
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //1.- Encontrar el tenant en la base de datos (o cache) según el header
        $tenant = $this->tenantResolver->resolve();
        //2.- Guardar el tenant en TenantManager para que esté disponible en toda la app
        $this->tenantManager->setTenant($tenant);
        return $next($request);
    }
}
