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
            $table->boolean('show_in_response')->default(false);
            $table->boolean('list_item')->default(false);
            $table->boolean('has_array_value')->default(false);
            $table->longText('array_keys')->nullable();
            $table->string('return_data_type')->nullable();
            $table->boolean('prepend_extra_data')->default(false);
            $table->string('prepend_extra_data_value')->nullable();
            $table->boolean('append_extra_data')->default(false);
            $table->string('append_extra_data_value')->nullable();
            $table->boolean('is_service_request')->default(false);
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
