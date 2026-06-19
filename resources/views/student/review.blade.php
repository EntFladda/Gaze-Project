<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Review Jawaban</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-[#071426] via-[#0A2342] to-[#123A68] min-h-screen text-white py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-10">
            <p class="text-sm uppercase tracking-[0.3em] text-amber-300 font-semibold">Tinjau Jawaban</p>
            <h1 class="text-4xl font-bold mt-2">Pelajari pembahasan dan jawaban</h1>
            <p class="text-sky-100/80 mt-3">Setelah menyelesaikan semua soal, kamu bisa melihat jawabanmu, kunci, dan pembahasan setiap soal di sini.</p>
        </div>

        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-[#F4F8FC] text-slate-900 rounded-2xl p-4 shadow border border-sky-200/40">
                <p class="text-sm text-slate-500">Poin</p>
                <p class="text-2xl font-bold text-emerald-600">{{ $result->total_score }}</p>
            </div>
            <div class="bg-[#F4F8FC] text-slate-900 rounded-2xl p-4 shadow border border-sky-200/40">
                <p class="text-sm text-slate-500">EXP</p>
                <p class="text-2xl font-bold text-sky-600">{{ $result->total_exp }}</p>
            </div>
            <div class="bg-[#F4F8FC] text-slate-900 rounded-2xl p-4 shadow border border-sky-200/40">
                <p class="text-sm text-slate-500">Benar</p>
                <p class="text-2xl font-bold text-emerald-600">{{ $result->correct_answers }}</p>
            </div>
            <div class="bg-[#F4F8FC] text-slate-900 rounded-2xl p-4 shadow border border-sky-200/40">
                <p class="text-sm text-slate-500">Salah</p>
                <p class="text-2xl font-bold text-red-600">{{ $result->wrong_answers }}</p>
            </div>
        </div>

        <div class="space-y-6">
            @foreach ($answers as $questionId => $answerGroup)
                @php
                    $question = $answerGroup->first()->question;
                    $correctAnswers = $question->answers->where('is_correct', true);
                    $submittedAnswers = $answerGroup
                        ->map(fn($answer) => $answer->selectedAnswer?->answer ?? $answer->answer_text ?? $answer->selected_answer)
                        ->filter()
                        ->values();
                    $isCorrect = $answerGroup->first()->is_correct;
                @endphp

                <section id="question-{{ $question->id }}" class="bg-[#F4F8FC] text-slate-900 rounded-3xl shadow border border-sky-200/40 p-6 scroll-mt-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.25em] text-slate-400">Soal {{ $loop->iteration }}</p>
                            <h2 class="text-2xl font-bold mt-2">{{ $question->question_text }}</h2>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $isCorrect ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $isCorrect ? 'Jawabanmu benar' : 'Jawabanmu perlu diperbaiki' }}
                        </span>
                    </div>

                    @if ($question->blocks->isNotEmpty())
                        <div class="mt-4 space-y-4">
                            @foreach ($question->blocks as $block)
                                @if ($block->type === 'text' && filled($block->content))
                                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                        <x-question-rich-text :text="$block->content" />
                                    </div>
                                @elseif ($block->type === 'image' && $block->image_path)
                                    <div>
                                        <img src="{{ asset('storage/' . $block->image_path) }}" alt="Question Block Image"
                                            class="max-h-72 rounded-2xl border border-slate-200">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif ($question->description)
                        <div class="mt-4 rounded-2xl bg-slate-50 border border-slate-200 p-4 text-slate-700 leading-7">
                            <x-question-rich-text :text="$question->description" />
                        </div>
                    @endif

                    @if ($question->question_image)
                        <div class="mt-4">
                            <img src="{{ asset('storage/' . $question->question_image) }}" alt="Question Image"
                                class="max-h-72 rounded-2xl border border-slate-200">
                        </div>
                    @endif

                    <div class="grid md:grid-cols-2 gap-4 mt-6">
                        <div class="rounded-2xl bg-blue-50 border border-blue-200 p-4">
                            <p class="text-sm uppercase tracking-[0.2em] text-sky-500 font-semibold mb-2">Jawaban Kamu</p>
                            @if ($submittedAnswers->isEmpty())
                                <p class="text-slate-600">Tidak ada jawaban tersimpan.</p>
                            @else
                                <ul class="space-y-2 text-slate-700">
                                    @foreach ($submittedAnswers as $submittedAnswer)
                                        <li>{{ $submittedAnswer }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4">
                            <p class="text-sm uppercase tracking-[0.2em] text-emerald-600 font-semibold mb-2">Jawaban Benar</p>
                            <ul class="space-y-2 text-slate-700">
                                @foreach ($correctAnswers as $correctAnswer)
                                    <li>{{ $correctAnswer->answer }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    @if ($question->help_text)
                        <div class="mt-4 rounded-2xl bg-amber-50 border border-amber-200 p-4">
                            <p class="text-sm uppercase tracking-[0.2em] text-amber-600 font-semibold mb-2">Bantuan yang Bisa Dipakai</p>
                            <x-question-rich-text :text="$question->help_text" tone="warning" />
                        </div>
                    @endif

                    <div class="mt-4 rounded-2xl bg-sky-50 border border-sky-200 p-4">
                        <p class="text-sm uppercase tracking-[0.2em] text-sky-600 font-semibold mb-2">Pembahasan</p>
                        @php
                            $explanationBlocks = $question->explanationBlocks;
                            if ($explanationBlocks->isEmpty()) {
                                $fallbackBlocks = collect();
                                if (filled($question->explanation_text)) {
                                    $fallbackBlocks->push((object) ['type' => 'text', 'content' => $question->explanation_text, 'image_path' => null]);
                                }
                                foreach ($question->explanationImages as $image) {
                                    $fallbackBlocks->push((object) ['type' => 'image', 'content' => null, 'image_path' => $image->image_path]);
                                }
                                if ($fallbackBlocks->isEmpty() && $question->explanation_image) {
                                    $fallbackBlocks->push((object) ['type' => 'image', 'content' => null, 'image_path' => $question->explanation_image]);
                                }
                                $explanationBlocks = $fallbackBlocks;
                            }
                        @endphp
                        <div class="grid gap-4">
                            @forelse ($explanationBlocks as $block)
                                @if ($block->type === 'text')
                                    <x-question-rich-text :text="$block->content" tone="info" />
                                @elseif ($block->image_path)
                                    <img src="{{ asset('storage/' . $block->image_path) }}" alt="Gambar Pembahasan {{ $loop->iteration }}"
                                        class="mx-auto max-h-[32rem] max-w-full rounded-2xl border border-sky-200 shadow-md">
                                @endif
                            @empty
                                <x-question-rich-text text="Pembahasan untuk soal ini belum diisi." tone="info" />
                            @endforelse
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="text-center mt-10">
            <a href="{{ route('student.mission.index') }}"
                class="inline-flex items-center rounded-2xl bg-slate-900 text-white px-6 py-3 font-semibold hover:bg-slate-800 transition">
                Kembali ke Misi
            </a>
        </div>
    </div>
</body>

</html>
