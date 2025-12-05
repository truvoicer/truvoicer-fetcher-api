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
        Schema::create('sr_config_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sr_config_id')
            ->constrained('sr_configs')
            ->cascadeOnDelete();
            $table->morphs('entityable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_config_entities');
    }
};
