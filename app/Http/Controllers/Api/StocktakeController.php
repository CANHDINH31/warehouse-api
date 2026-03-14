<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Stocktakes",
 *     description="Kiểm kê kho"
 * )
 */
class StocktakeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Stocktake::query()->with(['warehouse:id,code,name', 'creator:id,name'])->withCount('items');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('checked_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('checked_at', '<=', $request->input('to_date'));
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stocktakes",
     *     tags={"Stocktakes"},
     *     summary="Lập biên bản kiểm kê",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=201, description="Tạo biên bản kiểm kê thành công")
     * )
     */
    public function store(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['nullable', 'string', 'max:100', 'unique:stocktakes,code'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'checked_at' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'apply_adjustment' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.actual_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $stocktake = DB::transaction(function () use ($payload, $request, $inventoryService) {
            $code = $payload['code'] ?? $this->generateCode('KK');
            $applyAdjustment = (bool) ($payload['apply_adjustment'] ?? false);

            $header = Stocktake::create([
                'code' => $code,
                'warehouse_id' => $payload['warehouse_id'],
                'checked_at' => $payload['checked_at'],
                'note' => $payload['note'] ?? null,
                'apply_adjustment' => $applyAdjustment,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($payload['items'] as $item) {
                $inventory = Inventory::query()
                    ->where('warehouse_id', (int) $payload['warehouse_id'])
                    ->where('product_id', (int) $item['product_id'])
                    ->first();

                $systemQty = $inventory ? (float) $inventory->quantity : 0;
                $actualQty = (float) $item['actual_qty'];
                $varianceQty = round($actualQty - $systemQty, 3);

                StocktakeItem::create([
                    'stocktake_id' => $header->id,
                    'product_id' => $item['product_id'],
                    'system_qty' => $systemQty,
                    'actual_qty' => $actualQty,
                    'variance_qty' => $varianceQty,
                ]);

                if ($applyAdjustment && $varianceQty !== 0.0) {
                    $inventoryService->adjustStockToActual(
                        warehouseId: (int) $payload['warehouse_id'],
                        productId: (int) $item['product_id'],
                        actualQuantity: $actualQty,
                        referenceCode: $code,
                        createdBy: $request->user()?->id,
                        note: $payload['note'] ?? 'Điều chỉnh từ kiểm kê',
                        transactedAt: $payload['checked_at']
                    );
                }
            }

            return $header->load([
                'warehouse:id,code,name',
                'creator:id,name',
                'items.product:id,code,name,unit',
            ]);
        });

        return response()->json([
            'message' => 'Lập biên bản kiểm kê thành công.',
            'data' => $stocktake,
        ], 201);
    }

    public function show(Stocktake $stocktake): JsonResponse
    {
        $stocktake->load([
            'warehouse:id,code,name,address',
            'creator:id,name',
            'items.product:id,code,name,unit',
        ]);

        return response()->json([
            'data' => $stocktake,
        ]);
    }

    public function update(Request $request, Stocktake $stocktake): JsonResponse
    {
        return response()->json([
            'message' => 'Không hỗ trợ cập nhật biên bản kiểm kê đã tạo.',
        ], 405);
    }

    public function destroy(Stocktake $stocktake): JsonResponse
    {
        return response()->json([
            'message' => 'Không hỗ trợ xóa biên bản kiểm kê đã tạo.',
        ], 405);
    }

    private function generateCode(string $prefix): string
    {
        return $prefix.'-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
    }
}
