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

        Schema::table('sr_schedules', function (Blueprint $table) {
            $table->boolean('execute_immediately')->default(false)->nullable()->change();
            $table->boolean('forever')->default(false)->nullable()->change();
            $table->boolean('every_day')->default(false)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_schedules', function (Blueprint $table) {
            $table->boolean('execute_immediately')->default(true)->nullable()->change();
            $table->boolean('forever')->default(true)->nullable()->change();
            $table->boolean('every_day')->default(true)->nullable()->change();
        });
    }
};
