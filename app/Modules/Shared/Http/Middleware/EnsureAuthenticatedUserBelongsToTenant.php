<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Exceptions\TenantException;
use App\Modules\Shared\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedUserBelongsToTenant
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {
    }
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $this->tenantManager->getTenant();

        if ($user && $tenant && (string) $user->tenant_id !== (string) $tenant->id) {
            throw TenantException::tenantMismatch();
        }
        return $next($request);
    }
}
