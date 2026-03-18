<?php

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
        if (!Schema::hasColumn('bookings', 'user_email')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('user_email')->after('end_date')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'user_email')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('user_email');
            });
        }
    }
};
