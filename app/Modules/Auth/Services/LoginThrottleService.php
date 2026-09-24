<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Exceptions\AuthException;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginThrottleService
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected int $maxAttempts = 5,
        protected int $decaySeconds = 60
    ) {
    }

    public function ensureIsNotRateLimited(Request $request): void
    {
        $key = $this->throttleKey($request);
        if (!RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return;
        }
        throw AuthException::tooManyAttemptsException(RateLimiter::availableIn($key));
    }
    public function hit(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), $this->decaySeconds);
    }


    public function clear(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    public function throttleKey(Request $request): string
    {
        return sha1(implode('|', [
            $this->tenantManager->getTenantId() ?? 'no-tenant',
            strtolower((string) $request->input('email')),
            $request->ip(),
        ]));
    }
}
