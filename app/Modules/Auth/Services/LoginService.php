<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\AuthenticatedUserDTO;
use App\Modules\Auth\DTOs\CredentialsDTO;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Exceptions\AuthException;
use App\Modules\Shared\Exceptions\TenantException;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Support\Facades\Hash;

class LoginService
{

    public function __construct(
        private TenantManager $tenantManager,
        private int $defaultTokenTotalMinutes = 720,      // 12h sin "remember"
        private int $rememberTokenTotalMinutes = 43_200,   // 30 días con "remember"
    ) {
    }

    public function login(CredentialsDTO $dto): AuthenticatedUserDTO
    {
        $user = $this->authenticate($dto);

        // now() devuelve CarbonImmutable porque Date::use(CarbonImmutable::class)
        // ya está configurado en AppServiceProvider.
        $expiresAt = now()->addMinutes(
            $dto->remember ? $this->rememberTokenTotalMinutes : $this->defaultTokenTotalMinutes
        );

        $token = $user
            ->createToken($dto->deviceName, ['*'], $expiresAt)
            ->plainTextToken;

        return new AuthenticatedUserDTO($user, $token, $expiresAt);
    }

    private function authenticate(CredentialsDTO $dto): User
    {
        if (!$this->tenantManager->hasTenant()) {
            throw TenantException::tenantHeaderMissing();
        }
        $user = User::query()
            ->where('tenant_id', $this->tenantManager->getTenantId())
            ->where("email", $dto->email)
            ->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw AuthException::invalidCredentialsException();
        }

        if ($user->status !== UserStatus::ACTIVE) {
            throw AuthException::userIsNotActive();
        }
        return $user;
    }

}
