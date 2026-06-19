@extends('lecturer.layouts.app')

@section('content')
    <div class="question-detail-page">
        <section class="question-detail-hero">
            <p class="question-detail-kicker">Detail Soal</p>
            <h1>{{ $question->challenge->title }}</h1>
            <p>Periksa isi soal, bantuan, pembahasan, dan kunci jawaban sebelum digunakan mahasiswa.</p>
        </section>

        <section class="question-detail-card bg-white">
            <div class="question-meta-grid">
                <div class="question-meta-item">
                    <span>Tipe Soal</span>
                    <strong>{{ match ($question->type) {
                        'multiple_choice' => 'Pilihan Ganda',
                        'true_false' => 'Benar / Salah',
                        default => 'Esai',
                    } }}</strong>
                </div>
                <div class="question-meta-item">
                    <span>Poin</span>
                    <strong>{{ $question->score }}</strong>
                </div>
                <div class="question-meta-item">
                    <span>EXP</span>
                    <strong>{{ $question->exp }}</strong>
                </div>
            </div>

            @if ($question->blocks->isEmpty() && $question->description)
                <div class="question-section-block">
                    <h2>Pengantar Soal</h2>
                    <div class="question-rich-box"><x-question-rich-text :text="$question->description" /></div>
                </div>
            @endif

            @if ($question->blocks->isNotEmpty())
                <div class="question-section-block">
                    <h2>Susunan Isi Soal</h2>
                    <div class="question-content-stack">
                        @foreach ($question->blocks as $block)
                            <div class="question-rich-box">
                                @if ($block->type === 'text')
                                    <x-question-rich-text :text="$block->content" />
                                @elseif ($block->image_path)
                                    <img src="{{ asset('storage/' . $block->image_path) }}" alt="Gambar susunan soal" class="question-detail-image">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="question-section-block">
                <h2>Pertanyaan</h2>
                <div class="question-rich-box">
                    <x-question-rich-text :text="$question->question_text" />
                    @if ($question->question_image)
                        <img src="{{ asset('storage/' . $question->question_image) }}" alt="Gambar soal" class="question-detail-image mt">
                    @endif
                </div>
            </div>

            <div class="question-grid-two">
                <div class="question-section-block compact">
                    <h2>Bantuan</h2>
                    <div class="question-rich-box warning">
                        <x-question-rich-text :text="$question->help_text ?: 'Belum ada bantuan untuk soal ini.'" tone="warning" />
                    </div>
                </div>

                <div class="question-section-block compact">
                    <h2>Pembahasan</h2>
                    <div class="question-rich-box info">
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
                        @forelse ($explanationBlocks as $block)
                            @if ($block->type === 'text')
                                <x-question-rich-text :text="$block->content" tone="info" />
                            @elseif ($block->image_path)
                                <img src="{{ asset('storage/' . $block->image_path) }}" alt="Gambar pembahasan {{ $loop->iteration }}" class="question-detail-image mt">
                            @endif
                        @empty
                            <x-question-rich-text text="Belum ada pembahasan untuk soal ini." tone="info" />
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="question-section-block">
                <h2>{{ $question->type === 'essay' ? 'Kunci Jawaban' : 'Pilihan Jawaban' }}</h2>
                @if ($question->type === 'essay')
                    <div class="question-rich-box answer">{{ $question->answers->first()->answer ?? 'Belum ada jawaban.' }}</div>
                @else
                    <div class="question-answer-list">
                        @foreach ($question->answers as $answer)
                            <div class="question-answer-item {{ $answer->is_correct ? 'is-correct' : '' }}">
                                <div>
                                    @if ($answer->answer)
                                        <strong>{{ $answer->answer }}</strong>
                                    @endif
                                    @if ($answer->is_correct)
                                        <span>Jawaban Benar</span>
                                    @endif
                                </div>

                                @if ($answer->answer_image)
                                    <img src="{{ asset('storage/' . $answer->answer_image) }}" alt="Gambar jawaban" class="question-answer-image">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="question-detail-actions">
                <a href="{{ route('lecturer.questions.index') }}" class="question-detail-btn neutral">Kembali</a>
                <a href="{{ route('lecturer.questions.edit', $question->id) }}" class="question-detail-btn primary">Edit Soal</a>
            </div>
        </section>
    </div>

    <style>
        .question-detail-page { max-width: 1050px; margin: 0 auto; }
        .question-detail-hero { margin-bottom:24px; padding:30px; border-radius:30px; background:rgba(10,35,66,.78); border:1px solid rgba(191,219,254,.16); box-shadow:0 20px 50px rgba(0,0,0,.22); }
        .question-detail-kicker { margin:0; color:rgba(191,219,254,.78); letter-spacing:.32em; text-transform:uppercase; font-size:12px; font-weight:900; }
        .question-detail-hero h1 { margin:10px 0 0; color:#fff; font-size:40px; font-weight:850; }
        .question-detail-hero p { margin:12px 0 0; color:rgba(219,234,254,.76); line-height:1.7; }
        .question-detail-card { padding:28px; border-radius:28px; border:1px solid #B7CCE6; box-shadow:0 18px 45px rgba(31,20,31,.14); }
        .question-meta-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:14px; margin-bottom:22px; }
        .question-meta-item { padding:18px; border-radius:20px; background:#E8F0F8; border:1px solid #B7CCE6; }
        .question-meta-item span { display:block; color:#1D5FD6; font-size:12px; letter-spacing:.22em; text-transform:uppercase; font-weight:900; }
        .question-meta-item strong { display:block; margin-top:8px; color:#0A2342; font-size:24px; }
        .question-section-block { margin-top:22px; }
        .question-section-block h2 { margin:0 0 12px; color:#0A2342; font-size:22px; font-weight:850; }
        .question-grid-two { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .question-rich-box { padding:18px; border-radius:20px; background:#f8fafc; border-left:5px solid #1D5FD6; color:#243044; line-height:1.75; }
        .question-rich-box.warning { background:#fff7ed; border-left-color:#F2A93B; }
        .question-rich-box.info { background:#E8F0F8; border-left-color:#2BA7D8; }
        .question-rich-box.answer { font-weight:800; }
        .question-content-stack { display:grid; gap:12px; }
        .question-detail-image { max-width:100%; border-radius:18px; border:1px solid #e5e7eb; box-shadow:0 14px 30px rgba(15,23,42,.12); }
        .question-detail-image.mt { margin-top:14px; }
        .question-answer-list { display:grid; gap:12px; }
        .question-answer-item { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px; border-radius:18px; background:#f8fafc; border:1px solid #e5e7eb; }
        .question-answer-item.is-correct { background:#ecfdf5; border-color:#86efac; }
        .question-answer-item strong { color:#0A2342; }
        .question-answer-item span { display:inline-flex; margin-top:8px; padding:6px 10px; border-radius:999px; background:#22c55e; color:#fff; font-size:12px; font-weight:900; }
        .question-answer-image { width:92px; height:74px; object-fit:contain; border-radius:14px; background:#fff; border:1px solid #e5e7eb; }
        .question-detail-actions { display:flex; justify-content:center; gap:12px; margin-top:28px; }
        .question-detail-btn { display:inline-flex; align-items:center; justify-content:center; min-width:130px; padding:13px 18px; border-radius:16px; text-decoration:none; font-weight:900; }
        .question-detail-btn.neutral { background:#f8fafc; color:#263E5C; border:1px solid #e2e8f0; }
        .question-detail-btn.primary { background:linear-gradient(135deg,#1D5FD6,#F2A93B); color:#fff; }
        @media (max-width: 800px) { .question-meta-grid,.question-grid-two { grid-template-columns:1fr; } .question-detail-hero h1 { font-size:32px; } .question-detail-card { padding:20px; } }
    </style>
@endsection
