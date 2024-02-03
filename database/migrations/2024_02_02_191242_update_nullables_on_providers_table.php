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
        Schema::table('providers', function (Blueprint $table) {
            $table->string('api_base_url')->nullable()->change();
            $table->string('access_key')->nullable()->change();
            $table->string('secret_key')->nullable()->change();
            $table->string('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('api_base_url')->nullable(false)->change();
            $table->string('access_key')->nullable(false)->change();
            $table->string('secret_key')->nullable(false)->change();
            $table->string('user_id')->nullable(false)->change();
        });
    }
};
