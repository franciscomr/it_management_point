<?php

use App\Modules\Shared\Exceptions\ApiException;

if (!function_exists('assertThrowsApiError')) {
    function assertThrowsApiError(Closure $callback, string $exceptionClass, string $errorCode, int $status): ApiException
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            expect($e)->toBeInstanceOf($exceptionClass);

            expect(method_exists($e, 'errorCode') ? $e->errorCode() : null)->toBe($errorCode);
            expect(method_exists($e, 'status') ? $e->status() : null)->toBe($status);
            if ($e instanceof ApiException) {
                return $e;
            }
        }

        throw new \RuntimeException("Expected {$exceptionClass} was not thrown.");
    }
}
