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
        Schema::create('sr_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sr_id')->constrained('srs')->onDelete('cascade');
            $table->boolean('execute_immediately')->default(true)->nullable();
            $table->boolean('forever')->default(true)->nullable();
            $table->boolean('disabled')->default(false)->nullable();
            $table->boolean('locked')->default(false)->nullable();
            $table->integer('priority')->default(0)->nullable();
            $table->dateTimeTz('start_date')->nullable();
            $table->dateTimeTz('end_date')->nullable();
            $table->boolean('use_cron_expression')->default(false)->nullable();
            $table->string('cron_expression')->nullable();
            $table->boolean('every_minute')->default(false)->nullable();
            $table->integer('minute')->nullable();
            $table->boolean('every_hour')->default(false)->nullable();
            $table->integer('hour')->nullable();
            $table->boolean('every_day')->default(true)->nullable();
            $table->integer('day')->nullable();
            $table->boolean('every_weekday')->default(false)->nullable();
            $table->integer('weekday')->nullable();
            $table->boolean('every_month')->default(false)->nullable();
            $table->integer('month')->nullable();
            $table->string('arguments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_schedules');
    }
};
