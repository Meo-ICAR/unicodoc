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
        Schema::create('request_email_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registry_id');
            $table->string('recipient');
            $table->string('subject');
            $table->longText('body');
            $table->unsignedBigInteger('sent_by')->nullable()->comment('Riferimento DB BPM (User ID)');
            $table->timestamps();

            $table->index('sent_by', 'idx_sent_by');
            $table->index('registry_id', 'request_email_logs_registry_id_foreign');
            $table->foreign('registry_id')->references('id')->on('request_registries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_email_logs');
    }
};
