<?php


use App\Modules\Shared\Enums\TenantStatus;
use App\Modules\Shared\Http\Middleware\TenantMiddleware;
use App\Modules\Shared\Models\Tenant;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class
);

beforeEach(function () {
    Route::middleware(TenantMiddleware::class)->get('/__test/tenant', function () {
        return response()->json([
            'tenant_id' => app(TenantManager::class)->getTenantId(),
        ]);
    });
});
describe('TenantMiddleware', function () {
    it('rejects requests without the tenant header', function () {
        $this->getJson('/__test/tenant')
            ->assertStatus(400)
            ->assertJson(['code' => 'TENANT_MISSING']);
    });

    it('rejects a malformed tenant header', function () {
        $this->getJson('/__test/tenant', [
            'X-Tenant-ID' => 'invalid-uuid',
        ])
            ->assertStatus(404)
            ->assertJson(['code' => 'TENANT_NOT_FOUND']);
    });

    it('rejects a well-formed but nonexistent tenant id', function () {
        $this->withHeader('X-Tenant-ID', (string) Str::ulid())
            ->getJson('/__test/tenant')
            ->assertStatus(404)
            ->assertJson(['code' => 'TENANT_NOT_FOUND']);
    });

    it('rejects a suspended tenant', function () {
        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::SUSPENDED,
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/__test/tenant')
            ->assertStatus(403)
            ->assertJson(['code' => 'TENANT_SUSPENDED']);
    });

    it('loads an active tenant from the header', function () {
        $tenant = Tenant::factory()->create([]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/__test/tenant')
            ->assertOk()
            ->assertJson(['tenant_id' => $tenant->id]);
    });
});
