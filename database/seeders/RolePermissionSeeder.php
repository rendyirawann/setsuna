<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ================================================
        // 1. CREATE ALL PERMISSIONS
        // ================================================

        // --- Navigation / Page Access ---
        $navPermissions = [
            'view_dashboard',
            'view_data_master',
            'view_resources',
            'view_help',
        ];

        // --- Granular User Management ---
        $userPermissions = [
            'user.show',
            'user.create',
            'user.edit',
            'user.delete',
            'user.massdelete',
            'user.ban',
        ];

        // --- Granular Role Management ---
        $rolePermissions = [
            'role.show',
            'role.create',
            'role.edit',
            'role.delete',
            'role.massdelete',
        ];

        // --- Manajemen wedding (paket, klien, acara, pesanan) ---
        $weddingPermissions = [
            'plan.show', 'plan.create', 'plan.edit', 'plan.delete',
            'client.show', 'client.create', 'client.edit', 'client.delete',
            'event.show', 'event.create', 'event.edit', 'event.delete',
            'event.qr', 'event.moderate', 'event.download',
            'subscription.show', 'subscription.create', 'subscription.edit',
            'subscription.delete', 'subscription.settle',
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $navPermissions, $userPermissions, $rolePermissions, $weddingPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================================
        // 2. CREATE ROLES (if they don't exist)
        // ================================================
        $roleSuperadmin = Role::firstOrCreate(['name' => 'Superadmin']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'admin']);

        // ================================================
        // 3. ASSIGN PERMISSIONS TO ROLES
        // ================================================

        // SUPERADMIN — gets everything implicitly via Gate::before in AppServiceProvider
        // But we still assign explicitly for completeness
        $roleSuperadmin->syncPermissions(Permission::all());

        // ADMIN — All except Resources (User/Role Management)
        $adminPermissions = [
            'view_dashboard',
            'view_data_master',
            'view_help',
        ];
        $roleAdmin->syncPermissions($adminPermissions);
    }
}
