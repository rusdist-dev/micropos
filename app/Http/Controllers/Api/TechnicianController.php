<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTechnicianRequest;
use App\Http\Requests\UpdateTechnicianRequest;
use App\Http\Resources\TechnicianResource;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $technicians = Technician::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->respondPaginated($technicians, TechnicianResource::class);
    }

    public function store(StoreTechnicianRequest $request): JsonResponse
    {
        $technician = Technician::create($request->validated());

        return $this->respondCreated(new TechnicianResource($technician), 'Teknisi berhasil disimpan');
    }

    public function show(Technician $technician): JsonResponse
    {
        return $this->respondResource(new TechnicianResource($technician));
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician): JsonResponse
    {
        $technician->update($request->validated());

        return $this->respondResource(new TechnicianResource($technician), 'Teknisi berhasil diperbarui');
    }

    public function destroy(Technician $technician): JsonResponse
    {
        $technician->delete();

        return $this->respondMessage('Teknisi berhasil dihapus');
    }
}
