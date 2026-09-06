<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class DummyBlacklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $batchId = 'DUMMY_' . time();
        $individuals = [];
        
        // Generate 10 Individuals
        for ($i = 0; $i < 10; $i++) {
            $individuals[] = [
                'entity_type' => 'individual',
                'name' => $faker->name,
                'upload_batch_id' => $batchId,
                'blacklist_count' => rand(1, 5),
                'blacklist_number' => 'BL-' . $faker->unique()->numerify('####'),
                'blacklist_date' => Carbon::now()->subDays(rand(1, 1000))->format('Y-m-d'),
                'blacklist_type' => $faker->randomElement(['Defaulter', 'Fraud', 'Money Laundering']),
                'blacklist_nature_of_relation' => 'Direct',
                'date_of_birth' => Carbon::now()->subYears(rand(20, 60))->format('Y-m-d'),
                'gender' => $faker->randomElement(['Male', 'Female', 'Other']),
                'father_name' => $faker->name('male'),
                'citizenship_count' => 1,
                'citizenship_number' => $faker->numerify('##-##-##-#####'),
                'ctz_issue_date' => Carbon::now()->subYears(rand(5, 20))->format('Y-m-d'),
                'ctz_issue_district' => $faker->randomElement(['Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Chitwan']),
                'blacklist_sector' => 'Banking',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Generate 10 Institutional
        $institutions = [];
        for ($i = 0; $i < 10; $i++) {
            $institutions[] = [
                'entity_type' => 'institutional',
                'name' => $faker->company,
                'upload_batch_id' => $batchId,
                'blacklist_count' => rand(1, 3),
                'blacklist_number' => 'BL-INST-' . $faker->unique()->numerify('####'),
                'blacklist_date' => Carbon::now()->subDays(rand(1, 1000))->format('Y-m-d'),
                'blacklist_type' => $faker->randomElement(['Corporate Defaulter', 'Tax Evasion', 'Bankruptcy']),
                'blacklist_nature_of_relation' => 'Corporate',
                'pan_details' => 'Active',
                'pan_count' => 1,
                'pan' => $faker->numerify('#########'),
                'pan_issue_date' => Carbon::now()->subYears(rand(2, 15))->format('Y-m-d'),
                'pan_issue_district' => $faker->randomElement(['Kathmandu', 'Lalitpur', 'Biratnagar', 'Birgunj']),
                'company_count' => 1,
                'company_reg_number' => 'REG-' . $faker->numerify('######'),
                'reg_date' => Carbon::now()->subYears(rand(2, 20))->format('Y-m-d'),
                'reg_authority' => 'Office of Company Registrar',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('cib_blacklists')->insert($individuals);
        DB::table('cib_blacklists')->insert($institutions);
    }
}
