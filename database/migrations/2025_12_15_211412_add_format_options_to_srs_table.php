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
        Schema::table('srs', function (Blueprint $table) {
            $table->json('items_array_format_options')->nullable()->after('pagination_type');
            $table->string('items_array_format_preg_match')->nullable()->after('pagination_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('srs', function (Blueprint $table) {
            $table->dropColumn('items_array_format_options');
            $table->dropColumn('items_array_format_preg_match');
        });
    }
};
