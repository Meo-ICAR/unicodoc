<?php

namespace App\Services\Classification;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Cache;

class RegexClassifier implements DocumentClassifierInterface
{
    public function classify(Document $document): ?ClassificationResult
    {
        // Cache dei tipi di documento per evitare query ripetitive per ogni file
        $documentTypes = Cache::remember('classification_regex_rules', 3600, function () {
            return DocumentType::whereNotNull('regex_pattern')
                ->orderByDesc('priority')
                ->get(['id', 'regex_pattern']);
        });

        // Il testo da analizzare: priorità al testo estratto dall'OCR, altrimenti il nome del file
        $textToAnalyze = $document->extracted_text ?? $document->name;

        if (empty($textToAnalyze)) {
            return null;
        }

        foreach ($documentTypes as $type) {
            $pattern = '/' . $type->regex_pattern . '/i';  // Case-insensitive

            if (preg_match($pattern, $textToAnalyze, $matches)) {
                return new ClassificationResult(
                    documentTypeId: $type->id,
                    confidenceScore: 100,  // La regex è per natura deterministica
                    classifierUsed: 'regex',
                    evidence: ['matched_string' => $matches[0], 'pattern' => $type->regex_pattern]
                );
            }
        }

        return null;
    }
}
