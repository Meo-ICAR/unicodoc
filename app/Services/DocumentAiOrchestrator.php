<?php

namespace App\Services;

use App\Models\RequestRegistryAttachment;
use Illuminate\Support\Facades\Http;

class DocumentAiOrchestrator
{
    public function validateDocument(RequestRegistryAttachment $attachment): array
    {
        // Simulazione chiamata a modello Vision (es. GPT-4o o AWS Textract)
        $response = Http::withToken(config('services.ai.key'))
            ->post('https://api.unicodoc.ai/v1/analyze', [
                'file_url' => $attachment->getTemporaryUrl(),
                'expected_type' => $attachment->file_type,
            ]);

        $data = $response->json();

        return [
            'status' => $data['confidence'] > 0.85 ? 'approved' : 'manual_review',
            'confidence' => $data['confidence'],
            'extracted_data' => $data['fields'],  // Es: data scadenza, numero documento
        ];
    }
}
