<?php

namespace Database\Seeders;

use App\Models\DocumentStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'id' => 1,
                'name' => 'Documento Assente',
                'status' => 'ASSENTE',
                'is_ok' => 0,
                'is_rejected' => 0,
                'description' => 'Il documento non è stato ancora caricato dal cliente',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 2,
                'name' => 'Da Verificare',
                'status' => 'DA VERIFICARE',
                'is_ok' => 0,
                'is_rejected' => 0,
                'description' => 'Il documento è stato caricato e deve essere verificato',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 3,
                'name' => 'In Verifica',
                'status' => 'IN VERIFICA',
                'is_ok' => 0,
                'is_rejected' => 0,
                'description' => 'Il documento è in fase di verifica da parte dello staff',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 4,
                'name' => 'Documento Valido',
                'status' => 'OK',
                'is_ok' => 1,
                'is_rejected' => 0,
                'description' => 'Il documento è stato verificato e risulta valido',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 5,
                'name' => 'Documento Difforme',
                'status' => 'DIFFORME',
                'is_ok' => 0,
                'is_rejected' => 1,
                'description' => 'Il documento presenta anomalie o non è conforme',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 6,
                'name' => 'Documento scaduto',
                'status' => 'SCADUTO',
                'is_ok' => 0,
                'is_rejected' => 1,
                'description' => 'Il documento scaduto',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 7,
                'name' => 'Informazioni Mancanti',
                'status' => 'RICHIESTA INFO',
                'is_ok' => 0,
                'is_rejected' => 0,
                'description' => 'Sono richieste informazioni aggiuntive al cliente',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 8,
                'name' => 'Documento Errato',
                'status' => 'ERRATO',
                'is_ok' => 0,
                'is_rejected' => 1,
                'description' => 'Il documento caricato non è corretto',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
            [
                'id' => 9,
                'name' => 'Documento Annullato',
                'status' => 'ANNULLATO',
                'is_ok' => 0,
                'is_rejected' => 1,
                'description' => 'Il documento è stato annullato e deve essere ricaricato',
                'created_at' => '2026-03-18 10:18:57',
                'updated_at' => '2026-03-18 10:18:57',
            ],
        ];

        foreach ($statuses as $status) {
            DocumentStatus::create($status);
        }
    }
}
