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
        Schema::create('request_registry_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registry_id');
            $table->string('file_path');
            $table->string('storage_disk')->default('local');
            $table->enum('file_type', ['richiesta', 'documento_identita', 'procura_mandato', 'risposta', 'documentazione_interna'])->default('richiesta');
            $table->enum('ai_validation_status', ['pending', 'approved', 'rejected', 'manual_review'])->default('pending');
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable()->comment('Riferimento DB BPM (User ID)');
            $table->timestamps();

            $table->index('uploaded_by', 'idx_uploaded_by');
            $table->index('registry_id', 'request_registry_attachments_registry_id_foreign');
            $table->foreign('registry_id')->references('id')->on('request_registries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_registry_attachments');
    }
};
