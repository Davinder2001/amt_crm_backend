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
        // ================================
        // User Management Permissions
        // ================================
        $userPermissions = [
            'view users',
            'add users',
            'edit users',
            'delete users',
        ];
        foreach ($userPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================
        // Role Management Permissions
        // ================================
        $rolePermissions = [
            'view roles',
            'add roles',
            'edit roles',
            'delete roles',
        ];
        foreach ($rolePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================
        // Task Management Permissions
        // ================================
        $taskPermissions = [
            'view task',
            'add task',
            'update task',
            'delete task',
        ];
        foreach ($taskPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================
        // Employee Management Permissions
        // ================================
        $employeePermissions = [
            'view employee',
            'add employee',
            'update employee',
            'delete employee',
            'view employee salary',
            'download employee salary',
        ];
        foreach ($employeePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================
        // Company Management Permissions
        // ================================
        $companyPermissions = [
            'view company',
            'add company',
            'update company',
            'delete company',
        ];
        foreach ($companyPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
