<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->uuid('id')->unique()->comment('Identificador único de cada registro');
            $table->enum('type', ['fcm'])->comment('Tipo de token registrado');
            $table->string('token', 250)->unique()->comment('Token registrado');
            $table->foreignUuid('user_id')
                ->comment('Relación al usuario asociado al token')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
