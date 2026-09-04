<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. System Admin (Next Gen Innovations)
        User::updateOrCreate(['email' => 'admin@nextgen.com'], [
            'name' => 'System Admin',
            'password' => Hash::make('password'),
            'tenant_id' => 'system',
            'role' => 'admin'
        ]);

        // 2. Bank A - Maker (Data Entry)
        User::updateOrCreate(['email' => 'maker@banka.com'], [
            'name' => 'Bank A Maker',
            'password' => Hash::make('password'),
            'tenant_id' => 'bank_a',
            'role' => 'maker'
        ]);

        // 3. Bank A - Checker (Approver)
        User::updateOrCreate(['email' => 'checker@banka.com'], [
            'name' => 'Bank A Checker',
            'password' => Hash::make('password'),
            'tenant_id' => 'bank_a',
            'role' => 'checker'
        ]);
    }
}
