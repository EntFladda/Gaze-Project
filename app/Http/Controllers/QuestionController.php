<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Challenge;
use App\Models\Question;
use App\Models\QuestionBlock;
use App\Models\QuestionExplanationBlock;
use App\Models\QuestionExplanationImage;
use App\Support\QuestionQualityAuditor;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $challenges = $this->orderedChallenges()
            ->withCount('questions')
            ->get();
        $selectedChallenge = $request->query('challenge_id');

        /** @var LengthAwarePaginator $questions */
        $questions = Question::query()
            ->with(['challenge.section', 'answers', 'blocks', 'explanationImages', 'explanationBlocks'])
            ->when($selectedChallenge, fn($query) => $query->where('challenge_id', $selectedChallenge))
            ->leftJoin('challenges', 'questions.challenge_id', '=', 'challenges.id')
            ->leftJoin('sections', 'challenges.section_id', '=', 'sections.id')
            ->orderBy('sections.order')
            ->orderBy('challenges.id')
            ->orderBy('questions.id')
            ->select('questions.*')
            ->paginate(10);
        $questions->withQueryString();

        $questions->getCollection()->transform(function (Question $question) {
            $question->setAttribute('quality_issues', QuestionQualityAuditor::issues($question));

            return $question;
        });

        $qualitySummary = QuestionQualityAuditor::summary($challenges);

        return view('lecturer.questions.index', compact('questions', 'challenges', 'selectedChallenge', 'qualitySummary'));
    }

    public function show($id)
    {
        $question = Question::with('answers', 'challenge', 'blocks', 'explanationImages', 'explanationBlocks')->findOrFail($id);

        return view('lecturer.questions.show', compact('question'));
    }

    public function create(Request $request)
    {
        $challenges = $this->orderedChallenges()->get();
        $selectedChallengeId = $request->query('challenge_id');

        return view('lecturer.questions.create', compact('challenges', 'selectedChallengeId'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateQuestion($request);

        $data = collect($validated)->only([
            'challenge_id',
            'type',
            'description',
            'question_text',
            'help_text',
            'explanation_text',
            'explanation_image',
            'score',
            'exp',
        ])->toArray();
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['question_text'] = trim((string) $data['question_text']);
        $data['help_text'] = trim((string) ($data['help_text'] ?? ''));
        $data['explanation_text'] = $this->compiledExplanationText($request) ?: trim((string) ($data['explanation_text'] ?? ''));

        if ($request->hasFile('question_image')) {
            $data['question_image'] = $request->file('question_image')->store('questions', 'public');
            $this->syncPublicStorageFile($data['question_image']);
        }

        if ($request->hasFile('explanation_image')) {
            $data['explanation_image'] = $request->file('explanation_image')->store('question-explanations', 'public');
            $this->syncPublicStorageFile($data['explanation_image']);
        }

        $question = Question::create($data);
        $this->syncExplanationBlocks($request, $question);
        $this->syncExplanationImages($request, $question);
        $this->syncQuestionBlocks($request, $question);
        $this->syncAnswers($request, $question);
        $question->challenge->recalculateTotals();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Soal berhasil dibuat.',
                'redirect_url' => route('lecturer.questions.index'),
            ]);
        }

        return redirect()->route('lecturer.questions.index')->with('success', 'Soal berhasil dibuat.');
    }

    public function edit($id)
    {
        $question = Question::with('answers', 'blocks', 'explanationImages', 'explanationBlocks')->findOrFail($id);
        $challenges = $this->orderedChallenges()->get();

        return view('lecturer.questions.edit', compact('question', 'challenges'));
    }

    protected function orderedChallenges()
    {
        return Challenge::query()
            ->with('section')
            ->leftJoin('sections', 'challenges.section_id', '=', 'sections.id')
            ->orderBy('sections.order')
            ->orderBy('challenges.id')
            ->select('challenges.*');
    }

    public function update(Request $request, Question $question)
    {
        $validated = $this->validateQuestion($request);

        $data = collect($validated)->only([
            'challenge_id',
            'type',
            'description',
            'question_text',
            'help_text',
            'explanation_text',
            'explanation_image',
            'score',
            'exp',
        ])->toArray();
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['question_text'] = trim((string) $data['question_text']);
        $data['help_text'] = trim((string) ($data['help_text'] ?? ''));
        $data['explanation_text'] = $this->compiledExplanationText($request) ?: trim((string) ($data['explanation_text'] ?? ''));

        if ($request->boolean('delete_question_image') && $question->question_image) {
            Storage::disk('public')->delete($question->question_image);
            $this->deletePublicStorageFile($question->question_image);
            $data['question_image'] = null;
        }

        if ($request->hasFile('question_image')) {
            if ($question->question_image) {
                Storage::disk('public')->delete($question->question_image);
                $this->deletePublicStorageFile($question->question_image);
            }

            $data['question_image'] = $request->file('question_image')->store('questions', 'public');
            $this->syncPublicStorageFile($data['question_image']);
        } elseif (! array_key_exists('question_image', $data)) {
            $data['question_image'] = $question->question_image;
        }

        if ($request->boolean('delete_explanation_image') && $question->explanation_image) {
            Storage::disk('public')->delete($question->explanation_image);
            $this->deletePublicStorageFile($question->explanation_image);
            $data['explanation_image'] = null;
        }

        if ($request->hasFile('explanation_image')) {
            if ($question->explanation_image) {
                Storage::disk('public')->delete($question->explanation_image);
                $this->deletePublicStorageFile($question->explanation_image);
            }

            $data['explanation_image'] = $request->file('explanation_image')->store('question-explanations', 'public');
            $this->syncPublicStorageFile($data['explanation_image']);
        } elseif (! array_key_exists('explanation_image', $data)) {
            $data['explanation_image'] = $question->explanation_image;
        }

        $retainedBlockImages = collect($request->input('old_block_images', []))
            ->filter()
            ->values()
            ->all();
        $retainedAnswerImages = collect($request->input('old_answer_images', []))
            ->filter()
            ->values()
            ->all();

        $this->deleteQuestionBlocks($question, $retainedBlockImages);
        $this->deleteAnswerImages($question, $retainedAnswerImages);
        $question->answers()->delete();
        $question->update($data);

        $this->syncExplanationBlocks($request, $question);
        $this->syncExplanationImages($request, $question);
        $this->syncQuestionBlocks($request, $question);
        $this->syncAnswers($request, $question);
        $question->challenge->recalculateTotals();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Soal berhasil diperbarui.',
                'redirect_url' => route('lecturer.questions.index'),
            ]);
        }

        return redirect()->route('lecturer.questions.index')->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $question = Question::with('answers', 'challenge', 'blocks', 'explanationImages', 'explanationBlocks')->findOrFail($id);

        if ($question->question_image) {
            Storage::disk('public')->delete($question->question_image);
            $this->deletePublicStorageFile($question->question_image);
        }

        if ($question->explanation_image) {
            Storage::disk('public')->delete($question->explanation_image);
            $this->deletePublicStorageFile($question->explanation_image);
        }

        $this->deleteExplanationImages($question);

        foreach ($question->answers as $answer) {
            if ($answer->answer_image) {
                Storage::disk('public')->delete($answer->answer_image);
                $this->deletePublicStorageFile($answer->answer_image);
            }
        }

        $this->deleteQuestionBlocks($question);
        $this->deleteExplanationBlocks($question);
        $challenge = $question->challenge;
        $question->answers()->delete();
        $question->delete();
        $challenge?->recalculateTotals();

        return redirect()->route('lecturer.questions.index')->with('success', 'Soal berhasil dihapus.');
    }

    protected function validateQuestion(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'challenge_id' => 'required|exists:challenges,id',
            'type' => 'required|in:multiple_choice,true_false,essay',
            'description' => 'nullable|string',
            'question_text' => 'required|string',
            'help_text' => 'nullable|string',
            'explanation_text' => 'nullable|string',
            'score' => 'required|integer|min:0',
            'exp' => 'required|integer|min:0',
            'question_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'explanation_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'explanation_images' => 'nullable|array',
            'explanation_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'old_explanation_images' => 'nullable|array',
            'old_explanation_images.*' => 'nullable|integer',
            'delete_explanation_images' => 'nullable|array',
            'delete_explanation_images.*' => 'integer|exists:question_explanation_images,id',
            'explanation_blocks' => 'nullable|array',
            'explanation_blocks.*.type' => 'required_with:explanation_blocks|in:text,image',
            'explanation_blocks.*.content' => 'nullable|string',
            'explanation_block_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'old_explanation_block_images' => 'nullable|array',
            'old_explanation_block_images.*' => 'nullable|string',
            'blocks' => 'nullable|array',
            'blocks.*.type' => 'required_with:blocks|in:text,image',
            'blocks.*.content' => 'nullable|string',
            'block_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'old_block_images' => 'nullable|array',
            'old_block_images.*' => 'nullable|string',
            'answers' => 'required_if:type,multiple_choice,essay|array',
            'answers.*' => 'nullable|string',
            'is_correct' => 'nullable|array',
            'answer_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'old_answer_images' => 'nullable|array',
            'old_answer_images.*' => 'nullable|string',
            'correct_answer' => 'required_if:type,true_false|in:true,false',
        ], [
            'challenge_id.required' => 'Misi wajib dipilih.',
            'challenge_id.exists' => 'Misi yang dipilih tidak ditemukan.',
            'type.required' => 'Tipe soal wajib dipilih.',
            'question_text.required' => 'Teks pertanyaan wajib diisi.',
            'score.required' => 'Poin wajib diisi.',
            'score.integer' => 'Poin harus berupa angka.',
            'exp.required' => 'EXP wajib diisi.',
            'exp.integer' => 'EXP harus berupa angka.',
            'question_image.image' => 'Gambar pertanyaan harus berupa file gambar.',
            'question_image.max' => 'Ukuran gambar pertanyaan maksimal 2 MB.',
            'explanation_image.image' => 'Gambar pembahasan harus berupa file gambar.',
            'explanation_image.max' => 'Ukuran gambar pembahasan maksimal 2 MB.',
            'explanation_images.*.image' => 'Gambar pembahasan harus berupa file gambar.',
            'explanation_images.*.max' => 'Ukuran gambar pembahasan maksimal 2 MB.',
            'explanation_block_images.*.image' => 'Gambar pembahasan harus berupa file gambar.',
            'explanation_block_images.*.max' => 'Ukuran gambar pembahasan maksimal 2 MB.',
            'block_images.*.image' => 'Gambar pada susunan soal harus berupa file gambar.',
            'block_images.*.max' => 'Ukuran gambar pada susunan soal maksimal 2 MB.',
            'answers.required_if' => 'Kunci jawaban wajib diisi.',
            'answers.*.required' => 'Kunci jawaban wajib diisi.',
            'answer_images.*.image' => 'Gambar jawaban harus berupa file gambar.',
            'answer_images.*.max' => 'Ukuran gambar jawaban maksimal 2 MB.',
            'correct_answer.required_if' => 'Pilih jawaban benar untuk soal benar/salah.',
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($request->input('type') === 'multiple_choice') {
                $answerIndexes = collect(array_keys($request->input('answers', [])))
                    ->filter(fn($index) => $this->answerHasContent($request, $index))
                    ->map(fn($index) => (string) $index)
                    ->values();

                $correctIndexes = collect($request->input('is_correct', []))
                    ->filter(fn($value) => (string) $value === '1')
                    ->keys()
                    ->map(fn($index) => (string) $index)
                    ->values();

                if ($answerIndexes->count() < 2) {
                    $validator->errors()->add('answers', 'Pilihan ganda minimal punya 2 pilihan jawaban.');
                }

                if ($correctIndexes->intersect($answerIndexes)->isEmpty()) {
                    $validator->errors()->add('is_correct', 'Tandai minimal 1 jawaban benar yang sudah terisi.');
                }
            }

            if ($request->input('type') === 'essay' && blank($request->input('answers.0'))) {
                $validator->errors()->add('answers.0', 'Kunci jawaban esai wajib diisi.');
            }
        });

        return $validator->validate();
    }

    protected function answerHasContent(Request $request, string|int $index): bool
    {
        return filled($request->input("answers.$index"))
            || $request->hasFile("answer_images.$index")
            || filled($request->input("old_answer_images.$index"));
    }

    protected function syncQuestionBlocks(Request $request, Question $question): void
    {
        $blocks = $request->input('blocks', []);
        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $index => $block) {
            $type = $block['type'] ?? null;
            if (! in_array($type, ['text', 'image'], true)) {
                continue;
            }

            $content = trim((string) ($block['content'] ?? ''));
            $imagePath = null;

            if ($request->hasFile("block_images.$index")) {
                $imagePath = $request->file("block_images.$index")->store('question-blocks', 'public');
                $this->syncPublicStorageFile($imagePath);
            } elseif (! empty($request->old_block_images[$index])) {
                $imagePath = $request->old_block_images[$index];
                $this->syncPublicStorageFile($imagePath);
            }

            if ($type === 'text' && $content === '') {
                continue;
            }

            if ($type === 'image' && $imagePath === null) {
                continue;
            }

            QuestionBlock::create([
                'question_id' => $question->id,
                'type' => $type,
                'content' => $type === 'text' ? $content : null,
                'image_path' => $type === 'image' ? $imagePath : null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function syncExplanationBlocks(Request $request, Question $question): void
    {
        $blocks = $request->input('explanation_blocks', []);
        if (! is_array($blocks)) {
            return;
        }

        $retainedImages = collect($request->input('old_explanation_block_images', []))
            ->filter()
            ->values()
            ->all();

        $this->deleteExplanationBlocks($question, $retainedImages);

        foreach ($blocks as $index => $block) {
            $type = $block['type'] ?? null;
            if (! in_array($type, ['text', 'image'], true)) {
                continue;
            }

            $content = trim((string) ($block['content'] ?? ''));
            $imagePath = null;

            if ($request->hasFile("explanation_block_images.$index")) {
                $imagePath = $request->file("explanation_block_images.$index")->store('question-explanations', 'public');
                $this->syncPublicStorageFile($imagePath);
            } elseif (! empty($request->old_explanation_block_images[$index])) {
                $imagePath = $request->old_explanation_block_images[$index];
                $this->syncPublicStorageFile($imagePath);
            }

            if ($type === 'text' && $content === '') {
                continue;
            }

            if ($type === 'image' && $imagePath === null) {
                continue;
            }

            QuestionExplanationBlock::create([
                'question_id' => $question->id,
                'type' => $type,
                'content' => $type === 'text' ? $content : null,
                'image_path' => $type === 'image' ? $imagePath : null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function compiledExplanationText(Request $request): string
    {
        $blocks = $request->input('explanation_blocks', []);
        if (! is_array($blocks)) {
            return '';
        }

        return collect($blocks)
            ->filter(fn($block) => ($block['type'] ?? null) === 'text' && filled($block['content'] ?? null))
            ->pluck('content')
            ->map(fn($content) => trim((string) $content))
            ->implode("\n\n");
    }

    protected function syncAnswers(Request $request, Question $question): void
    {
        if ($request->type === 'true_false') {
            Answer::create([
                'question_id' => $question->id,
                'answer' => 'True',
                'is_correct' => $request->correct_answer === 'true',
            ]);

            Answer::create([
                'question_id' => $question->id,
                'answer' => 'False',
                'is_correct' => $request->correct_answer === 'false',
            ]);

            return;
        }

        if ($request->type === 'essay') {
            Answer::create([
                'question_id' => $question->id,
                'answer' => $request->answers[0] ?? '',
                'is_correct' => true,
            ]);

            return;
        }

        foreach ($request->answers as $index => $answerText) {
            if (blank($answerText) && ! $request->hasFile("answer_images.$index")) {
                continue;
            }

            $answerData = [
                'question_id' => $question->id,
                'answer' => $answerText,
                'is_correct' => isset($request->is_correct[$index]) && (string) $request->is_correct[$index] === '1',
            ];

            if ($request->hasFile("answer_images.$index")) {
                $answerData['answer_image'] = $request->file("answer_images.$index")->store('answers', 'public');
                $this->syncPublicStorageFile($answerData['answer_image']);
            } elseif (! empty($request->old_answer_images[$index])) {
                $answerData['answer_image'] = $request->old_answer_images[$index];
                $this->syncPublicStorageFile($answerData['answer_image']);
            }

            Answer::create($answerData);
        }
    }

    protected function syncExplanationImages(Request $request, Question $question): void
    {
        $deleteIds = collect($request->input('delete_explanation_images', []))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        if (! empty($deleteIds)) {
            $question->explanationImages()
                ->whereIn('id', $deleteIds)
                ->get()
                ->each(function (QuestionExplanationImage $image): void {
                    Storage::disk('public')->delete($image->image_path);
                    $this->deletePublicStorageFile($image->image_path);
                    $image->delete();
                });
        }

        foreach ($request->input('old_explanation_images', []) as $id => $order) {
            $image = $question->explanationImages()->whereKey((int) $id)->first();
            if ($image && ! in_array($image->id, $deleteIds, true)) {
                $image->update(['sort_order' => max(1, (int) $order)]);
            }
        }

        $nextOrder = (int) ($question->explanationImages()->max('sort_order') ?? 0) + 1;

        foreach ($request->file('explanation_images', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('question-explanations', 'public');
            $this->syncPublicStorageFile($path);

            $question->explanationImages()->create([
                'image_path' => $path,
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    protected function buildQualitySummary($challenges): array
    {
        $questions = Question::with(['answers', 'blocks', 'explanationImages', 'explanationBlocks'])->get();
        $readyQuestions = 0;
        $needsReview = 0;

        $questions->each(function (Question $question) use (&$readyQuestions, &$needsReview): void {
            if (empty($this->questionQualityIssues($question))) {
                $readyQuestions++;

                return;
            }

            $needsReview++;
        });

        return [
            'total_questions' => $questions->count(),
            'ready_questions' => $readyQuestions,
            'needs_review' => $needsReview,
            'empty_challenges' => $challenges->where('questions_count', 0)->count(),
        ];
    }

    protected function questionQualityIssues(Question $question): array
    {
        $issues = [];
        $answers = $question->answers;
        $contextText = trim((string) ($question->description ?: $question->blocks->where('type', 'text')->pluck('content')->implode("\n")));

        if (blank($question->question_text)) {
            $issues[] = 'Teks pertanyaan belum diisi.';
        }

        if ((int) $question->score <= 0) {
            $issues[] = 'Poin masih 0.';
        }

        if ((int) $question->exp <= 0) {
            $issues[] = 'EXP masih 0.';
        }

        if (blank($question->help_text)) {
            $issues[] = 'Petunjuk saat salah belum diisi.';
        }

        if (blank($question->explanation_text) && blank($question->explanation_image) && $question->explanationImages->isEmpty() && $question->explanationBlocks->isEmpty()) {
            $issues[] = 'Pembahasan jawaban belum diisi.';
        }

        if (strlen(strip_tags($contextText)) > 1200) {
            $issues[] = 'Materi soal terlalu panjang, sebaiknya dipisah menjadi beberapa bagian.';
        }

        if (strlen(strip_tags((string) $question->question_text)) > 420) {
            $issues[] = 'Pertanyaan cukup panjang, pastikan mudah dibaca mahasiswa.';
        }

        if ($question->type === 'multiple_choice') {
            $filledAnswers = $answers->filter(fn(Answer $answer) => filled($answer->answer) || filled($answer->answer_image));

            if ($filledAnswers->count() < 2) {
                $issues[] = 'Pilihan ganda minimal punya 2 jawaban.';
            }

            if ($filledAnswers->where('is_correct', true)->isEmpty()) {
                $issues[] = 'Belum ada jawaban benar.';
            }
        }

        if ($question->type === 'true_false') {
            $answerLabels = $answers->pluck('answer')->map(fn($answer) => strtolower(trim((string) $answer)))->all();

            if (! in_array('true', $answerLabels, true) || ! in_array('false', $answerLabels, true)) {
                $issues[] = 'Jawaban benar/salah belum lengkap.';
            }

            if ($answers->where('is_correct', true)->count() !== 1) {
                $issues[] = 'Benar/salah harus punya tepat 1 kunci.';
            }
        }

        if ($question->type === 'essay') {
            $essayAnswer = $answers->firstWhere('is_correct', true);

            if (! $essayAnswer || blank($essayAnswer->answer)) {
                $issues[] = 'Kunci jawaban esai belum diisi.';
            }
        }

        foreach ($this->questionImagePaths($question) as $label => $path) {
            if (! $this->storedAssetExists($path)) {
                $issues[] = "{$label} tidak ditemukan.";
            }
        }

        return $issues;
    }

    protected function questionImagePaths(Question $question): array
    {
        $paths = [];

        if ($question->question_image) {
            $paths['Gambar pertanyaan'] = $question->question_image;
        }

        if ($question->explanationBlocks->isEmpty() && $question->explanation_image) {
            $paths['Gambar pembahasan'] = $question->explanation_image;
        }

        if ($question->explanationBlocks->isEmpty()) {
            foreach ($question->explanationImages as $index => $image) {
                if ($image->image_path) {
                    $paths['Gambar pembahasan ' . ($index + 1)] = $image->image_path;
                }
            }
        }

        foreach ($question->explanationBlocks as $index => $block) {
            if ($block->image_path) {
                $paths['Gambar blok pembahasan ' . ($index + 1)] = $block->image_path;
            }
        }

        foreach ($question->blocks as $index => $block) {
            if ($block->image_path) {
                $paths['Gambar susunan soal ' . ($index + 1)] = $block->image_path;
            }
        }

        foreach ($question->answers as $index => $answer) {
            if ($answer->answer_image) {
                $paths['Gambar jawaban ' . ($index + 1)] = $answer->answer_image;
            }
        }

        return $paths;
    }

    protected function storedAssetExists(?string $path): bool
    {
        if (blank($path)) {
            return true;
        }

        return file_exists(storage_path('app/public/' . $path))
            || file_exists(public_path('storage/' . $path));
    }

    protected function ensureQuestionAssetsPubliclyAvailable(Question $question): void
    {
        if ($question->question_image) {
            $this->syncPublicStorageFile($question->question_image);
        }

        if ($question->explanation_image) {
            $this->syncPublicStorageFile($question->explanation_image);
        }

        $question->explanationImages->each(function (QuestionExplanationImage $image): void {
            $this->syncPublicStorageFile($image->image_path);
        });

        $question->explanationBlocks->each(function (QuestionExplanationBlock $block): void {
            if ($block->image_path) {
                $this->syncPublicStorageFile($block->image_path);
            }
        });

        $question->blocks->each(function (QuestionBlock $block): void {
            if ($block->image_path) {
                $this->syncPublicStorageFile($block->image_path);
            }
        });

        $question->answers->each(function (Answer $answer): void {
            if ($answer->answer_image) {
                $this->syncPublicStorageFile($answer->answer_image);
            }
        });
    }

    protected function deleteQuestionBlocks(Question $question, array $retainedImages = []): void
    {
        $question->blocks->each(function (QuestionBlock $block) use ($retainedImages): void {
            if ($block->image_path && ! in_array($block->image_path, $retainedImages, true)) {
                Storage::disk('public')->delete($block->image_path);
                $this->deletePublicStorageFile($block->image_path);
            }
        });

        $question->blocks()->delete();
    }

    protected function deleteAnswerImages(Question $question, array $retainedImages = []): void
    {
        $question->answers->each(function (Answer $answer) use ($retainedImages): void {
            if ($answer->answer_image && ! in_array($answer->answer_image, $retainedImages, true)) {
                Storage::disk('public')->delete($answer->answer_image);
                $this->deletePublicStorageFile($answer->answer_image);
            }
        });
    }

    protected function deleteExplanationImages(Question $question): void
    {
        $question->explanationImages->each(function (QuestionExplanationImage $image): void {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
                $this->deletePublicStorageFile($image->image_path);
            }
        });

        $question->explanationImages()->delete();
    }

    protected function deleteExplanationBlocks(Question $question, array $retainedImages = []): void
    {
        $question->explanationBlocks->each(function (QuestionExplanationBlock $block) use ($retainedImages): void {
            if ($block->image_path && ! in_array($block->image_path, $retainedImages, true)) {
                Storage::disk('public')->delete($block->image_path);
                $this->deletePublicStorageFile($block->image_path);
            }
        });

        $question->explanationBlocks()->delete();
    }

    protected function syncPublicStorageFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $storagePath = storage_path('app/public/' . $path);
        $publicPath = public_path('storage/' . $path);

        if (! file_exists($storagePath) || file_exists($publicPath)) {
            return;
        }

        $directory = dirname($publicPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        copy($storagePath, $publicPath);
    }

    protected function deletePublicStorageFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $publicPath = public_path('storage/' . $path);
        if (file_exists($publicPath)) {
            @unlink($publicPath);
        }
    }
}
