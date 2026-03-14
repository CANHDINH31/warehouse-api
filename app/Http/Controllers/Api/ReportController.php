<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\StockIssueItem;
use App\Models\StockReceiptItem;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Báo cáo và thống kê"
 * )
 */
class ReportController extends Controller
{
    public function inventoryByWarehouse(Request $request): JsonResponse
    {
        $query = Warehouse::query()->with(['inventories' => function ($inventoryQuery) {
            $inventoryQuery
                ->where('quantity', '>', 0)
                ->with('product:id,code,name,unit,min_stock_alert')
                ->orderByDesc('quantity');
        }]);

        if ($request->filled('warehouse_id')) {
            $query->where('id', (int) $request->input('warehouse_id'));
        }

        $data = $query->get()->map(function (Warehouse $warehouse) {
            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_code' => $warehouse->code,
                'warehouse_name' => $warehouse->name,
                'total_items' => $warehouse->inventories->count(),
                'total_quantity' => round((float) $warehouse->inventories->sum('quantity'), 3),
                'items' => $warehouse->inventories,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function inOutByPeriod(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $receiptQuery = StockReceiptItem::query()
            ->join('stock_receipts', 'stock_receipt_items.stock_receipt_id', '=', 'stock_receipts.id')
            ->whereBetween('stock_receipts.receipt_date', [$payload['from_date'], $payload['to_date']]);

        $issueQuery = StockIssueItem::query()
            ->join('stock_issues', 'stock_issue_items.stock_issue_id', '=', 'stock_issues.id')
            ->whereBetween('stock_issues.issue_date', [$payload['from_date'], $payload['to_date']]);

        if (! empty($payload['warehouse_id'])) {
            $receiptQuery->where('stock_receipts.warehouse_id', (int) $payload['warehouse_id']);
            $issueQuery->where('stock_issues.warehouse_id', (int) $payload['warehouse_id']);
        }

        $totalReceiptQty = (float) $receiptQuery->sum('stock_receipt_items.quantity');
        $totalIssueQty = (float) $issueQuery->sum('stock_issue_items.quantity');

        $receiptByDate = StockReceiptItem::query()
            ->join('stock_receipts', 'stock_receipt_items.stock_receipt_id', '=', 'stock_receipts.id')
            ->whereBetween('stock_receipts.receipt_date', [$payload['from_date'], $payload['to_date']])
            ->when(! empty($payload['warehouse_id']), fn ($query) => $query->where('stock_receipts.warehouse_id', (int) $payload['warehouse_id']))
            ->groupBy('stock_receipts.receipt_date')
            ->orderBy('stock_receipts.receipt_date')
            ->select([
                'stock_receipts.receipt_date as date',
                DB::raw('SUM(stock_receipt_items.quantity) as total_quantity'),
                DB::raw('SUM(stock_receipt_items.line_total) as total_amount'),
            ])
            ->get();

        $issueByDate = StockIssueItem::query()
            ->join('stock_issues', 'stock_issue_items.stock_issue_id', '=', 'stock_issues.id')
            ->whereBetween('stock_issues.issue_date', [$payload['from_date'], $payload['to_date']])
            ->when(! empty($payload['warehouse_id']), fn ($query) => $query->where('stock_issues.warehouse_id', (int) $payload['warehouse_id']))
            ->groupBy('stock_issues.issue_date')
            ->orderBy('stock_issues.issue_date')
            ->select([
                'stock_issues.issue_date as date',
                DB::raw('SUM(stock_issue_items.quantity) as total_quantity'),
                DB::raw('SUM(stock_issue_items.line_total) as total_amount'),
            ])
            ->get();

        return response()->json([
            'summary' => [
                'from_date' => $payload['from_date'],
                'to_date' => $payload['to_date'],
                'warehouse_id' => $payload['warehouse_id'] ?? null,
                'total_receipt_quantity' => round($totalReceiptQty, 3),
                'total_issue_quantity' => round($totalIssueQty, 3),
                'net_quantity' => round($totalReceiptQty - $totalIssueQty, 3),
            ],
            'receipts_by_date' => $receiptByDate,
            'issues_by_date' => $issueByDate,
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $query = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->where('products.min_stock_alert', '>', 0)
            ->whereColumn('inventories.quantity', '<=', 'products.min_stock_alert')
            ->when($request->filled('warehouse_id'), fn ($builder) => $builder->where('inventories.warehouse_id', (int) $request->input('warehouse_id')))
            ->orderBy('inventories.quantity')
            ->select([
                'inventories.id',
                'inventories.warehouse_id',
                'inventories.product_id',
                'inventories.quantity',
                'products.code as product_code',
                'products.name as product_name',
                'products.unit as product_unit',
                'products.min_stock_alert',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
            ]);

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function slowMoving(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $threshold = now()->subDays($days);

        $data = Inventory::query()
            ->with(['product:id,code,name,unit', 'warehouse:id,code,name'])
            ->where('quantity', '>', 0)
            ->where(function ($query) use ($threshold) {
                $query
                    ->whereNull('last_movement_at')
                    ->orWhere('last_movement_at', '<=', $threshold);
            })
            ->orderByDesc('quantity')
            ->get();

        return response()->json([
            'threshold_days' => $days,
            'data' => $data,
        ]);
    }
}
