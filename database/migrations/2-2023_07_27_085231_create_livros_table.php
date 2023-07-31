<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('livros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('autor');
            $table->foreignId('endereco_id')->unique()->constrained('enderecos')->onUpdate('cascade');
            $table->string('editora');
            $table->string('ano_de_publicacao');
            $table->text('descricao');
            $table->integer('paginas');
            $table->char('isbn', 13)->unique();
            $table->string('capa');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('livros');
    }
};
