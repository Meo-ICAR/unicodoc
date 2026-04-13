<?php

namespace App\Services\Classification;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiClassifier implements DocumentClassifierInterface
{
    public function classify(Document $document): ?ClassificationResult
    {
        if (empty($document->extracted_text)) {
            Log::warning("AI Classification skipped: No extracted text for Document {$document->id}");
            return null;
        }

        // Recuperiamo i tipi che supportano o richiedono classificazione AI
        $allowedTypes = DocumentType::select('id', 'name', 'ai_pattern', 'description')->get();

        // Costruiamo il prompt per l'LLM
        $prompt = $this->buildPrompt($document->extracted_text, $allowedTypes);

        try {
            // Chiamata fittizia all'API AI (es. OpenAI)
            // L'API è istruita per rispondere in JSON: {"type_id": 1, "confidence": 85, "reasoning": "..."}
            $response = Http::withToken(config('services.ai.api_key'))
                ->post(config('services.ai.endpoint'), [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'system', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new ClassificationResult(
                    documentTypeId: $data['type_id'],
                    confidenceScore: $data['confidence'],
                    classifierUsed: 'ai',
                    evidence: ['reasoning' => $data['reasoning']]
                );
            }
        } catch (\Exception $e) {
            Log::error("AI Classification failed for Document {$document->id}: " . $e->getMessage());
        }

        return null;
    }

    private function buildPrompt(string $text, $types): string
    {
        $typesJson = $types->toJson();
        return <<<PROMPT
            Sei un assistente specializzato nella classificazione di documenti aziendali.
            Analizza il seguente testo estratto dal documento e classificalo basandoti sui tipi forniti.
            Tipi disponibili: {$typesJson}
            Testo del documento: "{$text}"

            Rispondi SOLO in JSON con questa struttura:
            {
                "type_id": ID del tipo corrispondente (o null se nessuno corrisponde),
                "confidence": Da 0 a 100,
                "reasoning": "Breve spiegazione del perché hai scelto questo tipo"
            }
            PROMPT;
    }
}
