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
        Schema::create('request_registry_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registry_id');
            $table->unsignedBigInteger('process_id')->comment('Riferimento DB BPM');
            $table->unsignedBigInteger('process_task_id')->nullable()->comment('Riferimento DB BPM');
            $table->string('outcome')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('process_id', 'idx_process_id');
            $table->index('process_task_id', 'idx_process_task_id');
            $table->index('registry_id', 'request_registry_processes_registry_id_foreign');
            $table->foreign('registry_id')->references('id')->on('request_registries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_registry_processes');
    }
};
