<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (!Schema::hasColumn('queues', 'user_id')) {
                $table->string('user_id')->nullable()->after('instrument_id');
            }

            if (!Schema::hasColumn('queues', 'date')) {
                $table->date('date')->nullable()->after('email');
            }

            if (!Schema::hasColumn('queues', 'time_slot')) {
                $table->string('time_slot')->nullable()->after('date');
            }

            if (!Schema::hasColumn('queues', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('queue_position');
            }

            // Prevent duplicate queue entries for the same user & slot
            $table->unique(['instrument_id', 'date', 'time_slot', 'user_id'], 'queues_unique_user_slot');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('queues', 'time_slot')) {
                $table->dropColumn('time_slot');
            }

            if (Schema::hasColumn('queues', 'date')) {
                $table->dropColumn('date');
            }

            if (Schema::hasColumn('queues', 'user_id')) {
                $table->dropColumn('user_id');
            }

            $table->dropUnique('queues_unique_user_slot');
        });
    }
};
