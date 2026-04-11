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
        Schema::create('document_scopes', function (Blueprint $table) {
            $table->id()->comment('ID univoco ambito');
            $table->string('name', 50)->unique()->comment("Nome dell'ambito: Privacy, AML, OAM, Istruttoria, Contrattualistica");
            $table->string('description', 255)->nullable()->comment('Descrizione della finalità normativa');
            $table->string('color_code', 7)->default('#6B7280')->comment("Codice colore per i tag nell'interfaccia (Filament Badge)");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_scopes');
    }
};
