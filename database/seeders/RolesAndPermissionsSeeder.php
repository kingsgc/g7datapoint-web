<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create a super_admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // Find the first user (or your desired admin user) and assign the role
        $superAdminUser = User::where('username', 'Bekind')->first();
        if ($superAdminUser) {
            $superAdminUser->assignRole($superAdminRole);
        }
    }
}

// php artisan db:seed --class=RolesAndPermissionsSeeder
// if ($role->name === 'super_admin') {
    //         return false;
    //     }