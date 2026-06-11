<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ApiResponses;

    /** Role inti yang tidak boleh dihapus. */
    private array $coreRoles = ['admin', 'kasir'];

    public function index(Request $request): JsonResponse
    {
        $query = Role::query()
            ->with('permissions')
            ->withCount(['permissions', 'users'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->orderBy('name');

        if ($request->boolean('all')) {
            return response()->json(['data' => RoleResource::collection($query->get())]);
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), RoleResource::class);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->validated()['name'], 'guard_name' => 'web']);
        $role->syncPermissions($request->validated()['permissions'] ?? []);

        return $this->respondCreated(new RoleResource($role->load('permissions')), 'Role berhasil disimpan');
    }

    public function show(Role $role): JsonResponse
    {
        return $this->respondResource(new RoleResource($role->load('permissions')->loadCount('users')));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $role->update(['name' => $data['name']]);
        }
        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->respondResource(new RoleResource($role->load('permissions')), 'Role berhasil diperbarui');
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, $this->coreRoles, true)) {
            return $this->respondMessage('Role inti tidak dapat dihapus.', 422);
        }
        if ($role->users()->exists()) {
            return $this->respondMessage('Role masih dipakai pengguna dan tidak dapat dihapus.', 422);
        }

        $role->delete();

        return $this->respondMessage('Role berhasil dihapus');
    }

    /** Daftar seluruh permission (untuk checkbox di form role). */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->pluck('name');

        return response()->json(['data' => $permissions]);
    }
}
