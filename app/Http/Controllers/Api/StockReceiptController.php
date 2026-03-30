<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Stock Receipts",
 *     description="Quản lý nhập kho"
 * )
 */
class StockReceiptController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/stock-receipts",
     *     tags={"Stock Receipts"},
     *     summary="Danh sách phiếu nhập kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="warehouse_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Danh sách phiếu nhập")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockReceipt::query()->with(['warehouse:id,code,name', 'creator:id,name'])->withCount('items');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('receipt_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('receipt_date', '<=', $request->input('to_date'));
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-receipts",
     *     tags={"Stock Receipts"},
     *     summary="Lập phiếu nhập kho",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true),
     *     @OA\Response(response=201, description="Tạo phiếu nhập thành công"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['nullable', 'string', 'max:100', 'unique:stock_receipts,code'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'receipt_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $receipt = DB::transaction(function () use ($payload, $request, $inventoryService) {
            $code = $payload['code'] ?? $this->generateCode('PN');

            $stockReceipt = StockReceipt::create([
                'code' => $code,
                'warehouse_id' => $payload['warehouse_id'],
                'receipt_date' => $payload['receipt_date'],
                'note' => $payload['note'] ?? null,
                'created_by' => $request->user()?->id,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($payload['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $lineTotal = round($quantity * $unitCost, 2);

                StockReceiptItem::create([
                    'stock_receipt_id' => $stockReceipt->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $inventoryService->increaseStock(
                    warehouseId: (int) $payload['warehouse_id'],
                    productId: (int) $item['product_id'],
                    quantity: $quantity,
                    movementType: 'receipt',
                    referenceCode: $code,
                    unitCost: $unitCost,
                    createdBy: $request->user()?->id,
                    note: $payload['note'] ?? null,
                    transactedAt: $payload['receipt_date']
                );

                $totalAmount += $lineTotal;
            }

            $stockReceipt->update([
                'total_amount' => round($totalAmount, 2),
            ]);

            return $stockReceipt->load([
                'warehouse:id,code,name',
                'creator:id,name',
                'items.product:id,code,name,unit',
            ]);
        });

        return response()->json([
            'message' => 'Lập phiếu nhập thành công.',
            'data' => $receipt,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stock-receipts/{stockReceipt}",
     *     tags={"Stock Receipts"},
     *     summary="Chi tiết phiếu nhập kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="stockReceipt", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Chi tiết phiếu nhập"),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function show(StockReceipt $stockReceipt): JsonResponse
    {
        $stockReceipt->load([
            'warehouse:id,code,name,address',
            'creator:id,name',
            'items.product:id,code,name,unit',
        ]);

        return response()->json([
            'data' => $stockReceipt,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/stock-receipts/{stockReceipt}",
     *     tags={"Stock Receipts"},
     *     summary="Cập nhật phiếu nhập kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="stockReceipt", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cập nhật phiếu nhập thành công")
     * )
     */
    public function update(Request $request, StockReceipt $stockReceipt, InventoryService $inventoryService): JsonResponse
    {
        $payload = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'receipt_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $receipt = DB::transaction(function () use ($payload, $request, $stockReceipt, $inventoryService) {
            // Hoàn tác tồn kho từ items cũ
            foreach ($stockReceipt->items as $oldItem) {
                $inventoryService->decreaseStock(
                    warehouseId: $stockReceipt->warehouse_id,
                    productId: $oldItem->product_id,
                    quantity: (float) $oldItem->quantity,
                    movementType: 'receipt_cancel',
                    referenceCode: $stockReceipt->code,
                    createdBy: $request->user()?->id,
                    note: 'Huỷ do cập nhật phiếu ' . $stockReceipt->code,
                );
            }

            $stockReceipt->items()->delete();

            $stockReceipt->update([
                'warehouse_id' => $payload['warehouse_id'],
                'receipt_date' => $payload['receipt_date'],
                'note' => $payload['note'] ?? null,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($payload['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $lineTotal = round($quantity * $unitCost, 2);

                StockReceiptItem::create([
                    'stock_receipt_id' => $stockReceipt->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $inventoryService->increaseStock(
                    warehouseId: (int) $payload['warehouse_id'],
                    productId: (int) $item['product_id'],
                    quantity: $quantity,
                    movementType: 'receipt',
                    referenceCode: $stockReceipt->code,
                    unitCost: $unitCost,
                    createdBy: $request->user()?->id,
                    note: $payload['note'] ?? null,
                    transactedAt: $payload['receipt_date'],
                );

                $totalAmount += $lineTotal;
            }

            $stockReceipt->update(['total_amount' => round($totalAmount, 2)]);

            return $stockReceipt->load([
                'warehouse:id,code,name',
                'creator:id,name',
                'items.product:id,code,name,unit',
            ]);
        });

        return response()->json([
            'message' => 'Cập nhật phiếu nhập thành công.',
            'data' => $receipt,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/stock-receipts/{stockReceipt}",
     *     tags={"Stock Receipts"},
     *     summary="Xoá phiếu nhập kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="stockReceipt", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xoá phiếu nhập thành công"),
     *     @OA\Response(response=422, description="Không đủ tồn kho để hoàn tác")
     * )
     */
    public function destroy(StockReceipt $stockReceipt, InventoryService $inventoryService): JsonResponse
    {
        DB::transaction(function () use ($stockReceipt, $inventoryService) {
            foreach ($stockReceipt->items as $item) {
                $inventoryService->decreaseStock(
                    warehouseId: $stockReceipt->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    movementType: 'receipt_cancel',
                    referenceCode: $stockReceipt->code,
                    note: 'Xoá phiếu nhập ' . $stockReceipt->code,
                );
            }

            $stockReceipt->items()->delete();
            $stockReceipt->delete();
        });

        return response()->json([
            'message' => 'Đã xoá phiếu nhập ' . $stockReceipt->code . '.',
        ]);
    }

    private function generateCode(string $prefix): string
    {
        return $prefix.'-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
    }
}
