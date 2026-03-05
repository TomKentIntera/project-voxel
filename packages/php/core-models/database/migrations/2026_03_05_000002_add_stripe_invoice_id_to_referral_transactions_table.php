<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_transactions', function (Blueprint $table): void {
            $table->string('stripe_invoice_id')->nullable()->after('amount');
            $table->index('stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('referral_transactions', function (Blueprint $table): void {
            $table->dropIndex(['stripe_invoice_id']);
            $table->dropColumn('stripe_invoice_id');
        });
    }
};
