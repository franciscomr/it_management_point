<?php

namespace App\Modules\Shared\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class TenantException extends ApiException
{
    public static function tenantHeaderMissing(): self
    {
        return new self(
            message: 'The X-Tenant-ID header is required.',
            status: Response::HTTP_BAD_REQUEST,
            errorCode: 'TENANT_MISSING',
        );
    }

    public static function tenantNotFound(string $id): self
    {
        return new self(
            message: sprintf('Asset with ID [%s] not found.', $id),
            status: Response::HTTP_NOT_FOUND,
            errorCode: 'TENANT_NOT_FOUND',
            meta: ['tenant_id' => $id,]
        );
    }

    public static function tenantSuspended(): self
    {
        return new self(
            message: 'This tenant is suspended.',
            status: Response::HTTP_FORBIDDEN,
            errorCode: 'TENANT_SUSPENDED',
        );
    }

    public static function tenantMismatch(): self
    {
        return new self(
            message: 'The authenticated user does not belong to the resolved tenant.',
            status: Response::HTTP_FORBIDDEN,
            errorCode: 'TENANT_MISMATCH',
        );
    }
}
