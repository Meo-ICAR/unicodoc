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
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('protocol')->default('imap'); // imap, outlook, etc.
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('encryption')->nullable(); // ssl, tls, etc.
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_accounts');
    }
};
