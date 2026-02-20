<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('regional_proxies', 'last_active_at')) {
            Schema::table('regional_proxies', function (Blueprint $table): void {
                $table->timestamp('last_active_at')->nullable()->after('token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('regional_proxies', 'last_active_at')) {
            Schema::table('regional_proxies', function (Blueprint $table): void {
                $table->dropColumn('last_active_at');
            });
        }
    }
};
