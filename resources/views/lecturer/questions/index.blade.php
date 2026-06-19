@extends('lecturer.layouts.app')

@section('content')
    <div class="questions-page">
        <section class="questions-hero">
            <div>
                <p class="questions-kicker">Bank Soal</p>
                <h1 class="questions-title">Kelola soal mission</h1>
                <p class="questions-copy">Susun pertanyaan, jawaban, bantuan, dan pembahasan yang akan dipakai mahasiswa saat mengerjakan mission.</p>
            </div>

            <div class="questions-hero-actions">
                <form method="GET" action="{{ route('lecturer.questions.index') }}" class="questions-filter-form">
                    <label for="challenge_id" class="questions-filter-label">Pilih Mission</label>
                    <select name="challenge_id" id="challenge_id" onchange="this.form.submit()" class="questions-filter-select">
                        <option value="">Semua Mission</option>
                        @foreach ($challenges as $challenge)
                            <option value="{{ $challenge->id }}" {{ (string) $selectedChallenge === (string) $challenge->id ? 'selected' : '' }}>
                                {{ $challenge->section?->order ?? '-' }}. {{ $challenge->title }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('lecturer.questions.create', ['challenge_id' => request('challenge_id')]) }}" class="questions-primary-btn">
                    + Tambah Soal
                </a>
            </div>
        </section>

        @if (session('success'))
            <div class="questions-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="questions-alert error">{{ session('error') }}</div>
        @endif

        <section class="questions-summary-grid">
            <div class="questions-summary-card ready">
                <span>Soal lengkap</span>
                <strong>{{ $qualitySummary['ready_questions'] }}</strong>
                <small>dari {{ $qualitySummary['total_questions'] }} soal</small>
            </div>
            <div class="questions-summary-card warning">
                <span>Perlu dilengkapi</span>
                <strong>{{ $qualitySummary['needs_review'] }}</strong>
                <small>soal masih punya catatan</small>
            </div>
            <div class="questions-summary-card danger">
                <span>Mission kosong</span>
                <strong>{{ $qualitySummary['empty_challenges'] }}</strong>
                <small>belum punya soal</small>
            </div>
        </section>

        <section class="questions-card bg-white">
            <div class="questions-card-head">
                <div>
                    <p class="questions-card-kicker">Daftar Soal</p>
                    <h2 class="questions-card-title">Materi pertanyaan aktif</h2>
                </div>

                <input type="text" id="searchInput" placeholder="Cari soal atau mission..."
                    class="questions-search-input" onkeyup="filterQuestions()">
            </div>

            <div class="questions-list">
                @forelse ($questions as $question)
                    @php
                        $typeMap = [
                            'multiple_choice' => 'Pilihan Ganda',
                            'true_false' => 'Benar / Salah',
                            'essay' => 'Esai',
                        ];
                        $descriptionPreview = $question->description ?: $question->blocks->firstWhere('type', 'text')?->content;
                        $qualityIssues = $question->quality_issues ?? [];
                        $imageBlocks = $question->blocks
                            ->where('type', 'image')
                            ->filter(fn ($block) => filled($block->image_path))
                            ->values();
                        $previewImage = $imageBlocks->first()?->image_path ?: $question->question_image;
                        $imageCount = $imageBlocks->count() ?: ($question->question_image ? 1 : 0);
                    @endphp

                    <article class="question-item" data-search-text="{{ strtolower(($question->challenge->title ?? '') . ' ' . ($descriptionPreview ?? '') . ' ' . ($question->question_text ?? '')) }}">
                        <div class="question-main">
                            <div class="question-topline">
                                <span class="questions-pill mission">{{ $question->challenge->section?->name ?? 'Tanpa bagian' }}</span>
                                <span class="questions-pill type">{{ $typeMap[$question->type] ?? 'Soal' }}</span>
                                @if (empty($qualityIssues))
                                    <span class="questions-pill ready">Lengkap</span>
                                @else
                                    <span class="questions-pill warning">Perlu dilengkapi</span>
                                @endif
                            </div>

                            <h3 class="question-title">{{ $question->challenge->title }}</h3>
                            <p class="question-desc">{{ Str::limit($descriptionPreview ?: 'Belum ada pengantar soal.', 120) }}</p>
                            <p class="question-text">{{ Str::limit($question->question_text ?: '-', 140) }}</p>

                            @if (! empty($qualityIssues))
                                <ul class="questions-note-list">
                                    @foreach (array_slice($qualityIssues, 0, 3) as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="question-side">
                            @if ($previewImage)
                                <div class="questions-preview-wrap">
                                    <img src="{{ asset('storage/' . $previewImage) }}" alt="Gambar soal" class="questions-thumb">
                                    @if ($imageCount > 1)
                                        <span>{{ $imageCount }} gambar</span>
                                    @endif
                                </div>
                            @else
                                <div class="questions-no-image">Tanpa gambar</div>
                            @endif

                            <div class="questions-score-wrap">
                                <span>{{ $question->score }} Poin</span>
                                <span>{{ $question->exp }} EXP</span>
                            </div>
                        </div>

                        <div class="questions-actions">
                            <a href="{{ route('lecturer.questions.show', $question->id) }}" class="questions-btn info">Detail</a>
                            <a href="{{ route('lecturer.questions.edit', $question->id) }}" class="questions-btn warn">Edit</a>
                            <form action="{{ route('lecturer.questions.destroy', $question->id) }}" method="POST"
                                onsubmit="return confirm('Hapus soal ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="questions-btn danger">Hapus</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="questions-empty">Belum ada soal. Tambahkan soal pertama untuk mission yang dipilih.</div>
                @endforelse

                <div id="noResultsRow" class="questions-empty" style="display: none;">Tidak ada soal yang cocok dengan pencarian.</div>
            </div>

            <div class="questions-pagination">
                <p class="questions-page-info">Halaman {{ $questions->currentPage() }} dari {{ $questions->lastPage() }}</p>
                {{ $questions->links() }}
            </div>
        </section>
    </div>

    <style>
        .questions-page { max-width: 1180px; margin: 0 auto; }
        .questions-hero { display:grid; grid-template-columns:1fr auto; gap:24px; align-items:end; margin-bottom:22px; padding:30px; border-radius:30px; background:rgba(10,35,66,.78); border:1px solid rgba(191,219,254,.16); box-shadow:0 20px 50px rgba(0,0,0,.22); }
        .questions-kicker,.questions-card-kicker { margin:0; font-size:12px; letter-spacing:.32em; text-transform:uppercase; font-weight:800; color:#1D5FD6; }
        .questions-hero .questions-kicker { color:rgba(191,219,254,.78); }
        .questions-title { margin:10px 0 0; font-size:42px; color:#fff; font-weight:800; }
        .questions-copy { margin:12px 0 0; color:rgba(219,234,254,.76); max-width:680px; line-height:1.7; }
        .questions-hero-actions { display:flex; gap:12px; align-items:end; flex-wrap:wrap; justify-content:flex-end; }
        .questions-filter-form { display:grid; gap:8px; }
        .questions-filter-label { color:rgba(219,234,254,.8); font-weight:800; font-size:13px; }
        .questions-filter-select,.questions-search-input { min-width:260px; border:1px solid #9CB8D8; border-radius:18px; padding:14px 16px; color:#09254A; background:#fff; outline:none; font-weight:700; }
        .questions-primary-btn,.questions-btn { display:inline-flex; align-items:center; justify-content:center; border:0; text-decoration:none; cursor:pointer; font-weight:800; border-radius:16px; transition:.18s ease; }
        .questions-primary-btn { min-height:52px; padding:0 22px; color:#fff; background:linear-gradient(135deg,#1D5FD6,#F2A93B); box-shadow:0 14px 26px rgba(37,99,235,.24); }
        .questions-primary-btn:hover,.questions-btn:hover { transform:translateY(-1px); }
        .questions-alert { margin-bottom:16px; padding:16px 18px; border-radius:18px; font-weight:800; }
        .questions-alert.success { background:#dcfce7; color:#166534; }
        .questions-alert.error { background:#fee2e2; color:#991b1b; }
        .questions-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:22px; }
        .questions-summary-card { padding:22px; border-radius:24px; background:#fff; border:1px solid #B7CCE6; box-shadow:0 18px 40px rgba(31,20,31,.12); }
        .questions-summary-card span { display:block; color:#667085; font-size:12px; letter-spacing:.24em; text-transform:uppercase; font-weight:900; }
        .questions-summary-card strong { display:block; margin-top:8px; font-size:38px; color:#0A2342; }
        .questions-summary-card small { color:#667085; font-weight:700; }
        .questions-summary-card.ready { border-color:#bbf7d0; } .questions-summary-card.warning { border-color:#fed7aa; } .questions-summary-card.danger { border-color:#fecaca; }
        .questions-card { padding:26px; border-radius:28px; box-shadow:0 18px 45px rgba(31,20,31,.14); border:1px solid #B7CCE6; }
        .questions-card-head { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:20px; }
        .questions-card-title { margin:6px 0 0; color:#0A2342; font-size:28px; font-weight:850; }
        .questions-list { display:grid; gap:14px; }
        .question-item { display:grid; grid-template-columns:minmax(0,1fr) 150px auto; gap:18px; align-items:center; padding:20px; border:1px solid #B7CCE6; border-radius:24px; background:linear-gradient(180deg,#fff,#E8F0F8); }
        .question-topline { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
        .questions-pill { display:inline-flex; align-items:center; padding:7px 11px; border-radius:999px; font-size:12px; font-weight:900; }
        .questions-pill.mission { background:#D9EEF7; color:#0369a1; } .questions-pill.type { background:#D9EEF7; color:#0369a1; } .questions-pill.ready { background:#dcfce7; color:#166534; } .questions-pill.warning { background:#ffedd5; color:#9a3412; }
        .question-title { margin:0; color:#0A2342; font-size:20px; font-weight:850; }
        .question-desc,.question-text { margin:8px 0 0; color:#53657A; line-height:1.55; }
        .question-text { color:#111827; font-weight:700; }
        .questions-note-list { margin:12px 0 0; padding-left:18px; color:#9a3412; font-size:13px; line-height:1.55; }
        .question-side { display:grid; gap:10px; justify-items:start; }
        .questions-thumb { width:112px; height:78px; object-fit:contain; border-radius:16px; background:#fff; border:1px solid #B7CCE6; }
        .questions-preview-wrap { display:grid; gap:6px; color:#1D5FD6; font-weight:800; font-size:12px; }
        .questions-no-image { padding:12px 14px; border-radius:16px; color:#6A7C93; background:#f8fafc; font-weight:800; }
        .questions-score-wrap { display:flex; flex-wrap:wrap; gap:8px; }
        .questions-score-wrap span { padding:8px 10px; border-radius:12px; background:#fee2e2; color:#b91c1c; font-size:12px; font-weight:900; }
        .questions-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
        .questions-btn { padding:10px 14px; color:#fff; }
        .questions-btn.info { background:#2BA7D8; } .questions-btn.warn { background:#F2A93B; } .questions-btn.danger { background:#ef4444; }
        .questions-empty { padding:28px; text-align:center; border-radius:20px; background:#E8F0F8; color:#6A7C93; font-weight:800; }
        .questions-pagination { margin-top:18px; color:#6A7C93; font-weight:700; }
        @media (max-width: 900px) { .questions-hero,.questions-card-head { grid-template-columns:1fr; display:grid; } .questions-summary-grid { grid-template-columns:1fr; } .question-item { grid-template-columns:1fr; } .questions-actions { justify-content:flex-start; } .questions-filter-select,.questions-search-input { min-width:100%; } .questions-title { font-size:34px; } }
    </style>

    <script>
        function filterQuestions() {
            const query = (document.getElementById('searchInput')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('.question-item');
            const noResultsRow = document.getElementById('noResultsRow');
            let anyVisible = false;

            rows.forEach(row => {
                const isMatch = (row.dataset.searchText || row.textContent.toLowerCase()).includes(query);
                row.style.display = isMatch ? '' : 'none';
                if (isMatch) anyVisible = true;
            });

            if (noResultsRow) noResultsRow.style.display = rows.length && !anyVisible ? '' : 'none';
        }
    </script>
@endsection
