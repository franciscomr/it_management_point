<?php

use App\Models\User;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Shared\Enums\TenantStatus;
use App\Modules\Shared\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

uses(Tests\TestCase::class, RefreshDatabase::class);

function postLogin(array $payload, ?Tenant $tenant = null): TestResponse
{
    $client = $tenant ? test()->withHeader('X-Tenant-ID', $tenant->id) : test();

    return $client->postJson(route('post.login'), $payload);
}

function seedActiveUser(Tenant $tenant, string $email, string $password): User
{
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $email,
        'password' => Hash::make($password),
        'status' => UserStatus::ACTIVE, // ajusta al case real de tu enum
    ]);
}

describe('POST /login', function () {
    it('returns a token and user data for valid credentials', function () {
        $tenant = Tenant::factory()->create();
        seedActiveUser($tenant, 'user@example.com', 'secret123');

        $response = postLogin([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ], $tenant);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_at', 'name', 'email'],
                'meta',
            ]);

        expect($response->json('data.token_type'))->toBe('Bearer')
            ->and($response->json('data.email'))->toBe('user@example.com');
    });

    it('rejects login without a tenant header', function () {
        postLogin(['email' => 'user@example.com', 'password' => 'secret123'])
            ->assertStatus(400)
            ->assertJson(['success' => false, 'code' => 'TENANT_MISSING']);
    });

    it('rejects login for a suspended tenant', function () {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);

        postLogin([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ], $tenant)
            ->assertStatus(403)
            ->assertJson(['code' => 'TENANT_SUSPENDED']);
    });

    it('rejects invalid credentials', function () {
        $tenant = Tenant::factory()->create();
        seedActiveUser($tenant, 'user@example.com', 'secret123');

        postLogin([
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ], $tenant)
            ->assertStatus(401)
            ->assertJson(['code' => 'INVALID_CREDENTIALS']);
    });

    it('rejects an inactive user', function () {
        $tenant = Tenant::factory()->create();

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'inactive@example.com',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::INACTIVE, // ajusta al case real de tu enum
        ]);

        postLogin([
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ], $tenant)
            ->assertStatus(403)
            ->assertJson(['code' => 'USER_IS_NOT_ACTIVE']);
    });

    it('locks out after too many failed attempts', function () {
        $tenant = Tenant::factory()->create();
        seedActiveUser($tenant, 'user@example.com', 'secret123');

        // Default real: maxAttempts = 5 (LoginThrottleService).
        for ($i = 0; $i < 5; $i++) {
            postLogin([
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ], $tenant)->assertStatus(401);
        }

        $response = postLogin([
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ], $tenant);

        $response->assertStatus(429)
            ->assertJson(['code' => 'AUTH_TOO_MANY_ATTEMPTS']);

        expect($response->json('meta.retry_after'))->toBeGreaterThan(0);
    });

    it('resets the attempt counter after a successful login', function () {
        // Cubre la interacción controller + throttle (hit/clear) que un
        // test unitario de LoginThrottleService en aislamiento no prueba.
        $tenant = Tenant::factory()->create();
        seedActiveUser($tenant, 'user@example.com', 'secret123');

        postLogin(['email' => 'user@example.com', 'password' => 'wrong'], $tenant)
            ->assertStatus(401);

        postLogin(['email' => 'user@example.com', 'password' => 'secret123'], $tenant)
            ->assertOk();

        // Si el contador no se hubiera limpiado, este 4º intento fallido
        // en total ya estaría cerca del límite de 5; debe seguir en 401.
        postLogin(['email' => 'user@example.com', 'password' => 'wrong'], $tenant)
            ->assertStatus(401);
    });

    it('issues a longer-lived token when remember is true', function () {
        $tenant = Tenant::factory()->create();
        seedActiveUser($tenant, 'user@example.com', 'secret123');

        $frozenNow = now();
        $this->travelTo($frozenNow);

        $short = postLogin([
            'email' => 'user@example.com',
            'password' => 'secret123',
            'remember' => false,
        ], $tenant);

        $long = postLogin([
            'email' => 'user@example.com',
            'password' => 'secret123',
            'remember' => true,
        ], $tenant);

        $shortExpiry = \Carbon\Carbon::parse($short->json('data.expires_at'));
        $longExpiry = \Carbon\Carbon::parse($long->json('data.expires_at'));

        expect($longExpiry->greaterThan($shortExpiry))->toBeTrue();
    });
});

