<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Quản lý người dùng và phân quyền"
 * )
 */
class UserController extends Controller
{
    private const VALID_ROLES = ['system_admin', 'warehouse_staff'];

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->input('search', '');
        $role = $request->input('role');

        $query = User::query()->with('roles:id,name');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->role($role);
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(self::VALID_ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'is_active' => $payload['is_active'] ?? true,
        ]);

        $user->syncRoles([$payload['role']]);

        return response()->json([
            'message' => 'Tạo người dùng thành công.',
            'data' => $user->load('roles:id,name'),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load('roles:id,name', 'permissions:id,name'),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(self::VALID_ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->name = $payload['name'];
        $user->email = $payload['email'];

        if (! empty($payload['password'])) {
            $user->password = Hash::make($payload['password']);
        }

        if (array_key_exists('is_active', $payload)) {
            $user->is_active = (bool) $payload['is_active'];
        }

        $user->save();
        $user->syncRoles([$payload['role']]);

        return response()->json([
            'message' => 'Cập nhật người dùng thành công.',
            'data' => $user->load('roles:id,name'),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'Không thể xóa tài khoản đang đăng nhập.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Xóa người dùng thành công.',
        ]);
    }
}
