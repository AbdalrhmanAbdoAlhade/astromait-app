<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'vendor', 'user'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Optional: fine-grained permissions if you want them later.
        $permissions = [
            'manage-vendors',
            'manage-products',
            'manage-services',
            'manage-categories',
            'manage-banners',
            'manage-coupons',
            'manage-articles',
            'issue-certificates',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::findByName('admin', 'web')->givePermissionTo($permissions);
    }
}
