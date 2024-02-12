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
            $table->dropColumn('locked');
            $table->dropColumn('arguments');
            $table->boolean('disable_child_srs')->default(false)->after('disabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_schedules', function (Blueprint $table) {
            $table->boolean('locked')->default(false)->nullable();
            $table->string('arguments')->nullable();
            $table->dropColumn('disable_child_srs');
        });
    }
};
