<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('document_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained(); // Es. KYC_ID
            
            // Quando il mittente carica il file, lo leghiamo qui per sapere che l'ha soddisfatto
            $table->foreignUuid('fulfilled_by_document_id')->nullable()->constrained('documents');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_request_items');
    }
};
