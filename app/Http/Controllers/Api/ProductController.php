<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProductImportTemplateExport;
use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Imports\ProductsRawImport;
use App\Models\Product;
use App\Services\ProductImportService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ProductService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['prices' => fn ($q) => $q->with('priceType')->orderBy('id'), 'category'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->string('brand')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            // Ambil produk spesifik berdasarkan daftar id (mis. memuat stok item order servis).
            ->when($request->filled('ids'), fn ($q) => $q->whereIn('id', array_filter(array_map('intval', explode(',', (string) $request->query('ids'))))))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->respondPaginated($products, ProductResource::class);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated(), $request->file('image'));

        return $this->respondCreated(new ProductResource($product), 'Produk berhasil disimpan');
    }

    public function show(Product $product): JsonResponse
    {
        return $this->respondResource(new ProductResource($product->load(['prices', 'category'])));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->service->update($product, $request->validated(), $request->file('image'));

        return $this->respondResource(new ProductResource($product), 'Produk berhasil diperbarui');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->service->delete($product);

        return $this->respondMessage('Produk berhasil dihapus');
    }

    /** Daftar merek unik (untuk filter dropdown, tanpa menarik seluruh produk). */
    public function brands(): JsonResponse
    {
        $brands = Product::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        return response()->json(['data' => $brands]);
    }

    /** Unduh template Excel import produk (kolom tipe harga mengikuti master). */
    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new ProductImportTemplateExport, 'template-import-produk.xlsx');
    }

    /** Import produk dari file Excel (1 sheet). */
    public function import(Request $request, ProductImportService $service): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.extensions' => 'Format harus .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $reader = new ProductsRawImport;
        Excel::import($reader, $request->file('file'));

        $result = $service->import($reader->rows);

        $failedCount = count($result['failed']);
        $message = "Impor selesai: {$result['created']} ditambahkan, {$result['updated']} diperbarui"
            . ($failedCount ? ", {$failedCount} gagal" : '') . '.';

        return response()->json(['message' => $message, 'data' => $result]);
    }

    /** Riwayat pergerakan stok (kartu stok) untuk satu produk. */
    public function stockMovements(Request $request, Product $product): JsonResponse
    {
        $movements = $product->stockMovements()
            ->with('user')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->respondPaginated($movements, StockMovementResource::class);
    }
}
