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
        Schema::create('service_request_response_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->foreignId('service_response_key_id')->constrained('service_response_keys')->onDelete('cascade');
            $table->string('value');
            $table->boolean('show_in_response');
            $table->boolean('list_item');
            $table->boolean('has_array_value');
            $table->longText('array_keys');
            $table->string('return_data_type');
            $table->boolean('prepend_extra_data');
            $table->string('prepend_extra_data_value');
            $table->boolean('append_extra_data');
            $table->string('append_extra_data_value');
            $table->boolean('is_service_request');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_response_keys');
    }
};
