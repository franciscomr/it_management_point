<?php

namespace App\Modules\Shared\Exceptions;

use Exception;

abstract class ApiException extends Exception
{
    protected function __construct(
        string $message,
        protected readonly int $status,
        protected readonly string $errorCode,
        protected readonly array $errors = [],
        protected readonly array $meta = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => $this->errors(),
            'meta' => $this->meta(),
        ];
    }
}
