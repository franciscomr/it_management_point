<?php

use App\Modules\Auth\Exceptions\AuthException;
use App\Modules\Auth\Services\LoginThrottleService;
use App\Modules\Shared\Models\Tenant;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;


uses(Tests\TestCase::class, RefreshDatabase::class);

function tenantManager(): TenantManager
{
    return app(TenantManager::class);
}

function throttleService(int $maxAttempts = 5, int $decaySeconds = 60): LoginThrottleService
{
    return new LoginThrottleService(tenantManager(), $maxAttempts, $decaySeconds);
}

function loginRequest(string $email, string $ip = '127.0.0.1'): Request
{
    return Request::create('/api/v1/login', 'POST', ['email' => $email], [], [], [
        'REMOTE_ADDR' => $ip,
    ]);
}

describe('LoginThrottleService', function () {
    it('allows attempts under the limit', function () {
        tenantManager()->setTenant(Tenant::factory()->create());

        $service = throttleService(maxAttempts: 3, decaySeconds: 60);
        $request = loginRequest(fake()->unique()->safeEmail());

        $service->hit($request);
        $service->hit($request);

        expect(fn() => $service->ensureIsNotRateLimited($request))
            ->not->toThrow(AuthException::class);
    });

    it('throws after reaching the max attempts', function () {
        tenantManager()->setTenant(Tenant::factory()->create());

        $service = throttleService(maxAttempts: 2, decaySeconds: 60);
        $request = loginRequest(fake()->unique()->safeEmail());

        $service->hit($request);
        $service->hit($request);

        $exception = assertThrowsApiError(
            fn() => $service->ensureIsNotRateLimited($request),
            AuthException::class,
            'AUTH_TOO_MANY_ATTEMPTS',
            429,
        );

        expect($exception->meta())
            ->toHaveKey('retry_after')
            ->and($exception->meta()['retry_after'])->toBeGreaterThan(0)
            ->and($exception->meta()['retry_after'])->toBeLessThanOrEqual(60);
    });

    it('clear resets the counter', function () {
        tenantManager()->setTenant(Tenant::factory()->create());

        $service = throttleService(maxAttempts: 1, decaySeconds: 60);
        $request = loginRequest(fake()->unique()->safeEmail());

        $service->hit($request);
        $service->clear($request);

        expect(fn() => $service->ensureIsNotRateLimited($request))
            ->not->toThrow(Throwable::class);
    });

    it('scopes the limit per tenant, not just per email and ip', function () {
        // Mismo patrón de regresión que usamos en LoginServiceTest: el
        // throttle debe ser independiente por tenant, aunque el atacante
        // repita el mismo email e IP contra tenants distintos.
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $email = fake()->unique()->safeEmail();
        $ip = '203.0.113.10';
        $service = throttleService(maxAttempts: 1, decaySeconds: 60);

        tenantManager()->setTenant($tenantA);
        $service->hit(loginRequest($email, $ip));

        assertThrowsApiError(
            fn() => $service->ensureIsNotRateLimited(loginRequest($email, $ip)),
            AuthException::class,
            'AUTH_TOO_MANY_ATTEMPTS',
            429,
        );

        tenantManager()->setTenant($tenantB);

        expect(fn() => $service->ensureIsNotRateLimited(loginRequest($email, $ip)))
            ->not->toThrow(Throwable::class);
    });

    it('scopes the limit per email within the same tenant and ip', function () {
        tenantManager()->setTenant(Tenant::factory()->create());

        $ip = '203.0.113.20';
        $service = throttleService(maxAttempts: 1, decaySeconds: 60);

        $service->hit(loginRequest('victim@example.com', $ip));

        expect(fn() => $service->ensureIsNotRateLimited(loginRequest('someone-else@example.com', $ip)))
            ->not->toThrow(Throwable::class);
    });

    it('scopes the limit per ip within the same tenant and email', function () {
        tenantManager()->setTenant(Tenant::factory()->create());

        $email = fake()->unique()->safeEmail();
        $service = throttleService(maxAttempts: 1, decaySeconds: 60);

        $service->hit(loginRequest($email, '203.0.113.30'));

        expect(fn() => $service->ensureIsNotRateLimited(loginRequest($email, '203.0.113.31')))
            ->not->toThrow(Throwable::class);
    });
});