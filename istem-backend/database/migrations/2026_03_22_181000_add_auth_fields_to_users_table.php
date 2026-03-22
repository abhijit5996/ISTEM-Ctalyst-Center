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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('google_id')->nullable()->after('phone');
            $table->string('profile_picture')->nullable()->after('google_id');
            $table->string('otp', 6)->nullable()->after('remember_token');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
            $table->boolean('email_verified')->default(false)->after('email_verified_at');
            $table->string('api_token', 80)->nullable()->unique()->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'google_id',
                'profile_picture',
                'otp',
                'otp_expires_at',
                'email_verified',
                'api_token',
            ]);
        });
    }
};
