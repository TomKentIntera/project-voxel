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
        Schema::create('curse_mod_versions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('curseId');
            $table->bigInteger('modId');
            $table->bigInteger('gameId');
            $table->string('gameVersion');
            $table->boolean('available')->default(true);
            $table->string('name');
            $table->string('fileName');
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
        Schema::dropIfExists('curse_mod_versions');
    }
};
