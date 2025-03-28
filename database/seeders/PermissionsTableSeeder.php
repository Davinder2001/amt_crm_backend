<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define permission groups with their corresponding permissions.
        $permissionGroups = [
            'User Management' => [
                'view users',
                'add users',
                'edit users',
                'delete users',
            ],
            'Role Management' => [
                'view roles',
                'add roles',
                'edit roles',
                'delete roles',
            ],
            'Task Management' => [
                'view task',
                'add task',
                'update task',
                'delete task',
            ],
            'Employee Management' => [
                'view employee',
                'add employee',
                'update employee',
                'delete employee',
                'view employee salary',
                'download employee salary',
            ],
            'Company Management' => [
                'view company',
                'add company',
                'update company',
                'delete company',
            ],
        ];

        // Loop through each group and its permissions.
        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $permissionName) {
                // If you added a 'group' column, include it here.
                Permission::firstOrCreate(
                    ['name' => $permissionName],
                    ['group' => $group]
                );
            }
        }
    }
}
