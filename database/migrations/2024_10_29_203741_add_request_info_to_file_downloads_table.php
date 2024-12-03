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
        Schema::table('file_downloads', function (Blueprint $table) {
            $table->string('client_ip')->after('download_key');
            $table->string('user_agent')->after('client_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_downloads', function (Blueprint $table) {
            $table->dropColumn(['client_ip', 'user_agent']);
        });
    }
};
