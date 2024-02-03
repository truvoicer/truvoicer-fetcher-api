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
            $table->json('parameters')->nullable()->after('arguments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_schedules', function (Blueprint $table) {
            $table->dropColumn('parameters');
        });
    }
};
