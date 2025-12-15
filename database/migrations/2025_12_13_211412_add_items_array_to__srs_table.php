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
            $table->string('items_array_key')->nullable()->after('type');
            $table->string('item_repeater_key')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('srs', function (Blueprint $table) {
            $table->dropColumn('items_array_key');
            $table->dropColumn('item_repeater_key');
        });
    }
};
