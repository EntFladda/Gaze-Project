@extends('lecturer.layouts.app')

@section('content')
    @php
        $totalAttempts = $results->count();
        $totalCorrect = $results->sum('correct_answers');
        $totalWrong = $results->sum('wrong_answers');
        $answered = $totalCorrect + $totalWrong;
        $accuracy = $answered > 0 ? round(($totalCorrect / $answered) * 100) : 0;
        $completedMissions = $results->whereNotNull('ended_at')->pluck('challenge_id')->unique()->count();

        $resultsWithFocus = $results->whereNotNull('focus_percentage');
        $avgFocusPct = $resultsWithFocus->count() > 0 ? round($resultsWithFocus->avg('focus_percentage')) : 0;
        $totalUnfocusedCount = $results->sum('unfocused_count');
        $avgFocusedDuration = $resultsWithFocus->count() > 0 ? round($resultsWithFocus->avg('focused_duration')) : 0;
        $avgUnfocusedDuration = $resultsWithFocus->count() > 0 ? round($resultsWithFocus->avg('unfocused_duration')) : 0;
    @endphp

    <div class="student-detail-page">
        <section class="student-detail-hero">
            <div class="student-detail-profile">
                <img src="{{ $student->user->profile_photo ? asset('storage/' . $student->user->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}"
                    alt="Foto profil" class="student-detail-avatar">
                <div>
                    <p class="student-detail-kicker">Detail Mahasiswa</p>
                    <h1 class="student-detail-title">{{ $student->user->name }}</h1>
                    <p class="student-detail-copy">
                        {{ $student->nim }} <span aria-hidden="true">&bull;</span> {{ $student->class ?? 'Kelas belum diisi' }} <span aria-hidden="true">&bull;</span> {{ $student->prodi ?? 'Program studi belum diisi' }}
                    </p>
                </div>
            </div>

            <div class="student-detail-hero-actions">
                <a href="{{ route('lecturer.students.index') }}" class="student-detail-back">Kembali</a>
                <a href="{{ route('lecturer.students.edit', $student->id) }}" class="student-detail-edit">Edit</a>
            </div>
        </section>

        <section class="student-summary-grid">
            <article class="student-summary-card bg-white">
                <span>Peringkat</span>
                <strong>{{ $student->current_rank?->name ?? 'Baru' }}</strong>
            </article>
            <article class="student-summary-card bg-white">
                <span>Total Poin</span>
                <strong>{{ $student->total_score }}</strong>
            </article>
            <article class="student-summary-card bg-white">
                <span>EXP</span>
                <strong>{{ $student->exp }}</strong>
            </article>
            <article class="student-summary-card bg-white">
                <span>Akurasi</span>
                <strong>{{ $accuracy }}%</strong>
            </article>
        </section>

        <section class="student-summary-grid mt-4">
            <article class="student-summary-card" style="background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border: 1px solid rgba(22, 163, 74, .25);">
                <span style="color: #166534;">Rata-rata Fokus</span>
                <strong style="color: #166534;">{{ $avgFocusPct }}%</strong>
            </article>
            <article class="student-summary-card" style="background: linear-gradient(135deg, #FEF2F2, #FEE2E2); border: 1px solid rgba(220, 38, 38, .25);">
                <span style="color: #991B1B;">Total Gangguan</span>
                <strong style="color: #991B1B;">{{ $totalUnfocusedCount }} kali</strong>
            </article>
            <article class="student-summary-card" style="background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border: 1px solid rgba(22, 163, 74, .25);">
                <span style="color: #166534;">Rata-rata Durasi Fokus</span>
                <strong style="color: #166534;">{{ $avgFocusedDuration }}s</strong>
            </article>
            <article class="student-summary-card" style="background: linear-gradient(135deg, #FEF2F2, #FEE2E2); border: 1px solid rgba(220, 38, 38, .25);">
                <span style="color: #991B1B;">Rata-rata Durasi Terganggu</span>
                <strong style="color: #991B1B;">{{ $avgUnfocusedDuration }}s</strong>
            </article>
        </section>

        <section class="student-info-grid">
            <article class="student-detail-card bg-white">
                <div class="student-card-head">
                    <p>Identitas</p>
                    <h2>Data akademik</h2>
                </div>
                <div class="student-info-list">
                    <div><span>NIM</span><strong>{{ $student->nim }}</strong></div>
                    <div><span>Email</span><strong>{{ $student->user->email }}</strong></div>
                    <div><span>Program Studi</span><strong>{{ $student->prodi ?? '-' }}</strong></div>
                    <div><span>Semester</span><strong>{{ $student->semester ?? '-' }}</strong></div>
                    <div><span>Kelas</span><strong>{{ $student->class ?? '-' }}</strong></div>
                </div>
            </article>

            <article class="student-detail-card bg-white">
                <div class="student-card-head">
                    <p>Belajar</p>
                    <h2>Progres mission</h2>
                </div>
                <div class="student-info-list">
                    <div><span>Mission selesai</span><strong>{{ $completedMissions }}</strong></div>
                    <div><span>Percobaan</span><strong>{{ $totalAttempts }}</strong></div>
                    <div><span>Jawaban benar</span><strong>{{ $totalCorrect }}</strong></div>
                    <div><span>Percobaan koreksi</span><strong>{{ $totalWrong }} kali</strong></div>
                    <div><span>Hari beruntun</span><strong>{{ $student->streak }} hari</strong></div>
                    <div><span>Terakhir bermain</span><strong>{{ $student->last_played ? \Carbon\Carbon::parse($student->last_played)->translatedFormat('d F Y') : 'Belum pernah' }}</strong></div>
                    <div><span>Mission berjalan</span><strong>{{ $student->currentChallenge?->title ?? 'Tidak ada' }}</strong></div>
                    <div><span>Bagian berjalan</span><strong>{{ $student->currentSection?->name ?? 'Tidak ada' }}</strong></div>
                </div>
            </article>
        </section>

        <section class="student-detail-card bg-white student-results-card">
            <div class="student-results-head">
                <div>
                    <p>Riwayat Mission</p>
                    <h2>Hasil pengerjaan mahasiswa</h2>
                </div>
            </div>

            <div class="student-results-wrap">
                <table class="student-results-table">
                    <thead>
                        <tr>
                            <th>Mission</th>
                            <th>Percobaan</th>
                            <th>Poin</th>
                            <th>EXP</th>
                            <th>Benar</th>
                            <th>Koreksi</th>
                            <th>Fokus</th>
                            <th>Gg. Fokus</th>
                            <th>Durasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($results as $result)
                            @php
                                $start = \Carbon\Carbon::parse($result->created_at);
                                $end = \Carbon\Carbon::parse($result->ended_at ?? $result->updated_at ?? now());
                                $duration = $start->diff($end)->format('%h jam %i menit %s detik');
                            @endphp
                            <tr>
                                <td><strong>{{ $result->challenge->title ?? 'Mission' }}</strong></td>
                                <td>#{{ $result->attempt_number }}</td>
                                <td>{{ $result->total_score }}</td>
                                <td>{{ $result->total_exp }}</td>
                                <td><span class="result-pill success">{{ $result->correct_answers }}</span></td>
                                <td><span class="result-pill danger">{{ $result->wrong_answers }}</span></td>
                                <td>{{ $result->focus_percentage ?? '-' }}%</td>
                                <td>{{ $result->unfocused_count ?? '-' }}x</td>
                                <td>{{ $duration }}</td>
                                <td>
                                    <a href="{{ route('lecturer.students.detail_result', ['student' => $student->id, 'challenge' => $result->challenge_id, 'attempt' => $result->attempt_number]) }}"
                                        class="student-detail-btn">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="students-empty">Belum ada hasil mission untuk mahasiswa ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <style>
        .student-detail-page { max-width: 1180px; margin: 0 auto; color:#0A2342; }
        .student-detail-hero { display:flex; justify-content:space-between; align-items:center; gap:18px; margin-bottom:18px; padding:24px; border-radius:26px; border:1px solid rgba(191,219,254,.14); background:rgba(10,35,66,.78); box-shadow:0 20px 50px rgba(0,0,0,.20); }
        .student-detail-profile { display:flex; align-items:center; gap:16px; min-width:0; }
        .student-detail-avatar { width:76px; height:76px; flex:0 0 auto; border-radius:22px; object-fit:cover; border:3px solid rgba(191,219,254,.28); background:#fff; }
        .student-detail-kicker, .student-card-head p, .student-results-head p, .student-summary-card span { margin:0; font-size:12px; letter-spacing:.24em; text-transform:uppercase; color:#1D5FD6; font-weight:900; }
        .student-detail-kicker { color:rgba(191,219,254,.78); }
        .student-detail-title { margin:8px 0 0; color:#fff; font-size:36px; line-height:1.1; font-weight:850; }
        .student-detail-copy { margin:8px 0 0; color:rgba(219,234,254,.76); line-height:1.5; }
        .student-detail-hero-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .student-detail-back, .student-detail-edit { display:inline-flex; align-items:center; justify-content:center; min-width:94px; padding:12px 16px; border-radius:14px; color:#fff; font-weight:900; text-decoration:none; }
        .student-detail-back { background:rgba(255,255,255,.12); }
        .student-detail-edit { background:linear-gradient(135deg,#1D5FD6,#F2A93B); box-shadow:0 14px 26px rgba(37,99,235,.22); }
        .student-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
        .student-summary-card, .student-detail-card { border:1px solid rgba(226,232,240,.9); box-shadow:0 18px 42px rgba(15,23,42,.08); }
        .student-summary-card { padding:18px; border-radius:20px; }
        .student-summary-card strong { display:block; margin-top:8px; color:#0A2342; font-size:30px; line-height:1; }
        .student-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
        .student-detail-card { padding:20px; border-radius:24px; }
        .student-card-head { margin-bottom:14px; }
        .student-card-head h2, .student-results-head h2 { margin:6px 0 0; color:#0A2342; font-size:24px; font-weight:900; }
        .student-info-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .student-info-list div { padding:13px 14px; border-radius:16px; background:#E8F0F8; border:1px solid #B7CCE6; min-width:0; }
        .student-info-list span { display:block; color:#123A68; font-size:12px; font-weight:900; margin-bottom:6px; }
        .student-info-list strong { display:block; color:#0A2342; font-size:14px; line-height:1.4; overflow-wrap:anywhere; }
        .student-results-card { margin-top:0; }
        .student-results-head { margin-bottom:14px; }
        .student-results-wrap { overflow-x:auto; }
        .student-results-table { width:100%; min-width:850px; border-collapse:collapse; }
        .student-results-table thead th { padding:13px 12px; text-align:left; background:#0A2342; color:#fff; font-size:11px; letter-spacing:.12em; text-transform:uppercase; }
        .student-results-table thead th:first-child { border-top-left-radius:12px; }
        .student-results-table thead th:last-child { border-top-right-radius:12px; }
        .student-results-table tbody td { padding:14px 12px; border-bottom:1px solid #DCE7F3; color:#263E5C; vertical-align:middle; }
        .student-results-table tbody tr:nth-child(even) { background:#E8F0F8; }
        .student-results-table tbody tr:hover { background:#E8F0F8; }
        .result-pill { display:inline-flex; min-width:34px; justify-content:center; padding:6px 10px; border-radius:999px; font-weight:900; font-size:12px; }
        .result-pill.success { background:#dcfce7; color:#166534; }
        .result-pill.danger { background:#fee2e2; color:#991b1b; }
        .student-detail-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:12px; background:#2BA7D8; color:#fff; font-weight:900; text-decoration:none; font-size:12px; }
        .students-empty { padding:22px; text-align:center; color:#6A7C93; font-weight:800; }
        @media (max-width: 900px) { .student-detail-hero { flex-direction:column; align-items:stretch; } .student-summary-grid, .student-info-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width: 640px) { .student-detail-profile { align-items:flex-start; } .student-detail-title { font-size:28px; } .student-summary-grid, .student-info-grid, .student-info-list { grid-template-columns:1fr; } .student-detail-hero-actions a { flex:1; } }
    </style>
@endsection
