<?php

namespace App\Http\Controllers\Api;

use App\Exports\TransactionsExport;
use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly TransactionService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request)
            ->with(['kasir', 'customer'])
            ->withCount('items')
            ->latest();

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), TransactionResource::class);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $this->service->create($request->validated(), $request->user());

        return $this->respondCreated(new TransactionResource($transaction), 'Transaksi berhasil disimpan');
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if (! $request->user()->can('transactions.view-all') && $transaction->kasir_id !== $request->user()->id) {
            return $this->respondMessage('Anda tidak berhak melihat transaksi ini.', 403);
        }

        return $this->respondResource(new TransactionResource($transaction->load(['items', 'kasir', 'customer'])));
    }

    /** Daftar kasir untuk filter (hanya pengguna yang punya transaksi-create / admin). */
    public function kasirs(): JsonResponse
    {
        $users = User::query()
            ->whereHas('roles')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return response()->json(['data' => $users]);
    }

    /** Export Excel 2 sheet sesuai filter aktif (search, tanggal, kasir). */
    public function export(Request $request): BinaryFileResponse
    {
        $transactions = $this->filteredQuery($request)
            ->with(['kasir', 'customer', 'items.product'])
            ->latest()
            ->get();

        $filename = 'riwayat-transaksi-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new TransactionsExport($transactions), $filename);
    }

    /**
     * Query transaksi terfilter bersama (dipakai index & export):
     * batasan kepemilikan (kasir non view-all), search, rentang tanggal, filter kasir.
     */
    private function filteredQuery(Request $request): Builder
    {
        $user = $request->user();

        return Transaction::query()
            ->when(
                ! $user->can('transactions.view-all'),
                fn ($q) => $q->where('kasir_id', $user->id)
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")));
            })
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('kasir_id', $request->integer('kasir_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')));
    }
}
