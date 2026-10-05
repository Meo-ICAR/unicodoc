<?php
namespace App\Listeners;

use App\Events\DocumentClassified;  // (Nuovo evento da lanciare nell'Orchestratore)
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use Illuminate\Contracts\Queue\ShouldQueue;

class FulfillDocumentRequest implements ShouldQueue
{
    public function handle(DocumentClassified $event): void
    {
        $document = $event->document;

        // Se l'AI non ha capito cos'è, o se non è legato a un'entità, ci fermiamo
        if (!$document->document_type_id || !$document->documentable_id) {
            return;
        }

        // Cerchiamo un "Item" di un Dossier aperto che aspetta questo tipo di documento per questa entità
        $pendingItem = DocumentRequestItem::where('document_type_id', $document->document_type_id)
            ->whereNull('fulfilled_by_document_id')
            ->whereHas('request', function ($query) use ($document) {
                $query
                    ->where('documentable_id', $document->documentable_id)
                    ->where('documentable_type', $document->documentable_type)
                    ->whereIn('status', ['PENDING', 'PARTIAL']);
            })
            ->first();

        if ($pendingItem) {
            // MATCH TROVATO! L'utente ha mandato il documento giusto via mail.
            $pendingItem->update(['fulfilled_by_document_id' => $document->id]);

            // Controlliamo se ora il Dossier è completo
            $this->checkIfRequestIsComplete($pendingItem->document_request_id);
        }
    }

    protected function checkIfRequestIsComplete(string $requestId): void
    {
        $request = DocumentRequest::with('items')->find($requestId);

        $unfulfilledCount = $request->items()->whereNull('fulfilled_by_document_id')->count();

        if ($unfulfilledCount === 0) {
            $request->update(['status' => 'COMPLETED']);

            // Qui notifichi il BPM (es. tramite Webhook) che il task può avanzare!
            \Illuminate\Support\Facades\Http::post(config('services.bpm.webhook_url'), [
                'event' => 'document_request_completed',
                'bpm_task_id' => $request->bpm_task_id,
            ]);
        } else {
            $request->update(['status' => 'PARTIAL']);
        }
    }
}
