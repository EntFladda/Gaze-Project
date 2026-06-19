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
        Schema::table('student_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('student_answers', 'wrong_attempts')) {
                $table->unsignedInteger('wrong_attempts')->default(0)->after('help_requested_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            if (Schema::hasColumn('student_answers', 'wrong_attempts')) {
                $table->dropColumn('wrong_attempts');
            }
        });
    }
};
