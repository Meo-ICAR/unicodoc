<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentAiJob;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    /**
     * Endpoint: POST /api/v1/documents
     * Carica un nuovo documento, lo associa all'entità e fa dispatch del job AI.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validazione base
        $validated = $request->validate([
            'company_id'        => ['required', 'exists:companies,id'],
            'documentable_type' => ['required', 'string'],
            'documentable_id'   => ['required', 'integer'],
            'file'              => ['required', 'file', 'mimes:pdf,jpg,png', 'max:20480'], // max 20MB
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 2. Creazione record Document base (stato iniziale PENDING)
            $document = Document::create([
                'company_id'        => $validated['company_id'],
                'documentable_type' => $validated['documentable_type'],
                'documentable_id'   => $validated['documentable_id'],
                // Supponiamo che il default tramite DB o trait sia DocumentStatus::PENDING
            ]);

            // 3. Associazione file storage via Spatie su S3
            // Non intasa PHP: processa l'upload e invia ad S3 in base all'astrazione
            $document->addMediaFromRequest('file')
                ->toMediaCollection('documents', 's3');

            // 4. Dispatch del job asincrono in Redis/Horizon per non far attendere il chiamante
            ProcessDocumentAiJob::dispatch($document);

            // 5. Restituire una risposta "202 Accepted" asincrona API standard
            return response()->json([
                'message'     => 'Documento ricevuto con successo. Elaborazione asincrona in corso.',
                'document_id' => $document->id,
            ], Response::HTTP_ACCEPTED);
        });
    }

    /**
     * Endpoint: GET /api/v1/documents/{id}/download
     * Evita di scaricare il file dal controller, delega tramite link temporaneo di S3 per 0 RAM server.
     */
    public function download($id): JsonResponse
    {
        // Usa policy/permessi (es. $this->authorize('view', $document)) se richiesto
        $document = Document::findOrFail($id);

        $media = $document->getFirstMedia('documents');

        if (! $media) {
            return response()->json(['error' => 'Media file non trovato'], Response::HTTP_NOT_FOUND);
        }

        // Genera un url firmato pre-autenticato nativo di AWS S3 valido 30 min
        $temporaryUrl = $media->getTemporaryUrl(now()->addMinutes(30));

        return response()->json([
            'download_url' => $temporaryUrl,
            'expires_in'   => '30 minutes'
        ]);
    }
}
