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
        Schema::table('provider_properties', function (Blueprint $table) {
            $table->json('array_value')->nullable()->after('value');
            $table->string('value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_properties', function (Blueprint $table) {
            $table->dropColumn('array_value');
            $table->string('value')->nullable(false)->change();
        });
    }
};
