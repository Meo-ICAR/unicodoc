<?php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Services\Classification\ClassificationOrchestratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RunDocumentClassification implements ShouldQueue  // Esecuzione asincrona
{
    use InteractsWithQueue;

    public function __construct(
        protected ClassificationOrchestratorService $orchestrator
    ) {}

    public function handle(DocumentUploaded $event): void
    {
        // Se il documento ha già un tipo (es. forzato dall'utente al caricamento), saltiamo
        if ($event->document->document_type_id) {
            return;
        }

        // Avvia la pipeline: Regex -> AI -> DB Update
        $this->orchestrator->process($event->document);
    }
}
