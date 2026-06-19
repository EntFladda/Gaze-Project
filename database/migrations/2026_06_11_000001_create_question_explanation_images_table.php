<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_explanation_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        if (Schema::hasColumn('questions', 'explanation_image')) {
            DB::table('questions')
                ->whereNotNull('explanation_image')
                ->where('explanation_image', '!=', '')
                ->orderBy('id')
                ->chunkById(100, function ($questions): void {
                    foreach ($questions as $question) {
                        DB::table('question_explanation_images')->insert([
                            'question_id' => $question->id,
                            'image_path' => $question->explanation_image,
                            'sort_order' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_images');
    }
};
