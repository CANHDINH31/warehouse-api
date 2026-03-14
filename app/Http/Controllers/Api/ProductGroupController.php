<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Product Groups",
 *     description="Quản lý nhóm hàng"
 * )
 */
class ProductGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->input('search', '');

        $query = ProductGroup::query();

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_groups,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $group = ProductGroup::create($payload);

        return response()->json([
            'message' => 'Tạo nhóm hàng thành công.',
            'data' => $group,
        ], 201);
    }

    public function show(ProductGroup $productGroup): JsonResponse
    {
        return response()->json([
            'data' => $productGroup->loadCount('products'),
        ]);
    }

    public function update(Request $request, ProductGroup $productGroup): JsonResponse
    {
        $payload = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product_groups', 'code')->ignore($productGroup->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $productGroup->update($payload);

        return response()->json([
            'message' => 'Cập nhật nhóm hàng thành công.',
            'data' => $productGroup,
        ]);
    }

    public function destroy(ProductGroup $productGroup): JsonResponse
    {
        if ($productGroup->products()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa nhóm hàng vì đã có sản phẩm.',
            ], 422);
        }

        $productGroup->delete();

        return response()->json([
            'message' => 'Xóa nhóm hàng thành công.',
        ]);
    }
}
