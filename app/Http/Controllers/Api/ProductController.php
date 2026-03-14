<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Quản lý sản phẩm"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Danh sách sản phẩm, tìm kiếm theo tên/mã",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="product_group_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Danh sách sản phẩm")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->input('search', '');

        $query = Product::query()->with('group:id,code,name');

        if ($request->filled('product_group_id')) {
            $query->where('product_group_id', (int) $request->input('product_group_id'));
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_group_id' => ['nullable', 'integer', 'exists:product_groups,id'],
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:100'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = Product::create($payload);

        return response()->json([
            'message' => 'Tạo sản phẩm thành công.',
            'data' => $product->load('group:id,code,name'),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'group:id,code,name',
            'inventories' => function ($query) {
                $query->with('warehouse:id,code,name,address')->orderByDesc('quantity');
            },
        ]);

        return response()->json([
            'data' => $product,
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $payload = $request->validate([
            'product_group_id' => ['nullable', 'integer', 'exists:product_groups,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:100'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update($payload);

        return response()->json([
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => $product->load('group:id,code,name'),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $hasData = $product->inventories()->exists()
            || $product->stockReceiptItems()->exists()
            || $product->stockIssueItems()->exists()
            || $product->stockTransferItems()->exists()
            || $product->stocktakeItems()->exists()
            || $product->inventoryTransactions()->exists();

        if ($hasData) {
            return response()->json([
                'message' => 'Không thể xóa sản phẩm vì đã phát sinh dữ liệu kho.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'message' => 'Xóa sản phẩm thành công.',
        ]);
    }
}
