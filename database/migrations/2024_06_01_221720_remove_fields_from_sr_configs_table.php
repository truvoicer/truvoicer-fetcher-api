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
        Schema::table('sr_configs', function (Blueprint $table) {
            $table->dropColumn('value_type');
            $table->dropColumn('value_choices');
            $table->bigInteger('property_id')->nullable()->unsigned();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_configs', function (Blueprint $table) {
            $table->string('value_type');
            $table->json('value_choices')->nullable();
            $table->dropForeign(['property_id']);
            $table->dropColumn('property_id');
        });
    }
};
