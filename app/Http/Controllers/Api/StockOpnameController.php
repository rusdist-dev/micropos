<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Http\Requests\UpdateStockOpnameCountsRequest;
use App\Http\Resources\StockOpnameResource;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly StockOpnameService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockOpname::query()
            ->with('user')
            ->withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), StockOpnameResource::class);
    }

    public function store(StoreStockOpnameRequest $request): JsonResponse
    {
        $opname = $this->service->createDraft($request->validated(), $request->user());

        return $this->respondCreated(new StockOpnameResource($opname), 'Sesi opname dibuat');
    }

    public function show(StockOpname $stockOpname): JsonResponse
    {
        return $this->respondResource(new StockOpnameResource($stockOpname->load('items.product', 'user')));
    }

    public function update(UpdateStockOpnameCountsRequest $request, StockOpname $stockOpname): JsonResponse
    {
        $opname = $this->service->updateCounts($stockOpname, $request->validated());

        return $this->respondResource(new StockOpnameResource($opname->load('items.product')), 'Hitungan disimpan');
    }

    public function finalize(Request $request, StockOpname $stockOpname): JsonResponse
    {
        $opname = $this->service->finalize($stockOpname, $request->user());

        return $this->respondResource(new StockOpnameResource($opname->load('items.product')), 'Opname difinalisasi & stok tersinkron');
    }
}
