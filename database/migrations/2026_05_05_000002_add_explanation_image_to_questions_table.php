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
        if (Schema::hasColumn('questions', 'explanation_image')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->string('explanation_image')->nullable()->after('explanation_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('questions', 'explanation_image')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('explanation_image');
        });
    }
};
