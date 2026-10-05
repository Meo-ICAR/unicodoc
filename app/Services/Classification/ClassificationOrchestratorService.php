<?php

namespace App\Services\Classification;

use App\Models\ClassificationLog;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;

class ClassificationOrchestratorService
{
    public function __construct(
        protected RegexClassifier $regexClassifier,
        protected AiClassifier $aiClassifier
    ) {}

    /**
     * Esegue la pipeline di classificazione su un documento.
     */
    public function process(Document $document): void
    {
        // 1. Tenta prima con la Regex
        $result = $this->regexClassifier->classify($document);

        // 2. Se fallisce, passa all'AI
        if (!$result) {
            $result = $this->aiClassifier->classify($document);
        }

        // 3. Se entrambi falliscono, lo mettiamo in "DA VERIFICARE" senza tipo
        if (!$result || !$result->documentTypeId) {
            $this->markAsUnclassified($document);
            return;
        }

        // 4. Se abbiamo un risultato, lo applichiamo valutando le soglie (Thresholds)
        $this->applyResult($document, $result);
    }

    protected function applyResult(Document $document, ClassificationResult $result): void
    {
        $documentType = DocumentType::find($result->documentTypeId);

        DB::transaction(function () use ($document, $result, $documentType) {
            // Logica basata sulla "Confidence"
            $status = 'DA VERIFICARE';  // Default di sicurezza

            if ($result->confidenceScore >= $documentType->min_confidence) {
                // L'AI o la Regex sono abbastanza sicuri
                $status = 'IN VERIFICA';  // Pronto per lo sguardo umano

                // Opzionale: Auto-approvazione se lo score è altissimo e il tipo lo permette
                if ($result->confidenceScore >= 95 && $documentType->allow_auto_verification) {
                    $status = 'OK';
                    $document->verified_at = now();
                    $document->annotation = 'Auto-approvato dal sistema (' . $result->classifierUsed . ')';
                }
            } else {
                // Score troppo basso, l'AI ha tirato a indovinare
                $status = 'RICHIESTA INFO';
            }

            // Aggiorna il documento
            $document->update([
                'document_type_id' => $result->documentTypeId,
                'status_code' => $status,
                'ai_confidence_score' => $result->confidenceScore,
            ]);

            // Salva il feedback log per l'addestramento futuro
            ClassificationLog::create([
                'document_id' => $document->id,
                'predicted_type_id' => $result->documentTypeId,
                'actual_type_id' => null,  // Sarà popolato dall'umano quando verifica
                'classifier_used' => $result->classifierUsed,
                'confidence_score' => $result->confidenceScore,
                'classification_evidence' => $result->evidence,
                'is_override' => false,
            ]);
        });
    }

    protected function markAsUnclassified(Document $document): void
    {
        $document->update([
            'document_type_id' => null,
            'status_code' => 'DA VERIFICARE',
            'annotation' => 'Classificazione automatica fallita. Richiesto intervento manuale.',
        ]);
    }
}
