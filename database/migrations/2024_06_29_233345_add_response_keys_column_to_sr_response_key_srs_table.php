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
        Schema::table('sr_response_key_srs', function (Blueprint $table) {
            $table->json('response_keys')->nullable()->after('sr_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_response_key_srs', function (Blueprint $table) {
            $table->dropColumn('response_keys');
        });
    }
};
