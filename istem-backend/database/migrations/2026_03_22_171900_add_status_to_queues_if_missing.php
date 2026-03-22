<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (!Schema::hasColumn('queues', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('queue_position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
