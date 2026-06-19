@extends('lecturer.layouts.app')

@section('content')
    <div class="missions-page-simple">
        <section class="missions-hero-simple">
            <div>
                <p class="missions-kicker">Mission</p>
                <h1>Daftar Mission</h1>
                <span>Kelola mission belajar di setiap bagian materi.</span>
            </div>

            <div class="missions-hero-actions">
                <form method="GET" action="{{ route('lecturer.challenges.index') }}" class="missions-filter-form">
                    <label for="section_id">Bagian</label>
                    <select name="section_id" id="section_id" onchange="this.form.submit()">
                        <option value="">Semua bagian</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" {{ (string) $sectionSearch === (string) $section->id ? 'selected' : '' }}>
                                {{ $section->order }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('lecturer.challenges.create', ['section_id' => request('section_id')]) }}" class="missions-primary-btn">
                    Tambah Mission
                </a>
            </div>
        </section>

        @if (session('success'))
            <div class="missions-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="missions-alert error">{{ session('error') }}</div>
        @endif

        <section class="missions-card-simple bg-white">
            <div class="missions-card-head-simple">
                <div>
                    <p>Mission Aktif</p>
                    <h2>Alur Tantangan</h2>
                </div>
                <span>{{ $challenges->total() }} mission</span>
            </div>

            <div class="missions-list">
                @forelse ($challenges as $challenge)
                    <article class="mission-row">
                        <div class="mission-section-pill">
                            {{ $challenge->section->order ?? '-' }}
                        </div>

                        <div class="mission-main">
                            <strong>{{ $challenge->title }}</strong>
                            <span>{{ $challenge->section->name ?? 'Belum memilih bagian' }}</span>
                        </div>

                        <div class="mission-stats">
                            <div>
                                <span>Soal</span>
                                <strong>{{ $challenge->questions_count }}</strong>
                            </div>
                            <div>
                                <span>EXP</span>
                                <strong>{{ $challenge->total_exp }}</strong>
                            </div>
                            <div>
                                <span>Poin</span>
                                <strong>{{ $challenge->total_score }}</strong>
                            </div>
                        </div>

                        <div class="mission-actions">
                            <a href="{{ route('lecturer.challenges.edit', $challenge->id) }}" class="mission-btn edit">Edit</a>
                            <a href="{{ route('lecturer.questions.index', ['challenge_id' => $challenge->id]) }}" class="mission-btn questions">Soal</a>
                            <form action="{{ route('lecturer.challenges.destroy', $challenge->id) }}" method="POST"
                                onsubmit="return confirm('Hapus mission ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mission-btn danger">Hapus</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="missions-empty-simple">
                        Belum ada mission. Tambahkan mission pertama untuk mulai menyusun tantangan belajar.
                    </div>
                @endforelse
            </div>

            <div class="missions-pagination">
                <p class="missions-page-info">Halaman {{ $challenges->currentPage() }} dari {{ $challenges->lastPage() }}</p>

                @if ($challenges->hasPages())
                    <nav class="missions-pagination-nav" aria-label="Navigasi halaman mission">
                        <a href="{{ $challenges->onFirstPage() ? '#' : $challenges->previousPageUrl() }}"
                            class="missions-page-link missions-page-arrow {{ $challenges->onFirstPage() ? 'is-disabled' : '' }}"
                            @if ($challenges->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>

                        <div class="missions-page-numbers">
                            @foreach ($challenges->getUrlRange(1, $challenges->lastPage()) as $page => $url)
                                <a href="{{ $url }}" class="missions-page-link {{ $page === $challenges->currentPage() ? 'is-active' : '' }}"
                                    aria-current="{{ $page === $challenges->currentPage() ? 'page' : 'false' }}">{{ $page }}</a>
                            @endforeach
                        </div>

                        <a href="{{ $challenges->hasMorePages() ? $challenges->nextPageUrl() : '#' }}"
                            class="missions-page-link missions-page-arrow {{ $challenges->hasMorePages() ? '' : 'is-disabled' }}"
                            @if (! $challenges->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>Berikutnya</a>
                    </nav>
                @endif
            </div>
        </section>
    </div>

    <style>
        .missions-page-simple { max-width:1180px; margin:0 auto; color:#0A2342; }
        .missions-hero-simple { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; padding:22px 24px; border-radius:26px; border:1px solid rgba(191,219,254,.14); background:linear-gradient(135deg,#0A2342,#0F2F57); box-shadow:0 16px 34px rgba(15,23,42,.18); margin-bottom:18px; }
        .missions-kicker, .missions-card-head-simple p { margin:0; font-size:11px; font-weight:900; letter-spacing:.18em; text-transform:uppercase; }
        .missions-kicker { color:rgba(191,219,254,.76); }
        .missions-hero-simple h1 { margin:8px 0 0; color:#fff; font-size:34px; line-height:1.1; font-weight:900; }
        .missions-hero-simple span { display:block; margin-top:8px; color:rgba(219,234,254,.75); line-height:1.55; }
        .missions-hero-actions { display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
        .missions-filter-form { display:flex; flex-direction:column; gap:7px; }
        .missions-filter-form label { color:rgba(219,234,254,.72); font-size:13px; font-weight:800; }
        .missions-filter-form select { min-width:230px; height:46px; padding:0 13px; border-radius:15px; border:1px solid rgba(191,219,254,.18); background:rgba(255,255,255,.08); color:#fff; outline:0; }
        .missions-filter-form select option { color:#09254A; background:#fff; }
        .missions-primary-btn { height:46px; display:inline-flex; align-items:center; justify-content:center; padding:0 16px; border-radius:15px; background:#fff; color:#0A2342; font-size:14px; font-weight:900; text-decoration:none; white-space:nowrap; }
        .missions-alert { margin-bottom:14px; padding:13px 16px; border-radius:16px; font-weight:800; }
        .missions-alert.success { background:rgba(220,252,231,.96); color:#166534; }
        .missions-alert.error { background:rgba(254,226,226,.96); color:#991b1b; }
        .missions-card-simple { padding:20px; border-radius:24px; border:1px solid #B7CCE6; box-shadow:0 12px 28px rgba(15,23,42,.08); }
        .missions-card-head-simple { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:16px; }
        .missions-card-head-simple p { color:#1D5FD6; }
        .missions-card-head-simple h2 { margin:6px 0 0; color:#0A2342; font-size:24px; font-weight:900; }
        .missions-card-head-simple > span { padding:7px 11px; border-radius:999px; background:#E8F0F8; color:#1D5FD6; font-size:12px; font-weight:900; }
        .missions-list { display:flex; flex-direction:column; gap:10px; }
        .mission-row { display:grid; grid-template-columns:52px minmax(210px,1fr) minmax(250px,.9fr) auto; align-items:center; gap:12px; padding:14px; border:1px solid #B7CCE6; border-radius:20px; background:#F4F8FC; transition:.16s ease; }
        .mission-row:hover { background:#E8F0F8; }
        .mission-section-pill { width:46px; height:46px; display:grid; place-items:center; border-radius:16px; background:#0F2F57; color:#fff; font-weight:900; }
        .mission-main { min-width:0; display:flex; flex-direction:column; gap:5px; }
        .mission-main strong { color:#09254A; font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .mission-main span { color:#6A7C93; font-size:13px; font-weight:800; }
        .mission-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .mission-stats div { padding:10px 11px; border-radius:15px; background:#E8F0F8; }
        .mission-stats span { display:block; color:#1D5FD6; font-size:11px; font-weight:900; text-transform:uppercase; letter-spacing:.12em; }
        .mission-stats strong { display:block; margin-top:4px; color:#0A2342; font-size:18px; line-height:1; }
        .mission-actions { display:flex; align-items:center; justify-content:flex-end; gap:7px; flex-wrap:wrap; }
        .mission-btn { display:inline-flex; align-items:center; justify-content:center; min-height:36px; padding:8px 11px; border:0; border-radius:12px; text-decoration:none; font-size:12px; font-weight:900; cursor:pointer; }
        .mission-btn.edit { background:#fff7ed; color:#c2410c; }
        .mission-btn.questions { background:#E8F0F8; color:#1D5FD6; }
        .mission-btn.danger { background:#fee2e2; color:#b91c1c; }
        .missions-empty-simple { padding:24px; border:1px dashed #9CB8D8; border-radius:20px; background:#E8F0F8; color:#6A7C93; text-align:center; font-weight:700; }
        .missions-pagination { margin-top:18px; }
        .missions-page-info { margin-bottom:12px; color:#6A7C93; font-size:14px; font-weight:700; }
        .missions-pagination-nav { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .missions-page-numbers { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .missions-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:42px; padding:9px 13px; border-radius:13px; border:1px solid #B7CCE6; background:#E8F0F8; color:#1D5FD6; font-weight:800; text-decoration:none; }
        .missions-page-link.is-active { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); border-color:transparent; color:#fff; }
        .missions-page-link.is-disabled { pointer-events:none; opacity:.45; }
        .missions-page-arrow { min-width:104px; }
        @media (max-width:1050px) { .mission-row { grid-template-columns:52px minmax(210px,1fr) auto; } .mission-stats { grid-column:2 / -1; } }
        @media (max-width:760px) { .missions-hero-simple,.missions-card-head-simple { flex-direction:column; align-items:stretch; } .missions-hero-actions,.missions-filter-form select,.missions-primary-btn { width:100%; } .missions-hero-simple h1 { font-size:29px; } .mission-row { grid-template-columns:46px 1fr; } .mission-stats,.mission-actions { grid-column:1 / -1; justify-content:flex-start; } .mission-stats { grid-template-columns:repeat(3,1fr); } .missions-pagination-nav { flex-direction:column; align-items:stretch; } .missions-page-numbers { justify-content:center; } .missions-page-arrow { width:100%; } }
        @media (max-width:480px) { .mission-stats { grid-template-columns:1fr; } }
    </style>
@endsection
