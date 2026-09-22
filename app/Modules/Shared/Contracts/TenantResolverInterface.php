<?php

namespace App\Modules\Shared\Contracts;

use App\Modules\Shared\Models\Tenant;

interface TenantResolverInterface
{
    public function resolve(): ?Tenant;
}
