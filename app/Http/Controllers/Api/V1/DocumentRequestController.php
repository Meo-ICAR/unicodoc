<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required',
            'required_codes' => 'required|array',
            'sender_email' => 'nullable|email',
            'bpm_task_id' => 'nullable|string',
            'expires_in_days' => 'nullable|integer',
        ]);

        // Recuperiamo gli ID reali dei tipi di documento
        $documentTypes = DocumentType::whereIn('code', $validated['required_codes'])->get();

        if ($documentTypes->count() !== count($validated['required_codes'])) {
            return response()->json(['error' => 'Alcuni codici documento non esistono.'], 422);
        }

        // Creiamo il Dossier
        $documentRequest = DocumentRequest::create([
            'id' => Str::uuid(),  // Genera l'UUID per il link
            'documentable_type' => $validated['documentable_type'],
            'documentable_id' => $validated['documentable_id'],
            'sender_email' => $validated['sender_email'] ?? null,
            'bpm_task_id' => $validated['bpm_task_id'] ?? null,
            'expires_at' => now()->addDays($request->input('expires_in_days', 7)),
        ]);

        // Creiamo gli "slot" per i documenti richiesti
        foreach ($documentTypes as $type) {
            $documentRequest->items()->create([
                'document_type_id' => $type->id,
            ]);
        }

        // Generiamo il Magic Link (rotta web pubblica di UnicoDoc)
        $uploadUrl = route('guest.dossier.upload', ['token' => $documentRequest->id]);

        return response()->json([
            'message' => 'Richiesta creata con successo.',
            'request_id' => $documentRequest->id,
            'upload_url' => $uploadUrl,
            'expires_at' => $documentRequest->expires_at
        ]);
    }
}
