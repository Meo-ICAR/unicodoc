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
        Schema::create('audit_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Riferimento DB BPM (User ID)');
            $table->string('target_organism');
            $table->json('included_ids');
            $table->string('zip_file_path');
            $table->enum('status', ['processing', 'ready', 'failed'])->default('processing');
            $table->string('access_pin')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('downloaded_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_exports');
    }
};
