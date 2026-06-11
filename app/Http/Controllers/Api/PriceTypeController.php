<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePriceTypeRequest;
use App\Http\Requests\UpdatePriceTypeRequest;
use App\Http\Resources\PriceTypeResource;
use App\Models\PriceType;
use App\Models\ProductPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceTypeController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $query = PriceType::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%"));
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name');

        // Mendukung pengambilan tanpa pagination (untuk dropdown kasir).
        if ($request->boolean('all')) {
            return response()->json(['data' => PriceTypeResource::collection($query->get())]);
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), PriceTypeResource::class);
    }

    public function store(StorePriceTypeRequest $request): JsonResponse
    {
        $priceType = PriceType::create($request->validated());

        return $this->respondCreated(new PriceTypeResource($priceType), 'Tipe harga berhasil disimpan');
    }

    public function show(PriceType $priceType): JsonResponse
    {
        return $this->respondResource(new PriceTypeResource($priceType));
    }

    public function update(UpdatePriceTypeRequest $request, PriceType $priceType): JsonResponse
    {
        $priceType->update($request->validated());

        return $this->respondResource(new PriceTypeResource($priceType), 'Tipe harga berhasil diperbarui');
    }

    public function destroy(PriceType $priceType): JsonResponse
    {
        $inUse = ProductPrice::where('price_type', $priceType->code)->exists();
        if ($inUse) {
            return $this->respondMessage('Tipe harga sedang dipakai produk dan tidak dapat dihapus. Nonaktifkan saja.', 422);
        }

        $priceType->delete();

        return $this->respondMessage('Tipe harga berhasil dihapus');
    }
}
