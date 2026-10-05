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
        Schema::create('request_registry_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registry_id');
            $table->dateTime('action_date');
            $table->enum('action_type', ['assegnazione', 'inoltro', 'risposta_preliminare', 'evasione', 'estensione_termini', 'reclamo_interno', 'ai_validation', 'email_inviata', 'documento_rifiutato']);
            $table->text('description');
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->comment('Riferimento DB BPM (User ID)');
            $table->timestamps();

            $table->index('performed_by', 'idx_performed_by');
            $table->index('registry_id', 'request_registry_actions_registry_id_foreign');
            $table->foreign('registry_id')->references('id')->on('request_registries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_registry_actions');
    }
};
