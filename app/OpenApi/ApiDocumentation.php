<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Warehouse Management API',
    description: 'API quản lý kho dùng Laravel 12, MySQL, Sanctum, Spatie Permission và Swagger'
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Local server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
)]
#[OA\Tag(name: 'Authentication', description: 'Đăng nhập / đăng xuất')]
#[OA\Tag(name: 'Users', description: 'Quản lý người dùng')]
#[OA\Tag(name: 'Warehouses', description: 'Quản lý kho')]
#[OA\Tag(name: 'Product Groups', description: 'Quản lý nhóm hàng')]
#[OA\Tag(name: 'Products', description: 'Quản lý sản phẩm')]
#[OA\Tag(name: 'Stock Receipts', description: 'Nhập kho')]
#[OA\Tag(name: 'Stock Issues', description: 'Xuất kho')]
#[OA\Tag(name: 'Stock Transfers', description: 'Điều chuyển kho')]
#[OA\Tag(name: 'Stocktakes', description: 'Kiểm kê kho')]
#[OA\Tag(name: 'Reports', description: 'Báo cáo và thống kê')]
class ApiDocumentation
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        tags: ['Authentication'],
        summary: 'Đăng nhập',
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 200, description: 'Đăng nhập thành công')]
    )]
    public function authLogin(): void {}

    #[OA\Post(
        path: '/api/v1/auth/logout',
        tags: ['Authentication'],
        summary: 'Đăng xuất',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Đăng xuất thành công')]
    )]
    public function authLogout(): void {}

    #[OA\Get(
        path: '/api/v1/auth/me',
        tags: ['Authentication'],
        summary: 'Thông tin tài khoản hiện tại',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Thông tin người dùng')]
    )]
    public function authMe(): void {}

    #[OA\Get(
        path: '/api/v1/users',
        tags: ['Users'],
        summary: 'Danh sách người dùng',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Danh sách người dùng')]
    )]
    #[OA\Post(
        path: '/api/v1/users',
        tags: ['Users'],
        summary: 'Tạo người dùng',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Tạo người dùng thành công')]
    )]
    public function usersCollection(): void {}

    #[OA\Get(
        path: '/api/v1/users/{user}',
        tags: ['Users'],
        summary: 'Chi tiết người dùng',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết người dùng')]
    )]
    #[OA\Put(
        path: '/api/v1/users/{user}',
        tags: ['Users'],
        summary: 'Cập nhật người dùng',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 200, description: 'Cập nhật thành công')]
    )]
    #[OA\Delete(
        path: '/api/v1/users/{user}',
        tags: ['Users'],
        summary: 'Xóa người dùng',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Xóa thành công')]
    )]
    public function usersItem(): void {}

    #[OA\Get(
        path: '/api/v1/warehouses',
        tags: ['Warehouses'],
        summary: 'Danh sách kho và tìm theo địa chỉ',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Danh sách kho')]
    )]
    #[OA\Post(
        path: '/api/v1/warehouses',
        tags: ['Warehouses'],
        summary: 'Tạo kho',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Tạo kho thành công')]
    )]
    public function warehousesCollection(): void {}

    #[OA\Get(
        path: '/api/v1/warehouses/{warehouse}',
        tags: ['Warehouses'],
        summary: 'Chi tiết kho',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết kho')]
    )]
    #[OA\Put(
        path: '/api/v1/warehouses/{warehouse}',
        tags: ['Warehouses'],
        summary: 'Cập nhật kho',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 200, description: 'Cập nhật thành công')]
    )]
    #[OA\Delete(
        path: '/api/v1/warehouses/{warehouse}',
        tags: ['Warehouses'],
        summary: 'Xóa kho',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Xóa thành công')]
    )]
    public function warehousesItem(): void {}

    #[OA\Get(
        path: '/api/v1/product-groups',
        tags: ['Product Groups'],
        summary: 'Danh sách nhóm hàng',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Danh sách nhóm hàng')]
    )]
    #[OA\Post(
        path: '/api/v1/product-groups',
        tags: ['Product Groups'],
        summary: 'Tạo nhóm hàng',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Tạo nhóm hàng thành công')]
    )]
    public function productGroupsCollection(): void {}

    #[OA\Get(
        path: '/api/v1/product-groups/{productGroup}',
        tags: ['Product Groups'],
        summary: 'Chi tiết nhóm hàng',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'productGroup', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết nhóm hàng')]
    )]
    #[OA\Put(
        path: '/api/v1/product-groups/{productGroup}',
        tags: ['Product Groups'],
        summary: 'Cập nhật nhóm hàng',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'productGroup', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 200, description: 'Cập nhật thành công')]
    )]
    #[OA\Delete(
        path: '/api/v1/product-groups/{productGroup}',
        tags: ['Product Groups'],
        summary: 'Xóa nhóm hàng',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'productGroup', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Xóa thành công')]
    )]
    public function productGroupsItem(): void {}

    #[OA\Get(
        path: '/api/v1/products',
        tags: ['Products'],
        summary: 'Danh sách sản phẩm, tìm theo tên / mã',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Danh sách sản phẩm')]
    )]
    #[OA\Post(
        path: '/api/v1/products',
        tags: ['Products'],
        summary: 'Tạo sản phẩm',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Tạo sản phẩm thành công')]
    )]
    public function productsCollection(): void {}

    #[OA\Get(
        path: '/api/v1/products/{product}',
        tags: ['Products'],
        summary: 'Chi tiết sản phẩm và tồn theo kho',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết sản phẩm')]
    )]
    #[OA\Put(
        path: '/api/v1/products/{product}',
        tags: ['Products'],
        summary: 'Cập nhật sản phẩm',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 200, description: 'Cập nhật thành công')]
    )]
    #[OA\Delete(
        path: '/api/v1/products/{product}',
        tags: ['Products'],
        summary: 'Xóa sản phẩm',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Xóa thành công')]
    )]
    public function productsItem(): void {}

    #[OA\Get(
        path: '/api/v1/stock-receipts',
        tags: ['Stock Receipts'],
        summary: 'Danh sách phiếu nhập',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Danh sách phiếu nhập')]
    )]
    #[OA\Post(
        path: '/api/v1/stock-receipts',
        tags: ['Stock Receipts'],
        summary: 'Lập phiếu nhập kho',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Lập phiếu nhập thành công')]
    )]
    public function stockReceiptsCollection(): void {}

    #[OA\Get(
        path: '/api/v1/stock-receipts/{stockReceipt}',
        tags: ['Stock Receipts'],
        summary: 'Chi tiết phiếu nhập',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'stockReceipt', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết phiếu nhập')]
    )]
    public function stockReceiptsItem(): void {}

    #[OA\Get(
        path: '/api/v1/stock-issues',
        tags: ['Stock Issues'],
        summary: 'Danh sách phiếu xuất',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Danh sách phiếu xuất')]
    )]
    #[OA\Post(
        path: '/api/v1/stock-issues',
        tags: ['Stock Issues'],
        summary: 'Lập phiếu xuất kho',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Lập phiếu xuất thành công')]
    )]
    public function stockIssuesCollection(): void {}

    #[OA\Get(
        path: '/api/v1/stock-issues/{stockIssue}',
        tags: ['Stock Issues'],
        summary: 'Chi tiết phiếu xuất',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'stockIssue', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết phiếu xuất')]
    )]
    public function stockIssuesItem(): void {}

    #[OA\Get(
        path: '/api/v1/stock-transfers',
        tags: ['Stock Transfers'],
        summary: 'Danh sách phiếu điều chuyển',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Danh sách phiếu điều chuyển')]
    )]
    #[OA\Post(
        path: '/api/v1/stock-transfers',
        tags: ['Stock Transfers'],
        summary: 'Lập phiếu điều chuyển kho',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Lập phiếu điều chuyển thành công')]
    )]
    public function stockTransfersCollection(): void {}

    #[OA\Get(
        path: '/api/v1/stock-transfers/{stockTransfer}',
        tags: ['Stock Transfers'],
        summary: 'Chi tiết phiếu điều chuyển',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'stockTransfer', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết phiếu điều chuyển')]
    )]
    public function stockTransfersItem(): void {}

    #[OA\Get(
        path: '/api/v1/stocktakes',
        tags: ['Stocktakes'],
        summary: 'Danh sách biên bản kiểm kê',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Danh sách kiểm kê')]
    )]
    #[OA\Post(
        path: '/api/v1/stocktakes',
        tags: ['Stocktakes'],
        summary: 'Lập biên bản kiểm kê',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [new OA\Response(response: 201, description: 'Tạo biên bản kiểm kê thành công')]
    )]
    public function stocktakesCollection(): void {}

    #[OA\Get(
        path: '/api/v1/stocktakes/{stocktake}',
        tags: ['Stocktakes'],
        summary: 'Chi tiết biên bản kiểm kê',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'stocktake', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiết biên bản kiểm kê')]
    )]
    public function stocktakesItem(): void {}

    #[OA\Get(
        path: '/api/v1/reports/inventory-by-warehouse',
        tags: ['Reports'],
        summary: 'Báo cáo tồn kho theo từng kho',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Báo cáo tồn kho')]
    )]
    #[OA\Get(
        path: '/api/v1/reports/in-out-by-period',
        tags: ['Reports'],
        summary: 'Báo cáo nhập xuất theo thời gian',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'from_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Báo cáo nhập xuất')]
    )]
    #[OA\Get(
        path: '/api/v1/reports/low-stock',
        tags: ['Reports'],
        summary: 'Báo cáo hàng sắp hết',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Báo cáo hàng sắp hết')]
    )]
    #[OA\Get(
        path: '/api/v1/reports/slow-moving',
        tags: ['Reports'],
        summary: 'Báo cáo hàng tồn lâu',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'days', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Báo cáo hàng tồn lâu')]
    )]
    public function reports(): void {}
}
