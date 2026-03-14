# Warehouse Management API

Project Laravel 12 xây dựng hệ thống API quản lý kho dùng MySQL, Sanctum, Spatie Permission và Swagger.

## Chức năng đã dựng

- Quản lý kho: thêm / sửa / xóa / tìm theo địa chỉ
- Quản lý hàng hóa: thêm / sửa / xóa / tìm theo tên hoặc mã sản phẩm
- Quản lý nhóm hàng, đơn vị tính, mã hàng, tên hàng
- Theo dõi tồn kho theo từng kho
- Nhập kho: lập phiếu nhập, cập nhật tồn, lưu lịch sử
- Xuất kho: lập phiếu xuất, kiểm tra tồn trước khi xuất, trừ tồn
- Điều chuyển kho: chuyển từ kho A sang kho B, cập nhật tồn 2 bên, lưu lịch sử
- Kiểm kê kho: so sánh tồn hệ thống và tồn thực tế, lưu chênh lệch, tùy chọn tự động điều chỉnh tồn
- Quản lý người dùng: đăng nhập / đăng xuất, phân quyền `system_admin`, `warehouse_staff`
- Báo cáo: tồn kho theo kho, nhập xuất theo thời gian, hàng sắp hết, hàng tồn lâu
- Swagger UI tại `/api/documentation`

## Công nghệ

- Laravel 12
- PHP 8.2+
- MySQL / MariaDB
- Laravel Sanctum
- Spatie Laravel Permission
- L5 Swagger

## Cấu hình nhanh

### 1. Cấu hình database

`.env` mặc định:

- `DB_CONNECTION=mysql`
- `DB_HOST=localhost`
- `DB_PORT=3306`
- `DB_DATABASE=warehouse_api`
- `DB_USERNAME=root`
- `DB_PASSWORD=`

Nếu máy bạn dùng XAMPP mặc định thì có thể chạy luôn.

### 2. Tạo database

Tạo database `warehouse_api` trong MySQL.

### 3. Cài package

Project đã có `vendor`, nhưng nếu cần cài lại:

- `php ..\composer.phar install`

### 4. Tạo app key

- `php artisan key:generate`

### 5. Migrate và seed

- `php artisan migrate:fresh --seed`

Seeder sẽ tạo sẵn tài khoản admin:

- Email: `admin@warehouse.local`
- Password: `Admin@123456`
- Role: `system_admin`

### 6. Generate Swagger docs

- `php artisan l5-swagger:generate`

### 7. Chạy server

- `php artisan serve`

Mặc định API local sẽ ở:

- `http://localhost:8000`

Swagger UI:

- `http://localhost:8000/api/documentation`

## Xác thực

Đăng nhập qua endpoint:

- `POST /api/v1/auth/login`

Token trả về dùng dạng:

- `Authorization: Bearer {token}`

## Nhóm endpoint chính

### Authentication

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

### Users

- `GET /api/v1/users`
- `POST /api/v1/users`
- `GET /api/v1/users/{id}`
- `PUT /api/v1/users/{id}`
- `DELETE /api/v1/users/{id}`

### Warehouses

- `GET /api/v1/warehouses`
- `POST /api/v1/warehouses`
- `GET /api/v1/warehouses/{id}`
- `PUT /api/v1/warehouses/{id}`
- `DELETE /api/v1/warehouses/{id}`

### Product Groups

- `GET /api/v1/product-groups`
- `POST /api/v1/product-groups`
- `GET /api/v1/product-groups/{id}`
- `PUT /api/v1/product-groups/{id}`
- `DELETE /api/v1/product-groups/{id}`

### Products

- `GET /api/v1/products`
- `POST /api/v1/products`
- `GET /api/v1/products/{id}`
- `PUT /api/v1/products/{id}`
- `DELETE /api/v1/products/{id}`

### Stock Receipts

- `GET /api/v1/stock-receipts`
- `POST /api/v1/stock-receipts`
- `GET /api/v1/stock-receipts/{id}`

### Stock Issues

- `GET /api/v1/stock-issues`
- `POST /api/v1/stock-issues`
- `GET /api/v1/stock-issues/{id}`

### Stock Transfers

- `GET /api/v1/stock-transfers`
- `POST /api/v1/stock-transfers`
- `GET /api/v1/stock-transfers/{id}`

### Stocktakes

- `GET /api/v1/stocktakes`
- `POST /api/v1/stocktakes`
- `GET /api/v1/stocktakes/{id}`

### Reports

- `GET /api/v1/reports/inventory-by-warehouse`
- `GET /api/v1/reports/in-out-by-period`
- `GET /api/v1/reports/low-stock`
- `GET /api/v1/reports/slow-moving`

## Gợi ý dữ liệu nghiệp vụ

### Role

- `system_admin`: toàn quyền
- `warehouse_staff`: thao tác kho, hàng hóa, nhập xuất chuyển kiểm kê, báo cáo

### Tìm kiếm hỗ trợ sẵn

- Kho: `search` theo tên / mã / địa chỉ
- Sản phẩm: `search` theo tên / mã

## Ghi chú

- Các phiếu nhập / xuất / điều chuyển / kiểm kê đã tạo không cho sửa hoặc xóa để tránh sai lệch lịch sử tồn kho.
- Kiểm kê có cờ `apply_adjustment=true` để tự động đồng bộ tồn hệ thống theo tồn thực tế.
- Lịch sử biến động kho được lưu tại bảng `inventory_transactions`.
