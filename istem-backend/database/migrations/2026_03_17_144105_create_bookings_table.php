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
    Schema::create('bookings', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('instrument_id');

        $table->enum('user_type', ['student', 'employee']);
        $table->string('name');
        $table->string('identifier');

        $table->string('department');
        $table->string('program_or_school');

        $table->string('project_title');
        $table->boolean('confidential_project');

        $table->date('start_date');
        $table->date('end_date');
        $table->string('user_email');
        $table->string('email')->nullable();

        $table->enum('status', ['pending','approved','rejected','completed'])->default('pending');

        $table->timestamps();

        $table->foreign('instrument_id')->references('id')->on('instruments');

        $table->index('instrument_id');
        $table->index(['start_date','end_date']);
        $table->index('status');
    });
}

public function down()
{
    Schema::dropIfExists('bookings');
}
};
