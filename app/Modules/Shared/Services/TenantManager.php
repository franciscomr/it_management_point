<?php

namespace App\Modules\Shared\Services;

use App\Modules\Shared\Models\Tenant;

class TenantManager
{
    private ?Tenant $currentTenant = null;
    public function getTenant(): ?Tenant
    {
        return $this->currentTenant;
    }
    public function setTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function gettenantId(): string
    {
        return $this->currentTenant?->getKey();
    }

    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    public function forgetTenant(): void
    {
        $this->currentTenant = null;
    }

}
