<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'guard_name' => 'api',
            ],
            [
                'name' => 'accountant',
                'guard_name' => 'api',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }


        $system = getVar('system');
        $permissions = Permission::pluck('name')->toArray();


        foreach ($system['permissions'] as $permission) {
            if (!in_array($permission, $permissions)) {
                Permission::create([
                    'name' => strtolower($permission),
                    'guard_name' => 'api'
                ]);
            }else{
                Permission::where('name', strtolower($permission))->update(['guard_name' => 'api']);
            }
        }


        $role = Role::whereName('admin')->first();
        $role->syncPermissions($system['permissions']);

        $role = Role::whereName('accountant')->first();
        $role->syncPermissions($system['accountant_permissions']);
    }
}
