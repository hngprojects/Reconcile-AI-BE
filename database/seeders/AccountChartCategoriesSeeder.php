<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountChartCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('account_chart_categories')->insert([
            [
                'id' => Str::uuid(),
                'title' => 'Revenue',
                'description' => 'Income from sales and services (Required)',
                'is_active' => true,
                'is_required' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Expenses',
                'description' => 'Costs of running your business (Required)',
                'is_active' => true,
                'is_required' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Assets',
                'description' => 'Things your business owns (Optional)',
                'is_active' => false,
                'is_required' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Liabilities',
                'description' => 'Debts your business owes (Optional)',
                'is_active' => false,
                'is_required' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Equity',
                'description' => 'Owner\'s stake in the business (Optional)',
                'is_active' => false,
                'is_required' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
