<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionList;
use App\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and permissions from the plan.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionList::all() as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (RoleName::cases() as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName->value, 'guard_name' => 'web'])
                ->syncPermissions($roleName->permissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
