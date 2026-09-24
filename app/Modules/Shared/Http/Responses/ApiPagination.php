<?php

namespace App\Modules\Shared\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ApiPagination
{
    public static function make(
        LengthAwarePaginator $paginator
    ): array {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),

            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ];
    }
}
