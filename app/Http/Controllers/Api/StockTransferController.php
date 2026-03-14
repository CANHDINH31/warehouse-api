<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Stock Transfers",
 *     description="Điều chuyển kho"
 * )
 */
class StockTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StockTransfer::query()
            ->with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name', 'creator:id,name'])
            ->withCount('items');

        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', (int) $request->input('from_warehouse_id'));
        }

        if ($request->filled('to_warehouse_id')) {
            $query->where('to_warehouse_id', (int) $request->input('to_warehouse_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transfer_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transfer_date', '<=', $request->input('to_date'));
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-transfers",
     *     tags={"Stock Transfers"},
     *     summary="Lập phiếu điều chuyển kho",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=201, description="Tạo phiếu điều chuyển thành công")
     * )
     */
    public function store(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['nullable', 'string', 'max:100', 'unique:stock_transfers,code'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'transfer_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $stockTransfer = DB::transaction(function () use ($payload, $request, $inventoryService) {
            foreach ($payload['items'] as $item) {
                $inventoryService->ensureSufficientStock(
                    warehouseId: (int) $payload['from_warehouse_id'],
                    productId: (int) $item['product_id'],
                    requiredQuantity: (float) $item['quantity']
                );
            }

            $code = $payload['code'] ?? $this->generateCode('DC');

            $transfer = StockTransfer::create([
                'code' => $code,
                'from_warehouse_id' => $payload['from_warehouse_id'],
                'to_warehouse_id' => $payload['to_warehouse_id'],
                'transfer_date' => $payload['transfer_date'],
                'note' => $payload['note'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($payload['items'] as $item) {
                $quantity = (float) $item['quantity'];

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                ]);

                $inventoryService->decreaseStock(
                    warehouseId: (int) $payload['from_warehouse_id'],
                    productId: (int) $item['product_id'],
                    quantity: $quantity,
                    movementType: 'transfer_out',
                    referenceCode: $code,
                    unitCost: null,
                    createdBy: $request->user()?->id,
                    note: $payload['note'] ?? null,
                    transactedAt: $payload['transfer_date']
                );

                $inventoryService->increaseStock(
                    warehouseId: (int) $payload['to_warehouse_id'],
                    productId: (int) $item['product_id'],
                    quantity: $quantity,
                    movementType: 'transfer_in',
                    referenceCode: $code,
                    unitCost: null,
                    createdBy: $request->user()?->id,
                    note: $payload['note'] ?? null,
                    transactedAt: $payload['transfer_date']
                );
            }

            return $transfer->load([
                'fromWarehouse:id,code,name',
                'toWarehouse:id,code,name',
                'creator:id,name',
                'items.product:id,code,name,unit',
            ]);
        });

        return response()->json([
            'message' => 'Lập phiếu điều chuyển thành công.',
            'data' => $stockTransfer,
        ], 201);
    }

    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $stockTransfer->load([
            'fromWarehouse:id,code,name,address',
            'toWarehouse:id,code,name,address',
            'creator:id,name',
            'items.product:id,code,name,unit',
        ]);

        return response()->json([
            'data' => $stockTransfer,
        ]);
    }

    public function update(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        return response()->json([
            'message' => 'Không hỗ trợ cập nhật phiếu điều chuyển đã tạo.',
        ], 405);
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        return response()->json([
            'message' => 'Không hỗ trợ xóa phiếu điều chuyển đã tạo.',
        ], 405);
    }

    private function generateCode(string $prefix): string
    {
        return $prefix.'-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
    }
}
