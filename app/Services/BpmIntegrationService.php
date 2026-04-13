<?php

namespace App\Services;

use App\Models\BPM\ProcessTask;
use App\Models\BPM\User as BpmUser;

class BpmIntegrationService
{
    /**
     * Sincronizza lo stato di un task sul DB esterno del BPM tramite Eloquent.
     */
    public function updateBpmTaskStatus(int $taskId, string $status, string $note = ''): void
    {
        // Usa findOrFail così se il task non esiste nel DB BPM lancia subito una 404/Exception
        $task = ProcessTask::findOrFail($taskId);

        $task->update([
            'status' => $status,
            'completed_at' => now(),
            'outcome_notes' => $note,
        ]);
    }

    /**
     * Recupera dati utente dal DB esterno restituendo un modello Eloquent.
     */
    public function getBpmUser(int $userId): BpmUser
    {
        return BpmUser::findOrFail($userId);
    }
}
