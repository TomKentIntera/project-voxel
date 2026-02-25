<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->unsignedBigInteger('ptero_node_id')->nullable()->after('id');
            $table->unsignedBigInteger('ptero_location_id')->nullable()->after('region');
            $table->string('fqdn')->nullable()->after('ip_address');
            $table->string('scheme', 8)->nullable()->after('fqdn');
            $table->boolean('behind_proxy')->default(false)->after('scheme');
            $table->boolean('maintenance_mode')->default(false)->after('behind_proxy');
            $table->unsignedInteger('memory')->nullable()->after('maintenance_mode');
            $table->integer('memory_overallocate')->default(0)->after('memory');
            $table->unsignedInteger('disk')->nullable()->after('memory_overallocate');
            $table->integer('disk_overallocate')->default(0)->after('disk');
            $table->unsignedInteger('upload_size')->default(100)->after('disk_overallocate');
            $table->unsignedSmallInteger('daemon_sftp')->nullable()->after('upload_size');
            $table->unsignedSmallInteger('daemon_listen')->nullable()->after('daemon_sftp');
            $table->string('allocation_ip', 45)->nullable()->after('daemon_listen');
            $table->string('allocation_alias')->nullable()->after('allocation_ip');
            $table->json('allocation_ports')->nullable()->after('allocation_alias');
            $table->string('sync_status', 32)->default('pending')->after('allocation_ports');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->timestamp('synced_at')->nullable()->after('sync_error');

            $table->index('ptero_node_id');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->dropIndex(['ptero_node_id']);
            $table->dropIndex(['sync_status']);

            $table->dropColumn([
                'ptero_node_id',
                'ptero_location_id',
                'fqdn',
                'scheme',
                'behind_proxy',
                'maintenance_mode',
                'memory',
                'memory_overallocate',
                'disk',
                'disk_overallocate',
                'upload_size',
                'daemon_sftp',
                'daemon_listen',
                'allocation_ip',
                'allocation_alias',
                'allocation_ports',
                'sync_status',
                'sync_error',
                'synced_at',
            ]);
        });
    }
};
