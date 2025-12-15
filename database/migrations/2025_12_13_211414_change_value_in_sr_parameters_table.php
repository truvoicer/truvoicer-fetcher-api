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
        Schema::table('sr_parameters', function (Blueprint $table) {
            $table->string('encode_to')->nullable()->after('value');
            $table->string('encode_from')->nullable()->after('value');
            $table->boolean('encode_value')->default(false)->nullable()->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_parameters', function (Blueprint $table) {
            $table->dropColumn('encode_value');
            $table->dropColumn('encode_from');
            $table->dropColumn('encode_to');
        });
    }
};
