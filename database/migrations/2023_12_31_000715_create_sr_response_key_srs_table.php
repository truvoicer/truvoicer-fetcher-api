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
        Schema::create('sr_response_key_srs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sr_response_key_id')->constrained('sr_response_keys')->onDelete('cascade');
            $table->foreignId('sr_id')->constrained('srs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_response_key_srs');
    }
};
