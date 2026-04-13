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
        Schema::create('mail_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('mail_account_id')->constrained()->onDelete('cascade');
            $table->string('message_id')->index()->comment('Unique ID from the mail server');
            $table->string('from_address')->index();
            $table->string('from_name')->nullable();
            $table->string('to_address')->index();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->unsignedBigInteger('associated_employee_id')->nullable()->index();
            $table->unsignedBigInteger('associated_client_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_messages');
    }
};
