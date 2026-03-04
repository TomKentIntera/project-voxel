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
        if (Schema::hasTable('subdomains')) {
            return;
        }

        Schema::create('subdomains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->unique()->constrained('servers')->cascadeOnDelete();
            $table->string('prefix', 24);
            $table->string('domain', 255);
            $table->timestamps();

            $table->unique(['prefix', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subdomains');
    }
};
