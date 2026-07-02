<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_results', function (Blueprint $table) {
            if (! $this->hasIndex('challenge_results', 'challenge_results_user_challenge_attempt_idx')) {
                $table->index(['user_id', 'challenge_id', 'attempt_number'], 'challenge_results_user_challenge_attempt_idx');
            }

            if (! $this->hasIndex('challenge_results', 'challenge_results_user_ended_idx')) {
                $table->index(['user_id', 'ended_at'], 'challenge_results_user_ended_idx');
            }

            if (! $this->hasIndex('challenge_results', 'challenge_results_challenge_ended_idx')) {
                $table->index(['challenge_id', 'ended_at'], 'challenge_results_challenge_ended_idx');
            }
        });

        Schema::table('student_answers', function (Blueprint $table) {
            if (! $this->hasIndex('student_answers', 'student_answers_user_challenge_attempt_idx')) {
                $table->index(['user_id', 'challenge_id', 'attempt_number'], 'student_answers_user_challenge_attempt_idx');
            }

            if (! $this->hasIndex('student_answers', 'student_answers_current_question_idx')) {
                $table->index(['user_id', 'challenge_id', 'question_id', 'attempt_number'], 'student_answers_current_question_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            if ($this->hasIndex('student_answers', 'student_answers_current_question_idx')) {
                $table->dropIndex('student_answers_current_question_idx');
            }

            if ($this->hasIndex('student_answers', 'student_answers_user_challenge_attempt_idx')) {
                $table->dropIndex('student_answers_user_challenge_attempt_idx');
            }
        });

        Schema::table('challenge_results', function (Blueprint $table) {
            if ($this->hasIndex('challenge_results', 'challenge_results_challenge_ended_idx')) {
                $table->dropIndex('challenge_results_challenge_ended_idx');
            }

            if ($this->hasIndex('challenge_results', 'challenge_results_user_ended_idx')) {
                $table->dropIndex('challenge_results_user_ended_idx');
            }

            if ($this->hasIndex('challenge_results', 'challenge_results_user_challenge_attempt_idx')) {
                $table->dropIndex('challenge_results_user_challenge_attempt_idx');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return count(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])) > 0;
    }
};
