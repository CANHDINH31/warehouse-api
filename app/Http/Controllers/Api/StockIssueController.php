<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockIssue;
use App\Models\StockIssueItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Stock Issues",
 *     description="Quản lý xuất kho"
 * )
 */
class StockIssueController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/stock-issues",
     *     tags={"Stock Issues"},
     *     summary="Danh sách phiếu xuất kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="warehouse_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Danh sách phiếu xuất")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockIssue::query()->with(['warehouse:id,code,name', 'creator:id,name'])->withCount('items');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->input('to_date'));
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-issues",
     *     tags={"Stock Issues"},
     *     summary="Lập phiếu xuất kho",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true),
     *     @OA\Response(response=201, description="Tạo phiếu xuất thành công"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ hoặc không đủ tồn kho")
     * )
     */
    public function store(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['nullable', 'string', 'max:100', 'unique:stock_issues,code'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'issue_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $stockIssue = DB::transaction(function () use ($payload, $request, $inventoryService) {
            foreach ($payload['items'] as $item) {
                $inventoryService->ensureSufficientStock(
                    warehouseId: (int) $payload['warehouse_id'],
                    productId: (int) $item['product_id'],
                    requiredQuantity: (float) $item['quantity']
                );
            }

            $code = $payload['code'] ?? $this->generateCode('PX');

            $issue = StockIssue::create([
                'code' => $code,
                'warehouse_id' => $payload['warehouse_id'],
                'issue_date' => $payload['issue_date'],
                'note' => $payload['note'] ?? null,
                'created_by' => $request->user()?->id,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($payload['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $lineTotal = round($quantity * $unitPrice, 2);

                StockIssueItem::create([
                    'stock_issue_id' => $issue->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $inventoryService->decreaseStock(
                    warehouseId: (int) $payload['warehouse_id'],
                    productId: (int) $item['product_id'],
                    quantity: $quantity,
                    movementType: 'issue',
                    referenceCode: $code,
                    unitCost: $unitPrice,
                    createdBy: $request->user()?->id,
                    note: $payload['note'] ?? null,
                    transactedAt: $payload['issue_date']
                );

                $totalAmount += $lineTotal;
            }

            $issue->update([
                'total_amount' => round($totalAmount, 2),
            ]);

            return $issue->load([
                'warehouse:id,code,name',
                'creator:id,name',
                'items.product:id,code,name,unit',
            ]);
        });

        return response()->json([
            'message' => 'Lập phiếu xuất thành công.',
            'data' => $stockIssue,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stock-issues/{stockIssue}",
     *     tags={"Stock Issues"},
     *     summary="Chi tiết phiếu xuất kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="stockIssue", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Chi tiết phiếu xuất"),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function show(StockIssue $stockIssue): JsonResponse
    {
        $stockIssue->load([
            'warehouse:id,code,name,address',
            'creator:id,name',
            'items.product:id,code,name,unit',
        ]);

        return response()->json([
            'data' => $stockIssue,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/stock-issues/{stockIssue}",
     *     tags={"Stock Issues"},
     *     summary="Cập nhật phiếu xuất kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="stockIssue", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true),
     *     @OA\Response(response=200, description="Cập nhật phiếu xuất thành công"),
     *     @OA\Response(response=422, description="Không đủ tồn kho")
     * )
     */
    public function update(Request $request, StockIssue $stockIssue, InventoryService $inventoryService): JsonResponse
    {
        $payload = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'issue_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $issue = DB::transaction(function () use ($payload, $request, $stockIssue, $inventoryService) {
            // Hoàn tác tồn kho từ items cũ (trả lại hàng về kho)
            foreach ($stockIssue->items as $oldItem) {
                $inventoryService->increaseStock(
                    warehouseId: $stockIssue->warehouse_id,
                    productId: $oldItem->product_id,
                    quantity: (float) $oldItem->quantity,
                    movementType: 'issue_cancel',
                    referenceCode: $stockIssue->code,
                    createdBy: $request->user()?->id,
                    note: 'Huỷ do cập nhật phiếu ' . $stockIssue->code,
                );
            }

            $stockIssue->items()->delete();

            $stockIssue->update([
                'warehouse_id' => $payload['warehouse_id'],
                'issue_date' => $payload['issue_date'],
                'note' => $payload['note'] ?? null,
                'total_amount' => 0,
            ]);

            // Kiểm tra tồn đủ cho items mới
            foreach ($payload['items'] as $item) {
                $inventoryService->ensureSufficientStock(
                    warehouseId: (int) $payload['warehouse_id'],
                    productId: (int) $item['product_id'],
                    requiredQuantity: (float) $item['quantity'],
                );
            }

            $totalAmount = 0;

            foreach ($payload['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $lineTotal = round($quantity * $unitPrice, 2);

                StockIssueItem::create([
                    'stock_issue_id' => $stockIssue->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $inventoryService->decreaseStock(
                    warehouseId: (int) $payload['warehouse_id'],
                    productId: (int) $item['product_id'],
                    quantity: $quantity,
                    movementType: 'issue',
                    referenceCode: $stockIssue->code,
                    unitCost: $unitPrice,
                    createdBy: $request->user()?->id,
                    note: $payload['note'] ?? null,
                    transactedAt: $payload['issue_date'],
                );

                $totalAmount += $lineTotal;
            }

            $stockIssue->update(['total_amount' => round($totalAmount, 2)]);

            return $stockIssue->load([
                'warehouse:id,code,name',
                'creator:id,name',
                'items.product:id,code,name,unit',
            ]);
        });

        return response()->json([
            'message' => 'Cập nhật phiếu xuất thành công.',
            'data' => $issue,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/stock-issues/{stockIssue}",
     *     tags={"Stock Issues"},
     *     summary="Xoá phiếu xuất kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="stockIssue", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xoá phiếu xuất thành công"),
     *     @OA\Response(response=422, description="Không đủ tồn kho để hoàn trả")
     * )
     */
    public function destroy(StockIssue $stockIssue, InventoryService $inventoryService): JsonResponse
    {
        DB::transaction(function () use ($stockIssue, $inventoryService) {
            foreach ($stockIssue->items as $item) {
                $inventoryService->increaseStock(
                    warehouseId: $stockIssue->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    movementType: 'issue_cancel',
                    referenceCode: $stockIssue->code,
                    note: 'Xoá phiếu xuất ' . $stockIssue->code,
                );
            }

            $stockIssue->items()->delete();
            $stockIssue->delete();
        });

        return response()->json([
            'message' => 'Đã xoá phiếu xuất ' . $stockIssue->code . '.',
        ]);
    }

    private function generateCode(string $prefix): string
    {
        return $prefix.'-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
    }
}
