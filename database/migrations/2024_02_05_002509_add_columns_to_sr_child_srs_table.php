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
        Schema::table('sr_child_srs', function (Blueprint $table) {
            $table->boolean('response_key_override')->default(false)->after('sr_child_id');
            $table->boolean('config_override')->default(false)->after('sr_child_id');
            $table->boolean('parameter_override')->default(false)->after('sr_child_id');
            $table->boolean('scheduler_override')->default(false)->after('sr_child_id');
            $table->boolean('rate_limits_override')->default(false)->after('sr_child_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_child_srs', function (Blueprint $table) {
            $table->dropColumn('response_key_override');
            $table->dropColumn('config_override');
            $table->dropColumn('parameter_override');
            $table->dropColumn('scheduler_override');
            $table->dropColumn('rate_limits_override');
        });
    }
};
