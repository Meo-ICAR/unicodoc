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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id()->comment('ID intero autoincrementante');
            $table->string('name', 255)->nullable()->comment('Nome documento');
            $table->string('description', 255)->nullable()->comment('Descrizione aggiuntiva');
            $table->string('code', 255)->nullable()->comment('Codice univoco documento menomico es CI = Carta Identita VISURA = Visura aziendale CCIA');
            $table->string('codegroup', 255)->nullable()->comment('Raggruppa documenti simili es. Documento di riconoscimento');
            $table->string('slug', 255)->unique();
            $table->string('regex_pattern', 255)->nullable();
            $table->integer('priority')->default(0);
            $table->string('phase', 255)->nullable()->comment('Fase di processo - es: "Pre-contrattuale", "Post-contrattuale"');
            $table->boolean('is_person')->default(1)->comment('Documento inerente Persona o azienda');
            $table->boolean('is_signed')->default(0)->comment('Indica se il documento deve essere firmato');
            $table->boolean('is_monitored')->default(0)->comment('Indica se la scadenza documento deve essere monitorata nel tempo');
            $table->boolean('is_company')->default(0);
            $table->boolean('is_employee')->default(0);
            $table->boolean('is_agent')->default(0);
            $table->boolean('is_principal')->default(0);
            $table->boolean('is_client')->default(0);
            $table->boolean('is_practice')->default(0);
            $table->integer('duration')->nullable()->comment('Validità dal rilascio in giorni');
            $table->string('emitted_by', 255)->nullable()->comment('Ente di rilascio');
            $table->boolean('is_sensible')->default(0)->comment('Indica se contiene dati sensibili');
            $table->boolean('is_template')->default(0)->comment('Indica se forniamo noi il documento');
            $table->boolean('is_stored')->default(0)->comment('Indica se il documento deve avere conservazione sostitutiva');
            $table->string('regex', 255)->nullable()->comment('Pattern regex per classificazione automatica documenti');
            $table->boolean('is_endmonth')->default(0)->comment('Approssima data a fine mese');
            $table->boolean('is_AiAbstract')->default(0)->comment('Ask AI to make abstract');
            $table->boolean('is_AiCheck')->default(0)->comment('AI conformity required');
            $table->text('AiPattern')->nullable()->comment('How AI can detect document is of this type');
            $table->timestamps();
            $table->softDeletes();
            $table->userstamps();
            $table->softUserstamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
