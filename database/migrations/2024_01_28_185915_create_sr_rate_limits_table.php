<?php

use Truvoicer\TfDbReadCore\Services\ApiServices\RateLimitService;
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
        Schema::create('sr_rate_limits', function (Blueprint $table) {
            $table->foreignId('sr_id')->constrained('srs')->onDelete('cascade');
            $table->boolean('override')->default(false);
            RateLimitService::generateRateLimitTable($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_rate_limits');
    }
};
