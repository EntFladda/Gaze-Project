@extends('lecturer.layouts.app')

@section('content')
    @php
        $attentionStudents = $classInsights['attention_students'];
        $difficultQuestions = $classInsights['difficult_questions'];
        $difficultMissions = $classInsights['difficult_missions'];
        $recommendations = $classInsights['recommendations'];
        $readinessRate = $qualitySummary['total_questions'] > 0
            ? round(($qualitySummary['ready_questions'] / $qualitySummary['total_questions']) * 100)
            : 0;
    @endphp

    <div class="students-monitor-page">
        <section class="students-monitor-hero">
            <div>
                <p class="students-monitor-kicker">Monitoring Mahasiswa</p>
                <h1 class="students-monitor-title">Pantau hasil belajar mahasiswa</h1>
                <p class="students-monitor-copy">Lihat progres mission, poin, EXP, dan mahasiswa yang perlu ditindaklanjuti.</p>
            </div>

            <div class="students-monitor-actions">
                <form method="GET" action="{{ route('lecturer.students.index') }}" class="students-class-filter">
                    <label for="class">Filter Kelas</label>
                    <select name="class" id="class" onchange="this.form.submit()">
                        <option value="">Semua kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class }}" {{ (string) $selectedClass === (string) $class ? 'selected' : '' }}>{{ $class }}</option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('lecturer.students.create') }}" class="students-monitor-primary">Tambah Mahasiswa</a>
            </div>
        </section>

        @if (session('success'))
            <div class="students-alert success">{{ session('success') }}</div>
        @endif

        <section class="students-stat-grid">
            <article class="students-stat-card">
                <span>Total Mahasiswa</span>
                <strong>{{ $monitoringStats['total_students'] }}</strong>
            </article>
            <article class="students-stat-card">
                <span>Rata-rata Poin</span>
                <strong>{{ $monitoringStats['average_score'] }}</strong>
            </article>
            <article class="students-stat-card">
                <span>Mission Selesai</span>
                <strong>{{ $monitoringStats['completed_missions'] }}</strong>
            </article>
            <article class="students-stat-card">
                <span>Rata-rata EXP</span>
                <strong>{{ $monitoringStats['average_exp'] }}</strong>
            </article>
        </section>

        <section class="students-view-switch" aria-label="Pilih tampilan mahasiswa">
            <button type="button" class="students-view-tab is-active" data-target="monitoring-view" onclick="switchStudentView(this)">
                Monitoring
            </button>
            <button type="button" class="students-view-tab" data-target="analysis-view" onclick="switchStudentView(this)">
                Analisis kelas
            </button>
        </section>

        <section id="monitoring-view" class="students-view">
            <div class="students-monitor-grid">
                <article class="students-monitor-card bg-white">
                    <div class="students-card-head compact">
                        <div>
                            <p class="students-card-kicker">Mahasiswa</p>
                            <h2 class="students-card-title">Tabel monitoring progres</h2>
                        </div>
                        <input type="text" id="searchInput" placeholder="Cari mahasiswa..." class="students-search-input" onkeyup="filterTable()">
                    </div>

                    <div class="students-table-wrap">
                        <table class="students-monitor-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>NIM</th>
                                    <th>Kelas</th>
                                    <th>Progres</th>
                                    <th>Poin</th>
                                    <th>EXP</th>
                                    <th>Peringkat</th>
                                    <th>Hari</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $student)
                                    @php($monitoring = $student->monitoring)
                                    <tr class="student-row {{ $selectedStudent?->id === $student->id ? 'is-selected' : '' }}">
                                        <td class="student-name"><strong>{{ $student->user->name }}</strong></td>
                                        <td class="student-nim">{{ $student->nim }}</td>
                                        <td>{{ $student->class ?? '-' }}</td>
                                        <td>
                                            <div class="students-progress">
                                                <span style="width: {{ $monitoring->progress }}%"></span>
                                            </div>
                                            <small>{{ $monitoring->accuracy }}%</small>
                                        </td>
                                        <td>{{ $student->total_score }}</td>
                                        <td>{{ $student->exp }}</td>
                                        <td><span class="students-rank-pill">{{ $student->current_rank?->name ?? 'Baru' }}</span></td>
                                        <td>{{ $student->streak }}</td>
                                        <td>
                                            <div class="students-row-actions">
                                                <a href="{{ route('lecturer.students.show', $student->id) }}" class="students-mini-btn">Detail</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noDataRow">
                                        <td colspan="9"><div class="students-empty">Belum ada mahasiswa pada kelas ini.</div></td>
                                    </tr>
                                @endforelse
                                <tr id="noResultsRow" style="display:none;">
                                    <td colspan="9"><div class="students-empty">Tidak ada mahasiswa yang cocok dengan pencarian.</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="students-pagination">
                        <p class="students-page-info">Halaman {{ $students->currentPage() }} dari {{ $students->lastPage() }}</p>

                        @if ($students->hasPages())
                            <nav class="students-pagination-nav" aria-label="Navigasi halaman mahasiswa">
                                <a href="{{ $students->onFirstPage() ? '#' : $students->previousPageUrl() }}"
                                    class="students-page-link students-page-arrow {{ $students->onFirstPage() ? 'is-disabled' : '' }}"
                                    @if ($students->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                                    Sebelumnya
                                </a>

                                <div class="students-page-numbers">
                                    @foreach ($students->getUrlRange(1, $students->lastPage()) as $page => $url)
                                        <a href="{{ $url }}"
                                            class="students-page-link {{ $page === $students->currentPage() ? 'is-active' : '' }}"
                                            aria-current="{{ $page === $students->currentPage() ? 'page' : 'false' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach
                                </div>

                                <a href="{{ $students->hasMorePages() ? $students->nextPageUrl() : '#' }}"
                                    class="students-page-link students-page-arrow {{ $students->hasMorePages() ? '' : 'is-disabled' }}"
                                    @if (! $students->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
                                    Berikutnya
                                </a>
                            </nav>
                        @endif
                    </div>
                </article>


            </div>
        </section>

        <section id="analysis-view" class="students-view hidden">
            <div class="students-insight-grid">
                <article class="students-insight-card accuracy">
                    <span>Akurasi kelas</span>
                    <strong>{{ $classInsights['average_accuracy'] }}%</strong>
                    <small>Dari jawaban yang sudah masuk.</small>
                </article>

                <article class="students-insight-card attention">
                    <span>Perlu dibantu</span>
                    <strong>{{ $classInsights['attention_count'] }}</strong>
                    <small>Prioritas pendampingan mahasiswa.</small>
                </article>

                <article class="students-insight-card warning">
                    <span>Mission kosong</span>
                    <strong>{{ $classInsights['empty_challenges'] }}</strong>
                    <small>Belum memiliki soal aktif.</small>
                </article>

                <article class="students-insight-card readiness">
                    <span>Kesiapan soal</span>
                    <strong>{{ $readinessRate }}%</strong>
                    <small>{{ $qualitySummary['ready_questions'] }} siap, {{ $qualitySummary['needs_review'] }} perlu dilengkapi.</small>
                </article>
            </div>

            <div class="students-analysis-grid">
                <article class="students-panel recommendations">
                    <div class="students-panel-head">
                        <p>Tindak lanjut</p>
                        <h2>Rekomendasi dosen</h2>
                    </div>

                    <div class="students-recommendation-list">
                        @foreach ($recommendations as $index => $recommendation)
                            <div class="students-recommendation-row">
                                <span>{{ $index + 1 }}</span>
                                <p>{{ $recommendation }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="students-panel">
                    <div class="students-panel-head with-note">
                        <div>
                            <p>Prioritas mahasiswa</p>
                            <h2>Mahasiswa perlu dibantu</h2>
                        </div>
                        <span class="students-panel-note">{{ $classInsights['attention_count'] }} orang</span>
                    </div>

                    <div class="students-list">
                        @forelse ($attentionStudents as $student)
                            <div class="students-list-row">
                                <div class="students-list-main">
                                    <strong>{{ $student->name }}</strong>
                                    <span>{{ $student->status }}</span>
                                    <small>{{ $student->current_mission }}</small>
                                </div>
                                <div class="students-score-pill">
                                    <b>{{ $student->accuracy === null ? '-' : $student->accuracy . '%' }}</b>
                                    <small>{{ $student->wrong }} koreksi</small>
                                </div>
                            </div>
                        @empty
                            <div class="students-empty compact">
                                <strong>Kelas aman</strong>
                                <span>Belum ada mahasiswa yang perlu pendampingan khusus.</span>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="students-panel">
                    <div class="students-panel-head with-note">
                        <div>
                            <p>Kesulitan soal</p>
                            <h2>Soal perlu dibahas ulang</h2>
                        </div>
                        <span class="students-panel-note">{{ $classInsights['difficult_question_count'] }} soal</span>
                    </div>

                    <div class="students-list">
                        @forelse ($difficultQuestions as $row)
                            <div class="students-list-row">
                                <div class="students-list-main">
                                    <strong>{{ Str::limit(strip_tags((string) $row->question->question_text), 84) }}</strong>
                                    <span>{{ $row->question->challenge?->title ?? 'Mission' }}</span>
                                    <small>{{ $row->attempts }} percobaan tercatat</small>
                                </div>
                                <div class="students-score-pill danger">
                                    <b>{{ $row->wrong_rate }}%</b>
                                    <small>{{ $row->wrong }} koreksi</small>
                                </div>
                            </div>
                        @empty
                            <div class="students-empty compact">
                                <strong>Belum ada soal sulit</strong>
                                <span>Data ini muncul setelah mahasiswa menjawab soal.</span>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="students-panel">
                    <div class="students-panel-head with-note">
                        <div>
                            <p>Kesulitan mission</p>
                            <h2>Mission paling berat</h2>
                        </div>
                        <span class="students-panel-note">{{ $classInsights['difficult_mission_count'] }} mission</span>
                    </div>

                    <div class="students-list">
                        @forelse ($difficultMissions as $row)
                            <div class="students-list-row">
                                <div class="students-list-main">
                                    <strong>{{ $row->challenge->title }}</strong>
                                    <span>{{ $row->challenge->section?->name ?? 'Tanpa bagian' }}</span>
                                    <small>{{ $row->attempts }} percobaan</small>
                                </div>
                                <div class="students-score-pill warning">
                                    <b>{{ $row->accuracy }}%</b>
                                    <small>akurasi</small>
                                </div>
                            </div>
                        @empty
                            <div class="students-empty compact">
                                <strong>Belum ada riwayat</strong>
                                <span>Mission sulit akan terlihat setelah ada percobaan mahasiswa.</span>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>
    </div>

    <style>
        .students-monitor-page { max-width: 1240px; margin: 0 auto; color:#0A2342; }
        .students-monitor-hero { display:grid; grid-template-columns:1fr auto; gap:22px; align-items:end; margin-bottom:18px; padding:28px; border-radius:30px; border:1px solid rgba(191,219,254,.14); background:rgba(10,35,66,.78); box-shadow:0 20px 50px rgba(0,0,0,.22); }
        .students-monitor-kicker { margin:0; font-size:12px; letter-spacing:.32em; text-transform:uppercase; color:rgba(191,219,254,.75); font-weight:900; }
        .students-monitor-title { margin:10px 0 0; color:#fff; font-size:40px; line-height:1.12; font-weight:850; }
        .students-monitor-copy { margin:12px 0 0; color:rgba(219,234,254,.76); line-height:1.7; }
        .students-monitor-actions { display:flex; align-items:end; gap:12px; flex-wrap:wrap; justify-content:flex-end; }
        .students-class-filter { display:grid; gap:8px; }
        .students-class-filter label { color:rgba(219,234,254,.8); font-weight:900; font-size:13px; }
        .students-class-filter select, .students-search-input { border:1px solid #9CB8D8; border-radius:16px; padding:13px 15px; background:#fff; color:#0A2342; font-weight:800; outline:none; }
        .students-monitor-primary { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 18px; border-radius:16px; background:linear-gradient(135deg,#1D5FD6,#F2A93B); color:#fff; text-decoration:none; font-weight:900; box-shadow:0 14px 26px rgba(37,99,235,.24); }
        .students-alert { margin-bottom:16px; padding:14px 18px; border-radius:18px; font-weight:800; }
        .students-alert.success { background:#dcfce7; color:#166534; }
        .students-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
        .students-stat-card, .students-monitor-card, .students-detail-panel, .students-panel, .students-insight-card { background:rgba(244,248,252,.97); border:1px solid rgba(226,232,240,.92); box-shadow:0 18px 42px rgba(15,23,42,.08); }
        .students-stat-card { padding:20px; border-radius:20px; }
        .students-stat-card span, .students-card-kicker, .students-panel-head p, .students-insight-card span { display:block; margin:0; font-size:12px; text-transform:uppercase; letter-spacing:.22em; font-weight:900; color:#1D5FD6; }
        .students-stat-card strong { display:block; margin-top:8px; color:#0A2342; font-size:34px; line-height:1; }
        .students-view-switch { display:inline-flex; gap:8px; padding:8px; margin-bottom:16px; border-radius:18px; border:1px solid rgba(191,219,254,.16); background:rgba(255,255,255,.08); }
        .students-view-tab { border:0; border-radius:13px; padding:10px 16px; background:transparent; color:#DCE7F3; font-weight:900; cursor:pointer; }
        .students-view-tab.is-active { background:#fff; color:#123A68; box-shadow:0 12px 24px rgba(15,23,42,.14); }
        .students-monitor-grid { display:grid; grid-template-columns:1fr; gap:18px; align-items:start; }
        .students-monitor-card, .students-detail-panel { padding:20px; border-radius:22px; }
        .students-card-head.compact { display:flex; justify-content:space-between; gap:16px; align-items:end; margin-bottom:14px; }
        .students-card-title { margin:6px 0 0; color:#0A2342; font-size:22px; font-weight:900; }
        .students-table-wrap { overflow-x:auto; }
        .students-monitor-table { width:100%; border-collapse:collapse; min-width:840px; }
        .students-monitor-table thead th { padding:13px 12px; text-align:left; background:#0A2342; color:#fff; font-size:11px; letter-spacing:.12em; text-transform:uppercase; }
        .students-monitor-table thead th:first-child { border-top-left-radius:12px; }
        .students-monitor-table thead th:last-child { border-top-right-radius:12px; }
        .students-monitor-table tbody td { padding:14px 12px; border-bottom:1px solid #DCE7F3; color:#263E5C; vertical-align:middle; }
        .students-monitor-table tbody tr:nth-child(even) { background:#E8F0F8; }
        .students-monitor-table tbody tr:hover, .students-monitor-table tbody tr.is-selected { background:#E8F0F8; }
        .student-name strong { color:#0A2342; }
        .student-nim { color:#53657A; font-weight:700; }
        .students-progress { width:72px; height:9px; border-radius:999px; background:#e8eef8; overflow:hidden; }
        .students-progress span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#7c5cff,#1D5FD6); }
        .students-monitor-table small { color:#6A7C93; font-size:11px; font-weight:800; }
        .students-rank-pill { display:inline-flex; padding:7px 10px; border-radius:999px; background:#D9EEF7; color:#0369a1; font-size:12px; font-weight:900; }
        .students-row-actions { display:flex; gap:6px; flex-wrap:wrap; }
        .students-mini-btn { display:inline-flex; align-items:center; justify-content:center; padding:8px 10px; border-radius:10px; background:#2BA7D8; color:#fff; text-decoration:none; font-weight:900; font-size:12px; }
       
       
        .students-empty { padding:24px; text-align:center; color:#6A7C93; font-weight:800; }
        .students-empty.compact { min-height:120px; display:flex; flex-direction:column; justify-content:center; gap:8px; border:1px dashed #9CB8D8; border-radius:18px; background:#E8F0F8; }
        .students-pagination { margin-top:16px; color:#6A7C93; font-weight:800; }
        .students-page-info { margin:0 0 12px; color:#6A7C93; font-size:14px; font-weight:900; }
        .students-pagination-nav { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .students-page-numbers { display:flex; align-items:center; justify-content:center; gap:8px; flex-wrap:wrap; }
        .students-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:42px; padding:10px 14px; border-radius:14px; border:1px solid #B7CCE6; background:#E8F0F8; color:#1D5FD6; font-weight:900; text-decoration:none; transition:.18s ease; }
        .students-page-link:hover { background:#DCE7F3; border-color:#2BA7D8; transform:translateY(-1px); }
        .students-page-link.is-active { background:linear-gradient(135deg,#1D5FD6,#F2A93B); border-color:transparent; color:#fff; box-shadow:0 12px 24px rgba(37,99,235,.22); }
        .students-page-link.is-disabled { pointer-events:none; opacity:.45; }
        .students-page-arrow { min-width:112px; }
        .students-insight-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
        .students-insight-card { padding:18px; border-radius:22px; }
        .students-insight-card strong { display:block; margin-top:10px; color:#0A2342; font-size:34px; line-height:1; font-weight:900; }
        .students-insight-card small { display:block; margin-top:10px; color:#6A7C93; font-weight:800; line-height:1.5; }
        .students-analysis-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
        .students-panel { padding:22px; border-radius:24px; }
        .students-panel.recommendations { background:linear-gradient(145deg,#fff,#E8F0F8); }
        .students-panel-head { margin-bottom:16px; }
        .students-panel-head.with-note { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
        .students-panel-head h2 { margin:8px 0 0; color:#0A2342; font-size:22px; line-height:1.2; font-weight:900; }
        .students-panel-note { display:inline-flex; white-space:nowrap; padding:8px 12px; border-radius:999px; background:#f8fafc; color:#53657A; font-size:12px; font-weight:900; }
        .students-recommendation-list, .students-list { display:grid; gap:12px; }
        .students-recommendation-row { display:grid; grid-template-columns:34px 1fr; gap:12px; align-items:start; padding:14px; border-radius:18px; border:1px solid #B7CCE6; background:#fff; }
        .students-recommendation-row span { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:12px; background:#1D5FD6; color:#fff; font-weight:900; }
        .students-recommendation-row p { margin:0; color:#0A2342; font-weight:800; line-height:1.6; }
        .students-list-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border:1px solid #e2e8f0; border-radius:18px; background:#fff; }
        .students-list-main strong, .students-list-main span, .students-list-main small { display:block; }
        .students-list-main strong { color:#0A2342; font-weight:900; line-height:1.35; }
        .students-list-main span { margin-top:5px; color:#53657A; font-size:13px; font-weight:800; }
        .students-list-main small { margin-top:4px; color:#68758c; line-height:1.5; }
        .students-score-pill { min-width:86px; padding:10px 12px; border-radius:18px; background:#E8F0F8; color:#1D5FD6; text-align:center; font-weight:900; }
        .students-score-pill b, .students-score-pill small { display:block; }
        .students-score-pill small { margin-top:4px; color:inherit; opacity:.72; font-size:11px; }
        .students-score-pill.danger { background:#fee2e2; color:#b91c1c; }
        .students-score-pill.warning { background:#fff7ed; color:#c2410c; }
        .hidden { display:none !important; }
        @media (max-width:1100px) { .students-monitor-grid { grid-template-columns:1fr; } .students-stat-grid,.students-insight-grid,.students-analysis-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:760px) { .students-monitor-hero,.students-card-head.compact { grid-template-columns:1fr; display:grid; } .students-monitor-actions { justify-content:stretch; } .students-class-filter select,.students-search-input,.students-monitor-primary { width:100%; box-sizing:border-box; } .students-stat-grid,.students-insight-grid,.students-analysis-grid { grid-template-columns:1fr; } .students-monitor-title { font-size:32px; } .students-view-switch { width:100%; display:grid; grid-template-columns:1fr 1fr; box-sizing:border-box; } .students-panel-head.with-note,.students-list-row { flex-direction:column; align-items:stretch; } .students-score-pill { width:100%; } .students-pagination-nav { flex-direction:column; align-items:stretch; } .students-page-arrow { width:100%; box-sizing:border-box; } }
    </style>

    <script>
        function switchStudentView(button) {
            const targetId = button.dataset.target;
            document.querySelectorAll('.students-view-tab').forEach(tab => tab.classList.toggle('is-active', tab === button));
            document.querySelectorAll('.students-view').forEach(view => view.classList.toggle('hidden', view.id !== targetId));
        }

        function filterTable() {
            const query = (document.getElementById('searchInput')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('.student-row');
            const noResultsRow = document.getElementById('noResultsRow');
            let anyVisible = false;

            rows.forEach(row => {
                const match = row.textContent.toLowerCase().includes(query);
                row.style.display = match ? '' : 'none';
                if (match) anyVisible = true;
            });

            if (noResultsRow) noResultsRow.style.display = rows.length && !anyVisible ? '' : 'none';
        }
    </script>
@endsection
