<?php

namespace App\Modules\Shared\Services;

use App\Modules\Shared\Contracts\TenantResolverInterface;
use App\Modules\Shared\Exceptions\TenantException;
use App\Modules\Shared\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HeaderTenantResolver implements TenantResolverInterface
{

    public function __construct(protected Request $request)
    {
    }

    public function resolve(): ?Tenant
    {
        $tenantId = $this->request->header('X-Tenant-ID');
        if (!$tenantId) {
            throw TenantException::tenantHeaderMissing();
        }
        if (!Str::isUlid($tenantId)) {
            throw TenantException::tenantNotFound($tenantId);
        }
        $tenant = Tenant::query()->find($tenantId);
        if (!$tenant) {
            throw TenantException::tenantNotFound($tenantId);
        }
        if (!$tenant->isActive()) {
            throw TenantException::tenantSuspended();
        }
        return $tenant;
    }
}
