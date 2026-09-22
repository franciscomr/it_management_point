<?php

use App\Modules\Shared\Models\Tenant;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class
);

function tenantManager(): TenantManager
{
    return app(TenantManager::class);
}
describe("TenantManager", function () {
    it("Han no Tenant by default", function () {
        expect(tenantManager()->hasTenant())->toBeFalse()
            ->and(tenantManager()->getTenant())->toBeNull();
    });

    it("Returns an instance of tenant", function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);
        expect(tenantManager()->getTenant())->toBeInstanceOf(Tenant::class);
    });

    it("stores the current tenant", function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);
        expect(tenantManager()->getTenant())->toBe($tenant)
            ->and(tenantManager()->hasTenant())->toBeTrue();
    });

    it("Returns Tenant ID", function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);
        expect(tenantManager()->getTenantId())->toBe($tenant->getKey());
    });

    it('forgets the current tenant', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);

        tenantManager()->forgetTenant();

        expect(tenantManager()->hasTenant())->toBeFalse();
    });
});