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
        Schema::create('request_registries', function (Blueprint $table) {
            $table->id();
            $table->char('company_id', 36)->comment('Riferimento DB BPM');
            $table->string('request_number');
            $table->date('request_date');
            $table->enum('received_via', ['email', 'pec', 'telefono', 'raccomandata', 'portale', 'di_persona'])->default('email');
            $table->enum('requester_type', ['interessato', 'mandatario', 'organismo_vigilanza'])->default('interessato');
            $table->string('requester_name');
            $table->string('requester_contact')->nullable();
            $table->string('mandate_reference')->nullable();
            $table->string('oversight_body_type')->nullable();
            $table->enum('request_type', ['accesso', 'cancellazione', 'rettifica', 'opposizione', 'limitazione', 'portabilita', 'revoca_consenso', 'reclamazione']);
            $table->string('data_subject_type')->nullable();
            $table->unsignedBigInteger('data_subject_id')->nullable();
            $table->text('description');
            $table->enum('status', ['ricevuta', 'in_lavorazione', 'evasa', 'respinta', 'parzialmente_evasa', 'scaduta'])->default('ricevuta');
            $table->date('response_deadline');
            $table->date('response_date')->nullable();
            $table->text('response_summary')->nullable();
            $table->boolean('sla_breach')->default(0);
            $table->boolean('extension_granted')->default(0);
            $table->text('extension_reason')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('Riferimento DB BPM (User ID)');
            $table->unsignedBigInteger('active_process_id')->nullable()->comment('Riferimento DB BPM');
            $table->unsignedBigInteger('process_task_id')->nullable()->comment('Riferimento DB BPM');
            $table->json('bpm_context')->nullable();
            $table->text('notes');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('request_number');
            $table->index('company_id', 'idx_company_id');
            $table->index(['data_subject_type', 'data_subject_id'], 'idx_data_subject');
            $table->index('assigned_to', 'idx_assigned_to');
            $table->index('active_process_id', 'idx_active_process');
            $table->index('process_task_id', 'idx_process_task');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_registries');
    }
};
