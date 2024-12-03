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
        Schema::table('sr_response_keys', function (Blueprint $table) {
            $table->boolean('is_date')->default(false)->after('custom_value');
            $table->string('date_format')->nullable()->after('is_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_response_keys', function (Blueprint $table) {
            $table->dropColumn(['is_date', 'date_format']);
        });
    }
};
