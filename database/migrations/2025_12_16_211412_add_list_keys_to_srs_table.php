<?php

use App\Enums\Api\ApiListKey;
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
        Schema::table('srs', function (Blueprint $table) {
            // Rename columns instead of dropping and creating new ones
            $table->renameColumn('items_array_key', ApiListKey::LIST_KEY->value);
            $table->renameColumn('item_repeater_key', ApiListKey::LIST_ITEM_REPEATER_KEY->value);

            // Rename the format options columns
            $table->renameColumn('items_array_format_options', ApiListKey::LIST_FORMAT_OPTIONS->value);
            $table->renameColumn('items_array_format_preg_match', ApiListKey::LIST_FORMAT_OPTION_PREG_MATCH->value);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('srs', function (Blueprint $table) {
            // Reverse the column renames
            $table->renameColumn(ApiListKey::LIST_KEY->value, 'items_array_key');
            $table->renameColumn(ApiListKey::LIST_ITEM_REPEATER_KEY->value, 'item_repeater_key');
            $table->renameColumn(ApiListKey::LIST_FORMAT_OPTIONS->value, 'items_array_format_options');
            $table->renameColumn(ApiListKey::LIST_FORMAT_OPTION_PREG_MATCH->value, 'items_array_format_preg_match');
        });
    }
};
