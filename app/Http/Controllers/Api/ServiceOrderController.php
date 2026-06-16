<?php

namespace App\Http\Controllers\Api;

use App\Exports\ServiceOrdersExport;
use App\Http\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelServiceOrderRequest;
use App\Http\Requests\CompleteServiceOrderRequest;
use App\Http\Requests\StoreServiceOrderRequest;
use App\Http\Requests\UpdateServiceOrderRequest;
use App\Http\Resources\ServiceOrderResource;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServiceOrderController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ServiceOrderService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request)
            ->with(['customer', 'technician', 'operator'])
            ->withCount('items')
            ->latest();

        return $this->respondPaginated($query->paginate($request->integer('per_page', 15)), ServiceOrderResource::class);
    }

    public function store(StoreServiceOrderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->validated(), $request->user());

        return $this->respondCreated(new ServiceOrderResource($order), 'Order servis berhasil dibuat');
    }

    public function show(ServiceOrder $serviceOrder): JsonResponse
    {
        return $this->respondResource(
            new ServiceOrderResource($serviceOrder->load(['items', 'customer', 'technician', 'operator']))
        );
    }

    public function update(UpdateServiceOrderRequest $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $order = $this->service->update($serviceOrder, $request->validated(), $request->user());

        return $this->respondResource(new ServiceOrderResource($order), 'Order servis diperbarui');
    }

    public function complete(CompleteServiceOrderRequest $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $order = $this->service->complete($serviceOrder, (float) $request->input('payment', 0));

        return $this->respondResource(new ServiceOrderResource($order), 'Servis ditandai selesai');
    }

    public function cancel(CancelServiceOrderRequest $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $data = $request->validated();
        $fee = array_key_exists('cancellation_fee', $data) && $data['cancellation_fee'] !== null
            ? (float) $data['cancellation_fee']
            : null;
        $order = $this->service->cancel($serviceOrder, $data['cancel_note'], $fee, $request->user());

        return $this->respondResource(new ServiceOrderResource($order), 'Servis dibatalkan & stok bahan dikembalikan');
    }

    /** Edit ulang biaya pembatalan (dinamis) pada order yang sudah Batal. */
    public function updateCancellation(CancelServiceOrderRequest $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $data = $request->validated();
        $fee = array_key_exists('cancellation_fee', $data) && $data['cancellation_fee'] !== null
            ? (float) $data['cancellation_fee']
            : null;
        $order = $this->service->updateCancellation($serviceOrder, $data['cancel_note'], $fee);

        return $this->respondResource(new ServiceOrderResource($order), 'Biaya pembatalan diperbarui');
    }

    /** Export Excel 2 sheet sesuai filter aktif (search, status, teknisi, tanggal). */
    public function export(Request $request): BinaryFileResponse
    {
        $orders = $this->filteredQuery($request)
            ->with(['customer', 'technician', 'operator', 'items'])
            ->latest()
            ->get();

        $filename = 'order-servis-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new ServiceOrdersExport($orders), $filename);
    }

    /**
     * Query terfilter: search invoice/pelanggan, status servis & bayar, teknisi, rentang tanggal.
     */
    private function filteredQuery(Request $request): Builder
    {
        return ServiceOrder::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($sub) => $sub->where('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")));
            })
            ->when($request->filled('service_status'), fn ($q) => $q->where('service_status', $request->string('service_status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('technician_id'), fn ($q) => $q->where('technician_id', $request->integer('technician_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')));
    }
}
