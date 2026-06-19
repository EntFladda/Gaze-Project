@php
    $correctCount = $question->type === 'multiple_choice' ? $question->answers->where('is_correct', true)->count() : 0;
    $isMultipleAnswer = $question->type === 'multiple_choice' && $correctCount > 1;
    $questionNumber = $questionNumber ?? 1;
    $totalQuestions = $totalQuestions ?? 1;
    $nextUrl = $nextUrl ?? route('student.next.question', ['challenge_id' => $question->challenge_id, 'advance' => 1]);
    $exitUrl = $exitUrl ?? route('student.question.exit');
    $hasContext = $question->blocks->isNotEmpty() || filled($question->description);
    $isLastQuestion = $questionNumber >= $totalQuestions;
    $questionConcept = \App\Support\ComputationalThinking::infer(implode(' ', [
        $question->challenge?->section?->name,
        $question->challenge?->title,
        $question->question_text,
        $question->description,
        $question->help_text,
        $question->explanation_text,
    ]));
@endphp

<div id="question-session" data-question-id="{{ $question->id }}" data-challenge-id="{{ $question->challenge_id }}"
    data-is-last-question="{{ $isLastQuestion ? '1' : '0' }}"
    data-next-url="{{ $nextUrl }}"
    data-exit-url="{{ $exitUrl }}"
>
    <div class="question-progress-panel px-6 py-5 border-b border-slate-200">
        <div class="grid md:grid-cols-[1fr_auto] gap-4 items-center">
            <div>
                <div class="flex items-center justify-between text-sm text-slate-500 mb-2">
                    <span>Soal {{ $questionNumber }} dari {{ $totalQuestions }}</span>
                    <span>{{ round($progress) }}%</span>
                </div>
                <div class="h-3 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-3 bg-gradient-to-r from-emerald-400 to-sky-500 rounded-full"
                        style="width: {{ $progress }}%"></div>
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-2 text-sm">
                <span class="question-stat-pill question-concept-pill">Fokus: {{ $questionConcept['short'] }}</span>
                <span class="question-stat-pill">{{ $question->score }} skor</span>
                <span class="question-stat-pill">{{ $question->exp }} EXP</span>
            </div>
        </div>
    </div>

    <div class="question-workspace {{ $hasContext ? '' : 'question-workspace--focused' }} px-6 py-6">
        @if ($hasContext)
            <main class="question-reading-lane space-y-5">
                <section class="question-section-card question-context-card rounded-3xl border p-5">
                    <div class="question-section-head">
                        <p class="question-section-kicker">Konteks</p>
                        <h2 class="question-section-title">Baca informasi soal</h2>
                    </div>

                    <div class="space-y-4 mt-4">
                        @if ($question->blocks->isNotEmpty())
                            @foreach ($question->blocks as $block)
                                @if ($block->type === 'text' && filled($block->content))
                                    <div class="question-text-card">
                                        <x-question-rich-text :text="$block->content" />
                                    </div>
                                @elseif ($block->type === 'image' && $block->image_path)
                                    <figure class="question-figure">
                                        <img src="{{ asset('storage/' . $block->image_path) }}" alt="Gambar konteks soal"
                                            class="question-zoom-image question-content-image rounded-2xl shadow-md border border-slate-200 cursor-zoom-in"
                                            onclick="openQuestionImageModal(this.src, this.alt)">
                                        <button type="button" class="question-image-preview-btn"
                                            onclick="openQuestionImageModal('{{ asset('storage/' . $block->image_path) }}', 'Gambar konteks soal')">
                                            Lihat besar
                                        </button>
                                    </figure>
                                @endif
                            @endforeach
                        @else
                            <div class="question-text-card">
                                <x-question-rich-text :text="$question->description" />
                            </div>
                        @endif
                    </div>
                </section>
            </main>
        @endif

        <aside class="question-action-lane">
            <div class="question-sticky-panel space-y-5">
                <section class="question-section-card rounded-3xl border border-amber-200 bg-amber-50/80 p-5">
                    <div class="question-section-head">
                        <p class="question-section-kicker text-amber-700">Pertanyaan</p>
                        <div class="question-question-text text-slate-900">
                            <x-question-rich-text :text="$question->question_text" />
                        </div>
                    </div>

                    @if ($question->question_image)
                        <figure class="question-figure mt-4">
                            <img src="{{ asset('storage/' . $question->question_image) }}" alt="Gambar pertanyaan"
                                class="question-zoom-image max-h-[28rem] max-w-full rounded-2xl shadow-md border border-slate-200 cursor-zoom-in"
                                onclick="openQuestionImageModal(this.src, this.alt)">
                            <button type="button" class="question-image-preview-btn"
                                onclick="openQuestionImageModal('{{ asset('storage/' . $question->question_image) }}', 'Gambar pertanyaan')">
                                Lihat besar
                            </button>
                        </figure>
                    @endif
                </section>

                <section class="question-section-card rounded-3xl border border-sky-100 bg-sky-50/80 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="question-section-kicker text-sky-700">Jawaban</p>
                            <p class="text-sm text-slate-600 mt-2">
                                @if ($question->type === 'multiple_choice')
                                    {{ $isMultipleAnswer ? 'Pilih semua jawaban yang benar.' : 'Pilih satu jawaban paling tepat.' }}
                                @elseif ($question->type === 'true_false')
                                    Tentukan benar atau salah.
                                @else
                                    Tulis jawaban akhir secara singkat.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        @if ($question->type === 'multiple_choice')
                            <div class="grid gap-3">
                                @foreach ($question->answers as $answer)
                                    @php
                                        $choiceLabel = chr(64 + $loop->iteration);
                                        $answerText = preg_replace('/^[A-Z]\.\s*/', '', $answer->answer) ?? $answer->answer;
                                    @endphp
                                    <button type="button"
                                        class="answer-option question-answer-card"
                                        data-answer-id="{{ $answer->id }}"
                                        @if (! $isMultipleAnswer) onclick="selectSingleAnswer(this, {{ $answer->id }})" @else onclick="toggleMultiAnswer(this, {{ $answer->id }})" @endif>
                                        <span class="question-answer-letter">{{ $choiceLabel }}</span>
                                        @if ($answer->answer_image)
                                            <img src="{{ asset('storage/' . $answer->answer_image) }}" alt="Gambar jawaban"
                                                class="question-zoom-image max-h-28 max-w-full rounded-xl border border-slate-200 cursor-zoom-in"
                                                onclick="event.stopPropagation(); openQuestionImageModal(this.src, this.alt)">
                                        @endif
                                        <span>{{ $answerText }}</span>
                                    </button>
                                @endforeach
                            </div>

                            @if ($isMultipleAnswer)
                                <button id="submit-multi-btn" type="button" onclick="submitMultiAnswer()"
                                    class="hidden mt-4 question-primary-btn">
                                    Kunci Jawaban
                                </button>
                            @else
                                <button id="submit-single-btn" type="button" onclick="submitSelectedSingleAnswer()"
                                    class="hidden mt-4 question-primary-btn">
                                    Kunci Jawaban
                                </button>
                            @endif
                        @endif

                        @if ($question->type === 'true_false')
                            <div class="grid sm:grid-cols-2 gap-3">
                                <button type="button" data-answer-id="true" onclick="selectSingleAnswer(this, 'true')" class="answer-option question-answer-card">
                                    Benar
                                </button>
                                <button type="button" data-answer-id="false" onclick="selectSingleAnswer(this, 'false')" class="answer-option question-answer-card">
                                    Salah
                                </button>
                            </div>
                            <button id="submit-single-btn" type="button" onclick="submitSelectedSingleAnswer()"
                                class="hidden mt-4 question-primary-btn">
                                Kunci Jawaban
                            </button>
                        @endif

                        @if ($question->type === 'essay')
                            <div class="space-y-4">
                                <textarea id="essay-answer" rows="5"
                                    class="w-full rounded-2xl border border-slate-300 p-4 focus:border-sky-500 focus:ring-sky-500"
                                    placeholder="Tulis jawabanmu di sini"></textarea>
                                <button type="button" onclick="submitEssayAnswer()" class="question-primary-btn">
                                    Kunci Jawaban
                                </button>
                            </div>
                        @endif
                    </div>
                </section>

                <div id="result-box" class="hidden question-feedback-card"></div>

                <div id="help-actions" class="hidden">
                    <button type="button" onclick="requestHelp()" class="question-help-btn">
                        Buka petunjuk dan coba lagi
                    </button>
                </div>

                <div id="help-box" class="hidden rounded-3xl bg-amber-50 border border-amber-200 p-5">
                    <p class="question-section-kicker text-amber-700 mb-2">Petunjuk</p>
                    <div id="help-text" class="leading-7 text-slate-700"></div>
                </div>

                <button id="next-btn" type="button" onclick="nextQuestion()" class="hidden question-next-btn">
                    {{ $isLastQuestion ? 'Lihat hasil' : 'Lanjut soal berikutnya' }}
                </button>
            </div>
        </aside>
    </div>
</div>
