<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Permissions needed by OtherApiResource / OtherApiPolicy. Only the
     * non-destructive abilities exist; the policy returns false for delete,
     * force delete, restore, replicate and reorder, so no permission is
     * seeded for them and those buttons can never appear.
     */
    private const PERMISSIONS = [
        'view_other_api::a::p::i',
        'view_any_other_api::a::p::i',
        'create_other_api::a::p::i',
        'update_other_api::a::p::i',
    ];

    public function up(): void
    {
        // Mirror the naming of the adex permissions: roles that already
        // manage the ADEX API credentials should manage these too.
        $sourcePermissions = [
            'view_other_api::a::p::i' => 'view_adex::a::p::i',
            'view_any_other_api::a::p::i' => 'view_any_adex::a::p::i',
            'create_other_api::a::p::i' => 'create_adex::a::p::i',
            'update_other_api::a::p::i' => 'update_adex::a::p::i',
        ];

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        // Inherit from the adex counterpart where one exists, otherwise fall
        // back to every role holding any adex permission (super_admin).
        foreach ($sourcePermissions as $target => $source) {
            $roleIds = $this->roleIdsFor($source);

            if ($roleIds === []) {
                $roleIds = $this->roleIdsFor(null);
            }

            $permission = Permission::findOrCreate($target, 'admin');

            foreach (array_unique($roleIds) as $roleId) {
                $already = DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $roleId)
                    ->exists();

                if ($already) {
                    continue;
                }

                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'role_id' => $roleId,
                ]);
            }
        }

        app('cache')->forget('spatie.permission.cache');
    }

    /**
     * @param  string|null  $permissionName  Exact permission name, or null for
     *                                       "any role holding an adex permission".
     * @return array<int, int>
     */
    private function roleIdsFor(?string $permissionName): array
    {
        $query = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.guard_name', 'admin')
            ->select('role_has_permissions.role_id')
            ->distinct();

        if ($permissionName === null) {
            $query->where('permissions.name', 'like', '%_adex::a::p::i');
        } else {
            $query->where('permissions.name', $permissionName);
        }

        return $query->pluck('role_has_permissions.role_id')->map(fn ($id) => (int) $id)->all();
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            Permission::query()->whereIn('id', $permissionIds)->delete();
        }

        app('cache')->forget('spatie.permission.cache');
    }
};