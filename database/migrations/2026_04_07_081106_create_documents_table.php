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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('UUID del documento');
            $table->uuid('company_id')->nullable()->comment('ID Tenant');

            // Relazione Polimorfica
            $table->string('documentable_type');
            $table->uuid('documentable_id');

            // Categorizzazione
            $table->unsignedBigInteger('document_type_id')->nullable()->comment('FK Tipo di documento');
            $table->string('name', 255)->nullable()->comment('Titolo logico del documento');
            $table->string('docnumber', 255)->nullable()->comment('Numero protocollo/documento');
            $table->string('spatie_collection', 100)->default('default')->comment('Nome della collection Spatie');
            $table->string('document_url')->default('default')->comment('URL del documento sul web');

            // Stati (Enum)
            $table->string('status', 50)->default('uploaded')->comment('Enum: uploaded, verified, rejected, expired');
            $table->string('sync_status', 50)->default('local')->comment('Enum: local, syncing, synced, failed');

            $table->string('source_app')->default('local')->comment('Applicazione di origine (local, sharepoint, etc)');
            // SharePoint Integrazione
            $table->string('app_id', 255)->nullable();
            $table->string('app_drive_id', 255)->nullable();
            $table->string('app_etag', 255)->nullable();

            // Dati AI e OCR
            $table->longText('extracted_text')->nullable()->comment('Testo OCR');
            $table->json('metadata')->nullable()->comment('Dati chiave estratti in JSON');
            $table->text('ai_abstract')->nullable()->comment('Riassunto AI');
            $table->tinyInteger('ai_confidence_score')->unsigned()->nullable()->comment('Affidabilità (0-100)');

            // Flag Booleani
            $table->boolean('is_template')->default(0)->comment("Modello fornito dall'azienda");
            $table->boolean('is_signed')->default(0)->comment('Il documento è stato firmato');
            $table->boolean('is_unique')->default(0)->comment('Unico ammesso in questa collection');
            $table->boolean('is_endMonth')->default(0)->comment('Approssima scadenza a fine mese');

            // Date ed Enti
            $table->string('emitted_by', 255)->nullable()->comment('Ente di rilascio (es. Comune di Roma)');
            $table->date('emitted_at')->nullable()->comment('Data emissione');
            $table->date('expires_at')->nullable()->comment('Data scadenza');
            $table->timestamp('delivered_at')->nullable()->comment('Data di consegna');
            $table->timestamp('signed_at')->nullable()->comment('Data firma');

            // Note e Descrizioni
            $table->text('description')->nullable()->comment('Descrizione pubblica/utente');
            $table->text('internal_notes')->nullable()->comment('Note interne (solo admin)');
            $table->text('rejection_note')->nullable()->comment('Motivazione rifiuto');

            // Tracciamento Utenti (Audit)
            $table->unsignedBigInteger('user_id')->nullable()->comment('Utente/Cliente intestatario');
            $table->unsignedBigInteger('uploaded_by')->nullable()->comment("Chi ha eseguito l'upload");
            $table->unsignedBigInteger('verified_by')->nullable()->comment('Admin che ha verificato');
            $table->timestamp('verified_at')->nullable()->comment('Data verifica');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Timestamps e SoftDeletes
            $table->string('file_hash', 64)->nullable()->comment('SHA-256 per prevenire duplicati');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['documentable_type', 'documentable_id'], 'doc_documentable_index');
            $table->index('company_id', 'doc_company_id_index');
            $table->index('expires_at', 'doc_expires_at_index');
            $table->index('status', 'doc_status_index');

            // Foreign Keys
            // Nota: le tabelle companies e users sono su connessione 'bpm', quindi non hanno foreign keys
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
