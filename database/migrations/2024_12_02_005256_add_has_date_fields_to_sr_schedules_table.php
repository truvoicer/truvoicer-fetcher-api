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
            $table->boolean('has_start_date')->after('priority')->default(false)->nullable();
            $table->boolean('has_end_date')->after('start_date')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_schedules', function (Blueprint $table) {
            $table->dropColumn(['has_start_date', 'has_end_date']);
        });
    }
};
