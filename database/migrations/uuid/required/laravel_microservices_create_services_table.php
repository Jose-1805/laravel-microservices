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
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->unique()->comment('Identificador único de cada registro');
            $table->text('name', 100)->unique()->comment('Nombre del micro servicio registrado');
            $table->text('base_uri', 250)->comment('Url base para conectarse al servicio');
            $table->text('path', 100)->comment('Path base para acceder a las funciones básicas CRUD del servicio');
            $table->text('access_token', 250)->comment('Token para poder acceder a las funciones del servicio');
            $table->text('queue', 60)->comment('Cola a la que el servicio se conecta para recibir mensajes u ordenes de ejecución');
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
        Schema::dropIfExists('services');
    }
};
