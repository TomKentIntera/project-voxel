<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_codes', function (Blueprint $table): void {
            $table->unsignedInteger('valid_for_invoice_count')->default(3)->after('referral_percent');
        });
    }

    public function down(): void
    {
        Schema::table('referral_codes', function (Blueprint $table): void {
            $table->dropColumn('valid_for_invoice_count');
        });
    }
};
