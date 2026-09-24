<?php

namespace App\Modules\Auth\DTOs;

use App\Modules\Auth\Http\Requests\LoginRequest;

final readonly class CredentialsDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
        public string $deviceName = 'web',
    ) {
    }

    public static function fromRequest(LoginRequest $request): self
    {
        $data = $request->validated();

        return new self(
            email: $data['email'],
            password: $data['password'],
            remember: (bool) ($data['remember'] ?? false),
            deviceName: $data['device_name'] ?? $data['deviceName'] ?? 'web',
        );
    }
}
