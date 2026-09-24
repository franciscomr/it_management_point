<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\DTOs\CredentialsDTO;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\Exceptions\AuthException;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\AuthenticatedUserResource;
use App\Modules\Auth\Services\LoginService;
use App\Modules\Auth\Services\LoginThrottleService;
use App\Modules\Shared\Http\Resources\UserResource;
use App\Modules\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;


class AuthController extends Controller
{
    public function __construct(
        private LoginService $loginService,
        private LoginThrottleService $loginThrottleService
    ) {
    }
    public function login(LoginRequest $request): JsonResponse
    {
        $this->loginThrottleService->ensureIsNotRateLimited($request);
        $dto = CredentialsDTO::fromRequest($request);
        try {
            $authenticatedUser = $this->loginService->login($dto);
        } catch (AuthException $e) {
            $this->loginThrottleService->hit($request);
            throw $e;
        }
        $this->loginThrottleService->clear($request);

        return ApiResponse::success(new AuthenticatedUserResource($authenticatedUser), message: 'Login successful');
    }
}
