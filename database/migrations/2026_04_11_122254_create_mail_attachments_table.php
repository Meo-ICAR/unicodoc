<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('mail_message_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type')->default('application/octet-stream');
            $table->unsignedBigInteger('size')->default(0)->comment('Dimensione in byte');
            $table->boolean('is_inline')->default(false)->comment('Allegato inline (es. logo firma)');
            $table->longText('content')->nullable()->comment('Contenuto base64 o path temporaneo');
            $table->foreignUuid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_attachments');
    }
};
