<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        if ($request->boolean('all')) {
            return response()->json(['data' => SupplierResource::collection($query->get())]);
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), SupplierResource::class);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return $this->respondCreated(new SupplierResource($supplier), 'Pemasok berhasil disimpan');
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->respondResource(new SupplierResource($supplier));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        return $this->respondResource(new SupplierResource($supplier), 'Pemasok berhasil diperbarui');
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->supplies()->exists()) {
            return $this->respondMessage('Pemasok memiliki riwayat supply dan tidak dapat dihapus. Nonaktifkan saja.', 422);
        }

        $supplier->delete();

        return $this->respondMessage('Pemasok berhasil dihapus');
    }
}
