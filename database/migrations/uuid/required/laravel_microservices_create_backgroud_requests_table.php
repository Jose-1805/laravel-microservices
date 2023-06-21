<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('background_requests', function (Blueprint $table) {
            $table->uuid('id')->unique()->comment('Identificador único de cada registro');
            $table->text('event', 60)->comment('Nombre del evento asociado a la solicitud (create_user, send_message, etc)');
            $table->enum('state', [0,1])->comment('Determina el estado actual de la petición: 0 => Petición en cola, 1 => Petición procesada');
            $table->longText('input_data')->comment('Datos de entrada de la petición (datos serializados)');
            $table->longText('output_data')->nullable()->comment('Datos de salida de la petición (datos serializados)');
            $table->foreignUuid('user_id')
                ->nullable()
                ->comment('Relación al usuario asociado a la solicitud')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
