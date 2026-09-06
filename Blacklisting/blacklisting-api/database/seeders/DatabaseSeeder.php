<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. System SaaS Manager (Next Gen Innovations)
        User::updateOrCreate(['email' => 'superadmin@nextgen.com'], [
            'name' => 'System Manager',
            'password' => Hash::make('admin'),
            'tenant_id' => 'system',
            'role' => 'superadmin'
        ]);

        // 2. Bank A - Tenant Admin
        User::updateOrCreate(['email' => 'admin@banka.com'], [
            'name' => 'Bank A Admin',
            'password' => Hash::make('admin'),
            'tenant_id' => 'bank_a',
            'role' => 'admin'
        ]);

        // 2. Bank A - Maker (Data Entry)
        User::updateOrCreate(['email' => 'maker@banka.com'], [
            'name' => 'Bank A Maker',
            'password' => Hash::make('admin'),
            'tenant_id' => 'bank_a',
            'role' => 'maker'
        ]);

        // 3. Bank A - Checker (Approver)
        User::updateOrCreate(['email' => 'checker@banka.com'], [
            'name' => 'Bank A Checker',
            'password' => Hash::make('admin'),
            'tenant_id' => 'bank_a',
            'role' => 'checker'
        ]);
    }
}
