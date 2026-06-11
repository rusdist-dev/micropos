<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequest;
use App\Http\Resources\ReturnResource;
use App\Models\ReturnTransaction;
use App\Models\Transaction;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ReturnService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ReturnTransaction::query()
            ->with(['transaction', 'kasir'])
            ->withCount('items')
            ->when(! $user->can('returns.view-all'), fn ($q) => $q->where('kasir_id', $user->id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where('code', 'like', "%{$term}%")
                    ->orWhereHas('transaction', fn ($t) => $t->where('invoice_number', 'like', "%{$term}%"));
            })
            ->latest();

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), ReturnResource::class);
    }

    public function store(StoreReturnRequest $request): JsonResponse
    {
        $return = $this->service->create($request->validated(), $request->user());

        return $this->respondCreated(new ReturnResource($return), 'Retur berhasil diproses');
    }

    public function show(Request $request, ReturnTransaction $return): JsonResponse
    {
        if (! $request->user()->can('returns.view-all') && $return->kasir_id !== $request->user()->id) {
            return $this->respondMessage('Anda tidak berhak melihat retur ini.', 403);
        }

        return $this->respondResource(new ReturnResource($return->load(['items', 'transaction', 'kasir'])));
    }

    /** Daftar item produk yang masih bisa diretur dari sebuah transaksi. */
    public function returnable(Transaction $transaction): JsonResponse
    {
        return response()->json([
            'data' => [
                'transaction' => [
                    'id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'customer_name' => $transaction->customer?->name ?? 'Pelanggan Umum',
                    'created_at' => $transaction->created_at?->toIso8601String(),
                ],
                'items' => $this->service->returnable($transaction),
            ],
        ]);
    }
}
