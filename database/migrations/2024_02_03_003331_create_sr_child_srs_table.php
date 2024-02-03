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
        Schema::create('sr_child_srs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sr_id')->constrained('srs')->onDelete('cascade');
            $table->foreignId('sr_child_id')->constrained('srs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_child_srs');
    }
};
