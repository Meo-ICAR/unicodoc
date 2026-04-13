<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function checkGate(Request $request): JsonResponse
    {
        // 1. Validazione dell'input dal BPM
        $validated = $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required',
            'required_codes' => 'required|array',
            'required_codes.*' => 'string',
        ]);

        $requiredCodes = $validated['required_codes'];

        // 2. Recuperiamo i documenti dell'entità che corrispondono ai codici richiesti
        // Ordiniamo per data di creazione discendente per prendere sempre il più recente
        $documents = Document::with(['documentType', 'status'])
            ->where('documentable_type', $validated['documentable_type'])
            ->where('documentable_id', $validated['documentable_id'])
            ->whereHas('documentType', function ($query) use ($requiredCodes) {
                $query->whereIn('code', $requiredCodes);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('documentType.code');  // Raggruppiamo per codice

        // 3. Prepariamo i contenitori per la risposta
        $validDocs = [];
        $invalidDocs = [];
        $missingDocs = [];

        // 4. Analizziamo ogni codice richiesto dal BPM
        foreach ($requiredCodes as $code) {
            if (!$documents->has($code)) {
                // Il documento non è mai stato caricato per questa entità
                $missingDocs[] = $code;
                continue;
            }

            // Prendiamo il documento più recente per questo codice
            $latestDoc = $documents->get($code)->first();

            // Valutiamo lo stato (usando la tabella document_status)
            if ($latestDoc->status->is_ok && $latestDoc->status_code !== 'SCADUTO') {
                $validDocs[] = [
                    'code' => $code,
                    'document_id' => $latestDoc->id,
                    'status' => $latestDoc->status_code,
                    'expires_at' => $latestDoc->expires_at?->toDateString(),
                ];
            } else {
                $invalidDocs[] = [
                    'code' => $code,
                    'document_id' => $latestDoc->id,
                    'status' => $latestDoc->status_code,
                    'reason' => $this->getInvalidationReason($latestDoc),
                ];
            }
        }

        // 5. Verdetto finale: è compliant solo se non ci sono documenti mancanti o invalidi
        $isCompliant = empty($missingDocs) && empty($invalidDocs);

        return response()->json([
            'is_compliant' => $isCompliant,
            'summary' => [
                'total_required' => count($requiredCodes),
                'valid' => count($validDocs),
                'invalid' => count($invalidDocs),
                'missing' => count($missingDocs),
            ],
            'details' => [
                'valid_documents' => $validDocs,
                'invalid_documents' => $invalidDocs,
                'missing_documents' => $missingDocs,
            ]
        ]);
    }

    /**
     * Genera una motivazione leggibile per il rifiuto, utile per i log del BPM.
     */
    protected function getInvalidationReason(Document $document): string
    {
        return match ($document->status_code) {
            'SCADUTO' => 'Documento scaduto in data ' . $document->expires_at?->toDateString(),
            'DA VERIFICARE', 'IN VERIFICA' => 'Documento in attesa di validazione da parte di un operatore',
            'DIFFORME', 'ERRATO' => 'Documento rifiutato: ' . ($document->rejection_note ?? 'Nessuna nota specificata'),
            'RICHIESTA INFO' => "Richieste informazioni aggiuntive all'utente",
            default => 'Stato documento non idoneo: ' . $document->status_code,
        };
    }
}
