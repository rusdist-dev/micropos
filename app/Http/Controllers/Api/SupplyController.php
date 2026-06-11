<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use App\Services\SupplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SupplyService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Supply::query()
            ->with(['supplier', 'user'])
            ->withCount('items')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where('code', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"));
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest();

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), SupplyResource::class);
    }

    public function store(StoreSupplyRequest $request): JsonResponse
    {
        $supply = $this->service->create($request->validated(), $request->user());

        return $this->respondCreated(new SupplyResource($supply), 'Supply berhasil disimpan');
    }

    public function show(Supply $supply): JsonResponse
    {
        return $this->respondResource(new SupplyResource($supply->load(['items.product', 'supplier', 'user'])));
    }
}
