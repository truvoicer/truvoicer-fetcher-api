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
            //change table column name to request_response_keys
            $table->renameColumn('response_keys', 'request_response_keys');
//            $table->json('response_response_keys')->nullable()->after('request_response_keys');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_response_key_srs', function (Blueprint $table) {
            $table->renameColumn('request_response_keys', 'response_keys');
//            $table->dropColumn('response_response_keys');
        });
    }
};
