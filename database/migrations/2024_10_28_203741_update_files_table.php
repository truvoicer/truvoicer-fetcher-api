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
        Schema::table('files', function (Blueprint $table) {
            $table->renameColumn('path', 'full_path');
            $table->renameColumn('file_type', 'type');
            $table->renameColumn('file_size', 'size');
            $table->string('rel_path')->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->renameColumn('full_path', 'path');
            $table->renameColumn('type', 'file_type');
            $table->renameColumn('size', 'file_size');
            $table->dropColumn('rel_path');
        });
    }
};
