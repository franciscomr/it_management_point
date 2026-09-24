<?php

namespace App\Modules\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
/**
 * Formato estándar de respuesta exitosa de la API.
 *
 * El shape del envelope es fijo y simétrico con ApiException::toArray()
 * (ver App\Modules\Shared\Exceptions\ApiException): 
 * Success  siempre lleva {success, message, data, meta}
 * Exception siempre lleva {success, message, code, errors, meta}
 * juntos forman el contrato único que consume el cliente.
 */
final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => self::transformData($data),
            // Meta está siempre presente (nunca condicional)
            'meta' => $meta,
        ], $status);
    }

    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            status: 201
        );
    }

    public static function updated(
        mixed $data = null,
        string $message = 'Resource updated successfully'
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message
        );
    }

    public static function deleted(
        string $message = 'Resource deleted successfully'
    ): JsonResponse {
        // 200 con envelope en vez de 204:  — 204 no debería llevar body por spec HTTP,
        // todos cliente de esta API espera el envelope estándar en cada respuesta
        return self::success(
            data: null,
            message: $message
        );
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Resources retrieved successfully'
    ): JsonResponse {
        // El ResourceCollection se construye aquí, a partir del MISMO
        // paginator que arma la metadata — antes se recibían por
        // separado y nada garantizaba que vinieran del mismo origen.
        return self::success(
            data: $resourceClass::collection($paginator)->resolve(),
            message: $message,
            meta: ['pagination' => ApiPagination::make($paginator)],
        );
    }

    protected static function transformData(mixed $data): mixed
    {
        // Falla ruidosamente en vez de silenciosamente: sin esto, pasar
        // un paginator directo a success() produce el shape NATIVO de
        // Laravel (via Arrayable), distinto al de ApiPagination — dos
        // endpoints paginados terminarían con dos formatos distintos
        // sin ningún aviso.
        if ($data instanceof LengthAwarePaginator) {
            throw new \LogicException(
                'Use ApiResponse::paginated() for paginated data, not success().'
            );
        }

        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        return $data;
    }

}
