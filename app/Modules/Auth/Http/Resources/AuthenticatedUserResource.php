<?php

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'token_type' => 'Bearer',
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar_url' => $this->user->avatar_url,
        ];
    }
}
