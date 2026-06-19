@extends('lecturer.layouts.app')

@section('content')
    @php
        $activeRate = $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100) : 0;
        $inactiveStudents = max($totalStudents - $activeStudents, 0);
        $maxRankCount = max((int) ($rankStats->max('students_count') ?? 0), 1);
        $maxStreakCount = max((int) ($streakStats->max('total') ?? 0), 1);
    @endphp

    <div class="lecturer-dashboard-simple">
        <section class="dash-hero-simple">
            <div>
                <p class="dash-kicker">Dashboard Dosen</p>
                <h1>Ringkasan Kelas</h1>
                <span>Pantau progres mahasiswa dan aktivitas mission secara cepat.</span>
            </div>
            <a href="{{ route('lecturer.students.index') }}" class="dash-primary-link">Lihat Mahasiswa</a>
        </section>

        <section class="dash-stat-grid" aria-label="Ringkasan kelas">
            <article class="dash-stat-card">
                <p>Total Mahasiswa</p>
                <strong>{{ number_format($totalStudents) }}</strong>
            </article>
            <article class="dash-stat-card accent">
                <p>Mahasiswa Aktif</p>
                <strong>{{ number_format($activeStudents) }}</strong>
                <div class="dash-mini-progress"><span style="width: {{ $activeRate }}%"></span></div>
            </article>
            <article class="dash-stat-card muted">
                <p>Belum Aktif</p>
                <strong>{{ number_format($inactiveStudents) }}</strong>
            </article>
        </section>

        <section class="dash-main-grid">
            <article class="dash-panel top-students-panel">
                <div class="dash-panel-head">
                    <div>
                        <p>Performa</p>
                        <h2>Mahasiswa Teratas</h2>
                    </div>
                    <span>{{ $activeRate }}% aktif</span>
                </div>

                @if ($hasStudentProgress && $topStudents->isNotEmpty())
                    <div class="dash-table-wrap">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Skor</th>
                                    <th>Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topStudents as $student)
                                    <tr>
                                        <td>{{ $student->user->name }}</td>
                                        <td>{{ number_format($student->weekly_score) }}</td>
                                        <td><span>{{ $student->dashboard_rank_name ?? '-' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dash-empty-state">
                        <strong>Belum ada aktivitas</strong>
                        <span>Data akan muncul setelah mahasiswa mulai mengerjakan mission.</span>
                    </div>
                @endif
            </article>

            <article class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <p>Rank</p>
                        <h2>Sebaran Mahasiswa</h2>
                    </div>
                </div>

                @if ($hasStudentProgress && $rankStats->isNotEmpty())
                    <div class="dash-list-bars">
                        @foreach ($rankStats as $rank)
                            @php $width = round(((int) $rank->students_count / $maxRankCount) * 100); @endphp
                            <div class="dash-bar-item">
                                <div class="dash-bar-meta">
                                    <span>{{ $rank->name }}</span>
                                    <strong>{{ $rank->students_count }}</strong>
                                </div>
                                <div class="dash-bar-track"><span style="width: {{ $width }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty-state small">
                        <strong>Rank belum tersedia</strong>
                        <span>Belum ada EXP mahasiswa yang tercatat.</span>
                    </div>
                @endif
            </article>

            <article class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <p>Streak</p>
                        <h2>Kebiasaan Belajar</h2>
                    </div>
                </div>

                @if ($hasStudentProgress && $streakStats->isNotEmpty())
                    <div class="dash-streak-list">
                        @foreach ($streakStats as $streak)
                            @php $width = round(((int) $streak->total / $maxStreakCount) * 100); @endphp
                            <div class="dash-streak-item">
                                <span>{{ $streak->streak }} hari</span>
                                <div class="dash-bar-track"><span style="width: {{ $width }}%"></span></div>
                                <strong>{{ $streak->total }}</strong>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty-state small warm">
                        <strong>Streak masih kosong</strong>
                        <span>Aktivitas belajar belum tercatat.</span>
                    </div>
                @endif
            </article>
        </section>
    </div>

    <style>
        .lecturer-dashboard-simple {
            --dash-ink:#0A2342;
            --dash-muted:#6A7C93;
            --dash-card:#F4F8FC;
            --dash-line:#B7CCE6;
            --dash-blue:#1D5FD6;
            --dash-blue-dark:#0A2342;
            max-width:1180px;
            margin:0 auto;
            color:var(--dash-ink);
        }

        .dash-hero-simple {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
            padding:22px 24px;
            border:1px solid rgba(191,219,254,.14);
            border-radius:26px;
            background:linear-gradient(135deg,#0A2342,#0F2F57);
            box-shadow:0 16px 34px rgba(15,23,42,.18);
            margin-bottom:18px;
        }

        .dash-kicker,
        .dash-panel-head p,
        .dash-stat-card p {
            margin:0;
            font-size:11px;
            font-weight:900;
            letter-spacing:.18em;
            text-transform:uppercase;
        }

        .dash-kicker { color:rgba(191,219,254,.76); }
        .dash-hero-simple h1 { margin:8px 0 0; color:#fff; font-size:34px; line-height:1.1; font-weight:900; }
        .dash-hero-simple span { display:block; margin-top:8px; color:rgba(219,234,254,.75); line-height:1.55; }
        .dash-primary-link { display:inline-flex; align-items:center; justify-content:center; padding:12px 16px; border-radius:15px; background:#fff; color:var(--dash-blue-dark); font-size:14px; font-weight:900; text-decoration:none; white-space:nowrap; }

        .dash-stat-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:14px; }
        .dash-stat-card { min-height:122px; padding:20px; border-radius:22px; background:var(--dash-card); border:1px solid var(--dash-line); box-shadow:0 12px 28px rgba(15,23,42,.08); }
        .dash-stat-card p { color:var(--dash-muted); }
        .dash-stat-card strong { display:block; margin-top:12px; color:var(--dash-ink); font-size:36px; line-height:1; font-weight:900; }
        .dash-stat-card.accent { background:#E8F0F8; }
        .dash-stat-card.accent p { color:var(--dash-blue); }
        .dash-stat-card.muted { background:#f8fafc; }
        .dash-mini-progress { height:9px; margin-top:16px; overflow:hidden; border-radius:999px; background:#DCE7F3; }
        .dash-mini-progress span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,#1D5FD6,#2BA7D8); }

        .dash-main-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(280px,.8fr); gap:14px; }
        .dash-panel { padding:20px; border-radius:24px; background:var(--dash-card); border:1px solid var(--dash-line); box-shadow:0 12px 28px rgba(15,23,42,.08); }
        .top-students-panel { grid-row:span 2; }
        .dash-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .dash-panel-head p { color:var(--dash-blue); }
        .dash-panel-head h2 { margin:6px 0 0; color:var(--dash-ink); font-size:22px; line-height:1.2; font-weight:900; }
        .dash-panel-head > span { padding:7px 11px; border-radius:999px; background:#E8F0F8; color:var(--dash-blue); font-size:12px; font-weight:900; }

        .dash-table-wrap { overflow-x:auto; border:1px solid #B7CCE6; border-radius:18px; }
        .dash-table { width:100%; min-width:480px; border-collapse:collapse; }
        .dash-table th, .dash-table td { padding:13px 14px; text-align:left; border-bottom:1px solid #DCE7F3; }
        .dash-table th { background:#E8F0F8; color:var(--dash-blue); font-size:11px; font-weight:900; letter-spacing:.12em; text-transform:uppercase; }
        .dash-table tr:last-child td { border-bottom:0; }
        .dash-table td { color:#263E5C; font-weight:700; }
        .dash-table td span { display:inline-flex; padding:6px 10px; border-radius:999px; background:#DCE7F3; color:#15509A; font-size:12px; font-weight:900; }

        .dash-list-bars, .dash-streak-list { display:flex; flex-direction:column; gap:12px; }
        .dash-bar-meta, .dash-streak-item { display:flex; align-items:center; gap:10px; justify-content:space-between; color:#263E5C; font-weight:800; }
        .dash-bar-meta span { max-width:70%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .dash-bar-meta strong, .dash-streak-item strong { color:var(--dash-blue); }
        .dash-bar-track { height:9px; flex:1; min-width:90px; overflow:hidden; border-radius:999px; background:#DCE7F3; }
        .dash-bar-track span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,#1D5FD6,#2BA7D8); }
        .dash-streak-item > span { width:70px; color:#53657A; font-size:13px; }

        .dash-empty-state { min-height:210px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:20px; border:1px dashed #9CB8D8; border-radius:20px; background:#E8F0F8; text-align:center; color:var(--dash-muted); }
        .dash-empty-state.small { min-height:150px; }
        .dash-empty-state strong { color:var(--dash-blue); font-size:18px; }
        .dash-empty-state.warm { background:#fff7ed; border-color:#fed7aa; }
        .dash-empty-state.warm strong { color:#d97706; }

        @media (max-width:1000px) { .dash-main-grid { grid-template-columns:1fr; } .top-students-panel { grid-row:auto; } }
        @media (max-width:760px) { .dash-hero-simple { flex-direction:column; align-items:stretch; } .dash-primary-link { width:100%; } .dash-stat-grid { grid-template-columns:1fr; } .dash-hero-simple h1 { font-size:29px; } }
    </style>
@endsection
