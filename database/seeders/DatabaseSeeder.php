<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('system_admin');
        Role::findOrCreate('warehouse_staff');

        $admin = User::query()->firstOrCreate([
            'email' => 'admin@warehouse.local',
        ], [
            'name' => 'Warehouse Admin',
            'password' => Hash::make('Admin@123456'),
            'is_active' => true,
        ]);

        $admin->syncRoles(['system_admin']);
    }
}
