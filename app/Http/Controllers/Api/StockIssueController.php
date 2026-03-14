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
     *     @OA\Response(response=201, description="Tạo phiếu xuất thành công")
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

    public function update(Request $request, StockIssue $stockIssue): JsonResponse
    {
        return response()->json([
            'message' => 'Không hỗ trợ cập nhật phiếu xuất đã tạo.',
        ], 405);
    }

    public function destroy(StockIssue $stockIssue): JsonResponse
    {
        return response()->json([
            'message' => 'Không hỗ trợ xóa phiếu xuất đã tạo.',
        ], 405);
    }

    private function generateCode(string $prefix): string
    {
        return $prefix.'-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
    }
}
