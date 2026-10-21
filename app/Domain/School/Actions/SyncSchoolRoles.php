<?php

namespace App\Domain\School\Actions;

use App\Domain\School\Enums\Permission as PermissionName;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every Role for a school and gives each its permissions. Safe to re-run after
 * a milestone adds permissions.
 */
class SyncSchoolRoles
{
    public function handle(School $school): void
    {
        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }
        // The registrar caches the permission list; refresh it before roles look permissions up.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::cases() as $role) {
            $model = RoleModel::firstOrCreate(['name' => $role->value, 'guard_name' => 'web', 'school_id' => $school->id]);
            $model->syncPermissions($role->permissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
