<?php

use Truvoicer\TruFetcherGet\Services\ApiServices\RateLimitService;
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
        Schema::create('provider_rate_limits', function (Blueprint $table) {
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            RateLimitService::generateRateLimitTable($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_rate_limits');
    }
};
