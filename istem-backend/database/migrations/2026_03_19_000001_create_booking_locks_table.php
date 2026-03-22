<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('booking_locks', function (Blueprint $table) {
            $table->id();
            $table->string('instrument_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('email');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_locks');
    }
};
