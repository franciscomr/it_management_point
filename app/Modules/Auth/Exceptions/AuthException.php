<?php

namespace App\Modules\Auth\Exceptions;

use App\Modules\Shared\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;


class AuthException extends ApiException
{
    public static function invalidCredentialsException(): self
    {
        return new self(
            message: 'Invalid credentials.',
            status: Response::HTTP_UNAUTHORIZED,
            errorCode: 'INVALID_CREDENTIALS',
        );
    }

    public static function tooManyAttemptsException(int $retryAfterSeconds): self
    {
        return new self(
            message: 'Too many login attempts.',
            status: Response::HTTP_TOO_MANY_REQUESTS,
            errorCode: 'AUTH_TOO_MANY_ATTEMPTS',
            meta: ['retry_after' => $retryAfterSeconds],
        );
    }

    public static function userIsNotActive(): self
    {
        return new self(
            message: 'User is not active.',
            status: Response::HTTP_FORBIDDEN,
            errorCode: 'USER_IS_NOT_ACTIVE',
        );
    }
}
