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
        Schema::table('challenge_results', function (Blueprint $table) {
            $table->integer('focus_percentage')->nullable()->after('wrong_answers');
            $table->integer('unfocused_count')->nullable()->after('focus_percentage');
            $table->integer('focused_duration')->nullable()->after('unfocused_count');
            $table->integer('unfocused_duration')->nullable()->after('focused_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenge_results', function (Blueprint $table) {
            $table->dropColumn([
                'focus_percentage',
                'unfocused_count',
                'focused_duration',
                'unfocused_duration',
            ]);
        });
    }
};
