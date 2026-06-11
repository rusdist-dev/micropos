<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\CustomerType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $customers = CustomerType::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->respondPaginated($customers, CustomerResource::class);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = CustomerType::create($request->validated());

        return $this->respondCreated(new CustomerResource($customer), 'Pelanggan berhasil disimpan');
    }

    public function show(CustomerType $customer): JsonResponse
    {
        return $this->respondResource(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, CustomerType $customer): JsonResponse
    {
        $customer->update($request->validated());

        return $this->respondResource(new CustomerResource($customer), 'Pelanggan berhasil diperbarui');
    }

    public function destroy(CustomerType $customer): JsonResponse
    {
        $customer->delete();

        return $this->respondMessage('Pelanggan berhasil dihapus');
    }
}
