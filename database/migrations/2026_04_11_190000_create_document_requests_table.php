<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Usiamo UUID così funge anche da Token per il Magic Link

            // Chi deve caricare i documenti?
            $table->uuidMorphs('documentable');  // Es. App\Models\Client, ID: 123
            $table->string('sender_email')->nullable();  // Utile se il BPM ci passa la mail

            // Metadati del processo BPM
            $table->string('bpm_process_id')->nullable();
            $table->string('bpm_task_id')->nullable();

            // Stato della richiesta
            $table->enum('status', ['PENDING', 'PARTIAL', 'COMPLETED', 'EXPIRED'])->default('PENDING');
            $table->timestamp('expires_at');  // Il link scade!

            // Flag per notificare la UI (Filament) o il BPM
            $table->boolean('has_unread_messages')->default(false);

            // Salviamo l'ultimo messaggio ricevuto per comodità
            $table->longText('last_message_received')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
