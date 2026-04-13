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
        Schema::create('classification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();

            $table->foreignId('predicted_type_id')->nullable()->constrained('document_types');
            $table->foreignId('actual_type_id')->constrained('document_types');

            $table->string('classifier_used')->default('ai');  // 'ai' o 'regex'
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->boolean('is_override')->default(false);  // True se l'utente ha corretto l'AI
            $table->unsignedTinyInteger('user_id')->nullable();
            //  $table->foreignId('user_id')->nullable()->constrained('users'); // Chi ha validato/corretto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classification_logs');
    }
};
