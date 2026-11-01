<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Nama peran teknis & formal yang didukung sistem
     */
    public const ROLES = [
        'admin_pmi',
        'petugas_rs',
        'pendonor',
        'Petugas Bank Darah',
        'Admin Rumah Sakit',
        'Pendonor',
    ];

    /**
     * Hak akses granular RBAC
     */
    public const PERMISSIONS = [
        'kelola_master',
        'kelola_pendonor',
        'kelola_kegiatan',
        'kelola_stok_darah',
        'uji_saring_lab',
        'permintaan_darah',
        'distribusi_darah',
        'lihat_laporan',
        'lihat_audit_log',
    ];

    public function run(): void
    {
        // Reset cache permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Buat seluruh permission
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2. Buat seluruh role
        $roleMap = [];
        foreach (self::ROLES as $roleName) {
            $roleMap[$roleName] = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 3. Assign permissions ke Petugas Bank Darah (Admin UTD/PMI) - Full Access
        $allPermissions = Permission::all();
        $roleMap['admin_pmi']->syncPermissions($allPermissions);
        $roleMap['Petugas Bank Darah']->syncPermissions($allPermissions);

        // 4. Assign permissions ke Admin Rumah Sakit
        $rsPermissions = ['permintaan_darah', 'distribusi_darah', 'lihat_laporan'];
        $roleMap['petugas_rs']->syncPermissions($rsPermissions);
        $roleMap['Admin Rumah Sakit']->syncPermissions($rsPermissions);

        // 5. Assign permissions ke Pendonor
        $donorPermissions = ['kelola_kegiatan'];
        $roleMap['pendonor']->syncPermissions($donorPermissions);
        $roleMap['Pendonor']->syncPermissions($donorPermissions);
    }
}
