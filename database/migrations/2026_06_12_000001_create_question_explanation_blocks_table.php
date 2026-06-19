<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_explanation_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('content')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        DB::table('questions')->orderBy('id')->chunkById(100, function ($questions): void {
            foreach ($questions as $question) {
                $order = 1;

                if (filled($question->explanation_text ?? null)) {
                    DB::table('question_explanation_blocks')->insert([
                        'question_id' => $question->id,
                        'type' => 'text',
                        'content' => $question->explanation_text,
                        'image_path' => null,
                        'sort_order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $images = Schema::hasTable('question_explanation_images')
                    ? DB::table('question_explanation_images')->where('question_id', $question->id)->orderBy('sort_order')->orderBy('id')->get()
                    : collect();

                if ($images->isEmpty() && filled($question->explanation_image ?? null)) {
                    $images = collect([(object) ['image_path' => $question->explanation_image]]);
                }

                foreach ($images as $image) {
                    if (blank($image->image_path ?? null)) {
                        continue;
                    }

                    DB::table('question_explanation_blocks')->insert([
                        'question_id' => $question->id,
                        'type' => 'image',
                        'content' => null,
                        'image_path' => $image->image_path,
                        'sort_order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_blocks');
    }
};
