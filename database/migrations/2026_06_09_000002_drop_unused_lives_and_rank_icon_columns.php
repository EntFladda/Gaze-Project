<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'lives')) {
                $table->dropColumn('lives');
            }

            if (Schema::hasColumn('students', 'next_life_at')) {
                $table->dropColumn('next_life_at');
            }
        });

        Schema::table('ranks', function (Blueprint $table) {
            if (Schema::hasColumn('ranks', 'icon')) {
                $table->dropColumn('icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'lives')) {
                $table->integer('lives')->default(5);
            }

            if (! Schema::hasColumn('students', 'next_life_at')) {
                $table->timestamp('next_life_at')->nullable();
            }
        });

        Schema::table('ranks', function (Blueprint $table) {
            if (! Schema::hasColumn('ranks', 'icon')) {
                $table->string('icon')->nullable();
            }
        });
    }
};
