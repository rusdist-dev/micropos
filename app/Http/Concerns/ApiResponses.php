<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Helper format respons API seragam (lihat context.md bab 5.2).
 */
trait ApiResponses
{
    /** Respons resource tunggal: { data, message? }. */
    protected function respondResource(JsonResource $resource, ?string $message = null, int $status = 200): JsonResponse
    {
        if ($message !== null) {
            $resource->additional(['message' => $message]);
        }

        return $resource->response()->setStatusCode($status);
    }

    /** Respons resource yang baru dibuat (201). */
    protected function respondCreated(JsonResource $resource, ?string $message = null): JsonResponse
    {
        return $this->respondResource($resource, $message, 201);
    }

    /**
     * Respons koleksi paginated: { data, meta }.
     *
     * @param  class-string<JsonResource>  $resourceClass
     */
    protected function respondPaginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'data' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** Respons hanya pesan (mis. setelah delete). */
    protected function respondMessage(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
