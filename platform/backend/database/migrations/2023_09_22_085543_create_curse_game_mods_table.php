<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('curse_game_mods', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('curseModId');
            $table->bigInteger('gameId');
            $table->string('name');
            $table->string('slug');
            $table->text('summary');
            $table->text('curseLink');
            $table->boolean('available')->default(true);
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
        Schema::dropIfExists('curse_game_mods');
    }
};
