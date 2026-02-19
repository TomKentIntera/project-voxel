<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Interadigital\CoreModels\Enums\UserRole;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')
                ->default(UserRole::CUSTOMER->value)
                ->after('password');
            $table->index('role');
        });

        DB::table('users')
            ->whereNull('role')
            ->update(['role' => UserRole::CUSTOMER->value]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
