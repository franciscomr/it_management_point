<?php

use App\Models\User;
use App\Modules\Auth\DTOs\CredentialsDTO;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Exceptions\AuthException;
use App\Modules\Auth\Services\LoginService;
use App\Modules\Shared\Exceptions\TenantException;
use App\Modules\Shared\Models\Tenant;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;


uses(
    Tests\TestCase::class,
    RefreshDatabase::class
);

function tenantManager(): TenantManager
{
    return app(TenantManager::class);
}

// TTLs cortos por defecto en los tests: nada obliga a usar los 720/43200
// minutos reales de producción, y usar valores pequeños hace las
// aserciones de expiración más fáciles de leer.
function loginService(int $defaultTtl = 60, int $rememberTtl = 1440): LoginService
{
    return new LoginService(tenantManager(), $defaultTtl, $rememberTtl);
}

function loginDto(string $email, string $password, bool $remember = false, string $deviceName = 'web'): CredentialsDTO
{
    return new CredentialsDTO(email: $email, password: $password, remember: $remember, deviceName: $deviceName);
}

function activeUser(Tenant $tenant, string $email, string $password): User
{
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $email,
        'password' => Hash::make($password),
        // Ajusta el case si tu UserStatus no se llama ACTIVE — no tengo
        // el código del enum, asumo el nombre por el uso que vimos en
        // LoginService ($user->status !== UserStatus::ACTIVE).
        'status' => UserStatus::ACTIVE,
    ]);
}

describe('LoginService', function () {

    it('throws when there is no tenant in context', function () {
        assertThrowsApiError(
            fn() => loginService()->login(loginDto('user@example.com', 'secret123')),
            TenantException::class,
            'TENANT_MISSING',
            400,
        );
    });

    it('logs in and returns a token for correct credentials', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);

        $user = activeUser($tenant, 'user@example.com', 'secret123');

        $result = loginService()->login(loginDto('user@example.com', 'secret123'));

        expect($result->user->is($user))->toBeTrue()
            ->and($result->token)->toBeString()->not->toBeEmpty()
            ->and($result->expiresAt)->not->toBeNull();
    });

    it('rejects an email that does not exist in the current tenant', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);

        assertThrowsApiError(
            fn() => loginService()->login(loginDto('ghost@example.com', 'secret123')),
            AuthException::class,
            'INVALID_CREDENTIALS',
            401,
        );
    });

    it('rejects an incorrect password', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);

        activeUser($tenant, 'user@example.com', 'secret123');

        assertThrowsApiError(
            fn() => loginService()->login(loginDto('user@example.com', 'wrong-password')),
            AuthException::class,
            'INVALID_CREDENTIALS',
            401,
        );
    });

    it('rejects login when the matching email belongs to a different tenant', function () {
        // Regresión: LoginService debe filtrar por tenant_id, no solo por
        // email — el esquema permite el mismo email en tenants distintos.
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        activeUser($tenantA, 'shared@example.com', 'secret123');
        activeUser($tenantB, 'shared@example.com', 'different-password');

        tenantManager()->setTenant($tenantB);

        assertThrowsApiError(
            fn() => loginService()->login(loginDto('shared@example.com', 'secret123')),
            AuthException::class,
            'INVALID_CREDENTIALS',
            401,
        );
    });

    it('rejects an inactive user even with correct credentials', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'inactive@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'inactive',
        ]);

        assertThrowsApiError(
            fn() => loginService()->login(loginDto('inactive@example.com', 'secret123')),
            AuthException::class,
            'USER_IS_NOT_ACTIVE',
            403,
        );
    });

    it('issues a short-lived token by default (no remember)', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);
        activeUser($tenant, 'user@example.com', 'secret123');

        $frozenNow = now();
        $this->travelTo($frozenNow);

        $result = loginService(defaultTtl: 60, rememberTtl: 1440)
            ->login(loginDto('user@example.com', 'secret123', remember: false));

        expect($result->expiresAt)
            ->equalTo($frozenNow->addMinutes(60))
            ->toBeTrue();
    });

    it('issues a long-lived token when remember is true', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);
        activeUser($tenant, 'user@example.com', 'secret123');

        $frozenNow = now();
        $this->travelTo($frozenNow);

        $result = loginService(defaultTtl: 60, rememberTtl: 1440)
            ->login(loginDto('user@example.com', 'secret123', remember: true));

        expect($result->expiresAt)->equalTo($frozenNow->addMinutes(1440))->toBeTrue();
    });

    it('names the token after the given device', function () {
        $tenant = Tenant::factory()->create();
        tenantManager()->setTenant($tenant);
        $user = activeUser($tenant, 'user@example.com', 'secret123');

        loginService()->login(loginDto('user@example.com', 'secret123', deviceName: 'iphone-15'));

        expect($user->tokens()->latest()->first()->name)->toBe('iphone-15');
    });
});