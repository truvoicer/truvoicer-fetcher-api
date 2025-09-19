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
        Schema::create('sr_response_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sr_id')->constrained('srs')->onDelete('cascade');
            $table->foreignId('s_response_key_id')->constrained('s_response_keys')->onDelete('cascade');
            $table->string('value');
            $table->boolean('show_in_response')->default(false);
            $table->boolean('list_item')->default(false);
            $table->longText('array_keys')->nullable();
            $table->string('prepend_extra_data_value')->nullable();
            $table->string('append_extra_data_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_response_keys');
    }
};
