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
    Schema::create('instruments', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->string('category');
        $table->text('description')->nullable();
        $table->string('location');
        $table->string('image')->nullable();
        $table->string('usage_cost')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('instruments');
}
};
