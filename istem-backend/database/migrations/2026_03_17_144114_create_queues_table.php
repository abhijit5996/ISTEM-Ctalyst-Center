<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('queues', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('instrument_id');
        $table->string('booking_id')->nullable();

        $table->string('user_name');
        $table->string('email');

        // Use `queue_position` to match the Eloquent model and controllers
        $table->integer('queue_position');

        $table->timestamps();

        $table->foreign('instrument_id')->references('id')->on('instruments');
    });
}

public function down()
{
    Schema::dropIfExists('queues');
}
};
