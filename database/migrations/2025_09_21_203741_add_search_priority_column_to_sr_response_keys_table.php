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
            $table->integer('search_priority')->default(0)->after('searchable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_response_keys', function (Blueprint $table) {
            $table->dropColumn('search_priority');
        });
    }
};
