<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->respondPaginated($services, ServiceResource::class);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        return $this->respondCreated(new ServiceResource($service), 'Jasa berhasil disimpan');
    }

    public function show(Service $service): JsonResponse
    {
        return $this->respondResource(new ServiceResource($service));
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        return $this->respondResource(new ServiceResource($service), 'Jasa berhasil diperbarui');
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return $this->respondMessage('Jasa berhasil dihapus');
    }
}
