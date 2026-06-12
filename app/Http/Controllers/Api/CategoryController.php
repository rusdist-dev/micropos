<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $query = Category::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->withCount('products')
            ->orderBy('name');

        if ($request->boolean('all')) {
            return response()->json(['data' => CategoryResource::collection($query->get())]);
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), CategoryResource::class);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->respondCreated(new CategoryResource($category), 'Kategori berhasil disimpan');
    }

    public function show(Category $category): JsonResponse
    {
        return $this->respondResource(new CategoryResource($category->loadCount('products')));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->respondResource(new CategoryResource($category), 'Kategori berhasil diperbarui');
    }

    public function destroy(Category $category): JsonResponse
    {
        // Produk yang memakai kategori ini akan kehilangan kategori (category_id => null, nullOnDelete).
        $category->delete();

        return $this->respondMessage('Kategori berhasil dihapus');
    }
}
