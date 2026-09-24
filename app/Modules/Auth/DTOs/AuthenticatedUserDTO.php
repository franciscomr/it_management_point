<?php

namespace App\Modules\Auth\DTOs;

use App\Models\User;
use DateTimeInterface;
final readonly class AuthenticatedUserDTO
{
    public function __construct(
        public User $user,
        public string $token,
        public ?DateTimeInterface $expiresAt = null,
    ) {
    }
}
