<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Warehouses",
 *     description="Quản lý kho"
 * )
 */
class WarehouseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/warehouses",
     *     tags={"Warehouses"},
     *     summary="Danh sách kho, hỗ trợ tìm theo địa chỉ",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Danh sách kho")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->input('search', '');

        $query = Warehouse::query();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('address', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 15));

        return response()->json($warehouses);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/warehouses",
     *     tags={"Warehouses"},
     *     summary="Thêm kho",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=201, description="Tạo kho thành công")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $warehouse = Warehouse::create($payload);

        return response()->json([
            'message' => 'Tạo kho thành công.',
            'data' => $warehouse,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/warehouses/{id}",
     *     tags={"Warehouses"},
     *     summary="Chi tiết kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Chi tiết kho")
     * )
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return response()->json([
            'data' => $warehouse,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/warehouses/{id}",
     *     tags={"Warehouses"},
     *     summary="Cập nhật kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')->ignore($warehouse->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $warehouse->update($payload);

        return response()->json([
            'message' => 'Cập nhật kho thành công.',
            'data' => $warehouse,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/warehouses/{id}",
     *     tags={"Warehouses"},
     *     summary="Xóa kho",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $hasData = $warehouse->inventories()->exists()
            || $warehouse->stockReceipts()->exists()
            || $warehouse->stockIssues()->exists()
            || $warehouse->stockTransfersFrom()->exists()
            || $warehouse->stockTransfersTo()->exists()
            || $warehouse->stocktakes()->exists();

        if ($hasData) {
            return response()->json([
                'message' => 'Không thể xóa kho vì đã phát sinh dữ liệu.',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'message' => 'Xóa kho thành công.',
        ]);
    }
}
