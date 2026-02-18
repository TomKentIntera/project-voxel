<?php

declare(strict_types=1);

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
        Schema::create('server_events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('server_id');
            $table->bigInteger('actor_id')->nullable();
            $table->string('type');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('server_id');
            $table->index('actor_id');
            $table->index(['server_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_events');
    }
};
