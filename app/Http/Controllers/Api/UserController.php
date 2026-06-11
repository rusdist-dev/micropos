<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->respondPaginated($users, UserResource::class);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->syncRoles($data['role']);

        return $this->respondCreated(new UserResource($user->load('roles')), 'Pengguna berhasil disimpan');
    }

    public function show(User $user): JsonResponse
    {
        return $this->respondResource(new UserResource($user->load('roles')));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $user->fill(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('is_active', $data)) {
            $user->is_active = $data['is_active'];
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (! empty($data['role'])) {
            $user->syncRoles($data['role']);
        }

        return $this->respondResource(new UserResource($user->load('roles')), 'Pengguna berhasil diperbarui');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return $this->respondMessage('Anda tidak dapat menghapus akun sendiri.', 422);
        }

        $user->delete();

        return $this->respondMessage('Pengguna berhasil dihapus');
    }
}
