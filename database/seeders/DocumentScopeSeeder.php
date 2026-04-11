<?php

namespace Database\Seeders;

use App\Models\DocumentScope;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scopes = [
            [
                'id' => 1,
                'name' => 'Privacy',
                'description' => 'GDPR Privacy Consent',
                'color_code' => '#10B981',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
            [
                'id' => 2,
                'name' => 'AML',
                'description' => 'Anti-Money Laundering',
                'color_code' => '#EF4444',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
            [
                'id' => 3,
                'name' => 'OAM',
                'description' => 'OAM Forms',
                'color_code' => '#3B82F6',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
            [
                'id' => 4,
                'name' => 'UIF',
                'description' => 'UIF SOS',
                'color_code' => '#3B82F6',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
            [
                'id' => 5,
                'name' => 'Istruttoria',
                'description' => 'Pratica docs',
                'color_code' => '#F59E0B',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
            [
                'id' => 6,
                'name' => 'Onboarding',
                'description' => 'Onboarding',
                'color_code' => '#F59E0B',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
            [
                'id' => 7,
                'name' => 'Amministrativo',
                'description' => 'Amministrativo',
                'color_code' => '#F59E0B',
                'created_at' => '2026-03-18 10:18:54',
                'updated_at' => '2026-03-18 10:18:54',
            ],
        ];

        foreach ($scopes as $scope) {
            DocumentScope::create($scope);
        }
    }
}
