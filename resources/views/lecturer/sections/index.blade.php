@extends('lecturer.layouts.app')

@section('content')
    <div class="sections-page-simple">
        <section class="sections-hero-simple">
            <div>
                <p class="sections-kicker">Bagian Belajar</p>
                <h1>Susunan Materi</h1>
                <span>Atur urutan bagian belajar yang akan dilalui mahasiswa.</span>
            </div>

            <a href="{{ route('lecturer.sections.create') }}" class="sections-primary-btn">Tambah Bagian</a>
        </section>

        @if (session('success'))
            <div class="sections-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="sections-alert error">{{ session('error') }}</div>
        @endif

        <section class="sections-card-simple bg-white">
            <div class="sections-card-head-simple">
                <div>
                    <p>Daftar Bagian</p>
                    <h2>Alur Pembelajaran</h2>
                </div>
                <span id="sectionsReorderStatus">Geser baris untuk mengubah urutan.</span>
            </div>

            <div class="sections-list" id="sortable-sections">
                @forelse ($sections as $section)
                    <article class="section-row" data-id="{{ $section->id }}" draggable="true">
                        <div class="section-drag" title="Geser urutan" aria-label="Geser urutan">&#8942;</div>

                        <div class="section-order order-number">{{ $section->order }}</div>

                        <div class="section-main">
                            <strong>{{ $section->name }}</strong>
                            <span>{{ $section->challenges_count }} mission</span>
                        </div>

                        <div class="section-missions">
                            @if ($section->challenges->isNotEmpty())
                                @foreach ($section->challenges->take(3) as $challenge)
                                    <span>{{ $challenge->title }}</span>
                                @endforeach

                                @if ($section->challenges->count() > 3)
                                    <span class="more">+{{ $section->challenges->count() - 3 }}</span>
                                @endif
                            @else
                                <em>Belum ada mission</em>
                            @endif
                        </div>

                        <div class="section-actions">
                            <a href="{{ route('lecturer.sections.edit', $section->id) }}" class="section-btn edit">Edit</a>
                            <a href="{{ route('lecturer.challenges.index', ['section_id' => $section->id]) }}" class="section-btn view">Mission</a>
                            <form action="{{ route('lecturer.sections.destroy', $section->id) }}" method="POST"
                                onsubmit="return confirm('Hapus bagian ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="section-btn danger">Hapus</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="sections-empty-simple">
                        Belum ada bagian belajar. Tambahkan bagian pertama untuk mulai menyusun alur materi.
                    </div>
                @endforelse
            </div>

            <div class="sections-pagination">
                <p class="sections-page-info">Halaman {{ $sections->currentPage() }} dari {{ $sections->lastPage() }}</p>

                @if ($sections->hasPages())
                    <nav class="sections-pagination-nav" aria-label="Navigasi halaman bagian belajar">
                        <a href="{{ $sections->onFirstPage() ? '#' : $sections->previousPageUrl() }}"
                            class="sections-page-link sections-page-arrow {{ $sections->onFirstPage() ? 'is-disabled' : '' }}"
                            @if ($sections->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>

                        <div class="sections-page-numbers">
                            @foreach ($sections->getUrlRange(1, $sections->lastPage()) as $page => $url)
                                <a href="{{ $url }}" class="sections-page-link {{ $page === $sections->currentPage() ? 'is-active' : '' }}"
                                    aria-current="{{ $page === $sections->currentPage() ? 'page' : 'false' }}">{{ $page }}</a>
                            @endforeach
                        </div>

                        <a href="{{ $sections->hasMorePages() ? $sections->nextPageUrl() : '#' }}"
                            class="sections-page-link sections-page-arrow {{ $sections->hasMorePages() ? '' : 'is-disabled' }}"
                            @if (! $sections->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>Berikutnya</a>
                    </nav>
                @endif
            </div>
        </section>
    </div>

    <style>
        .sections-page-simple { max-width:1180px; margin:0 auto; color:#0A2342; }
        .sections-hero-simple { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:22px 24px; border-radius:26px; border:1px solid rgba(191,219,254,.14); background:linear-gradient(135deg,#0A2342,#0F2F57); box-shadow:0 16px 34px rgba(15,23,42,.18); margin-bottom:18px; }
        .sections-kicker, .sections-card-head-simple p { margin:0; font-size:11px; font-weight:900; letter-spacing:.18em; text-transform:uppercase; }
        .sections-kicker { color:rgba(191,219,254,.76); }
        .sections-hero-simple h1 { margin:8px 0 0; color:#fff; font-size:34px; line-height:1.1; font-weight:900; }
        .sections-hero-simple span { display:block; margin-top:8px; color:rgba(219,234,254,.75); line-height:1.55; }
        .sections-primary-btn { display:inline-flex; align-items:center; justify-content:center; padding:12px 16px; border-radius:15px; background:#fff; color:#0A2342; font-size:14px; font-weight:900; text-decoration:none; white-space:nowrap; }
        .sections-alert { margin-bottom:14px; padding:13px 16px; border-radius:16px; font-weight:800; }
        .sections-alert.success { background:rgba(220,252,231,.96); color:#166534; }
        .sections-alert.error { background:rgba(254,226,226,.96); color:#991b1b; }
        .sections-card-simple { padding:20px; border-radius:24px; border:1px solid #B7CCE6; box-shadow:0 12px 28px rgba(15,23,42,.08); }
        .sections-card-head-simple { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:16px; }
        .sections-card-head-simple p { color:#1D5FD6; }
        .sections-card-head-simple h2 { margin:6px 0 0; color:#0A2342; font-size:24px; font-weight:900; }
        .sections-card-head-simple > span { color:#6A7C93; font-size:13px; font-weight:700; }
        .sections-list { display:flex; flex-direction:column; gap:10px; }
        .section-row { display:grid; grid-template-columns:42px 54px minmax(180px,1fr) minmax(240px,1.1fr) auto; align-items:center; gap:12px; padding:14px; border:1px solid #B7CCE6; border-radius:20px; background:#F4F8FC; transition:.16s ease; }
        .section-row:hover { background:#E8F0F8; }
        .section-row.is-dragging { opacity:.62; transform:scale(.995); }
        .section-drag { width:40px; height:40px; display:grid; place-items:center; border-radius:14px; background:#E8F0F8; color:#1D5FD6; font-weight:900; cursor:grab; }
        .section-order { width:44px; height:44px; display:grid; place-items:center; border-radius:16px; background:#0F2F57; color:#fff; font-weight:900; }
        .section-main { display:flex; flex-direction:column; gap:5px; min-width:0; }
        .section-main strong { color:#09254A; font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .section-main span { color:#1D5FD6; font-size:13px; font-weight:800; }
        .section-missions { display:flex; flex-wrap:wrap; gap:7px; min-width:0; }
        .section-missions span { max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; padding:7px 10px; border-radius:999px; background:#DCE7F3; color:#15509A; font-size:12px; font-weight:800; }
        .section-missions span.more { background:#f8fafc; color:#6A7C93; }
        .section-missions em { color:#94a3b8; font-size:13px; }
        .section-actions { display:flex; align-items:center; justify-content:flex-end; gap:7px; flex-wrap:wrap; }
        .section-btn { display:inline-flex; align-items:center; justify-content:center; min-height:36px; padding:8px 11px; border:0; border-radius:12px; text-decoration:none; font-size:12px; font-weight:900; cursor:pointer; }
        .section-btn.edit { background:#fff7ed; color:#c2410c; }
        .section-btn.view { background:#E8F0F8; color:#1D5FD6; }
        .section-btn.danger { background:#fee2e2; color:#b91c1c; }
        .sections-empty-simple { padding:24px; border:1px dashed #9CB8D8; border-radius:20px; background:#E8F0F8; color:#6A7C93; text-align:center; font-weight:700; }
        .sections-pagination { margin-top:18px; }
        .sections-page-info { margin-bottom:12px; color:#6A7C93; font-size:14px; font-weight:700; }
        .sections-pagination-nav { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .sections-page-numbers { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .sections-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:42px; padding:9px 13px; border-radius:13px; border:1px solid #B7CCE6; background:#E8F0F8; color:#1D5FD6; font-weight:800; text-decoration:none; }
        .sections-page-link.is-active { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); border-color:transparent; color:#fff; }
        .sections-page-link.is-disabled { pointer-events:none; opacity:.45; }
        .sections-page-arrow { min-width:104px; }
        @media (max-width:1050px) { .section-row { grid-template-columns:42px 54px minmax(180px,1fr) auto; } .section-missions { grid-column:3 / -1; } }
        @media (max-width:760px) { .sections-hero-simple,.sections-card-head-simple { flex-direction:column; align-items:stretch; } .sections-primary-btn { width:100%; } .sections-hero-simple h1 { font-size:29px; } .section-row { grid-template-columns:38px 46px 1fr; } .section-actions,.section-missions { grid-column:1 / -1; justify-content:flex-start; } .sections-pagination-nav { flex-direction:column; align-items:stretch; } .sections-page-numbers { justify-content:center; } .sections-page-arrow { width:100%; } }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.getElementById('sortable-sections');
            const status = document.getElementById('sectionsReorderStatus');
            if (!list) return;

            let draggedRow = null;

            function rows() {
                return Array.from(list.querySelectorAll('.section-row'));
            }

            function updateNumbers() {
                rows().forEach((row, index) => {
                    const number = row.querySelector('.order-number');
                    if (number) number.textContent = index + 1;
                });
            }

            async function saveOrder() {
                const orderedIds = rows().map(row => row.dataset.id);
                if (!orderedIds.length) return;

                if (status) status.textContent = 'Menyimpan urutan...';

                try {
                    const response = await fetch("{{ route('lecturer.sections.reorder') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ orderedIds })
                    });

                    if (!response.ok) throw new Error('Gagal menyimpan urutan.');
                    if (status) status.textContent = 'Urutan tersimpan.';
                } catch (error) {
                    if (status) status.textContent = 'Urutan belum tersimpan. Coba lagi.';
                }
            }

            rows().forEach(row => {
                row.addEventListener('dragstart', () => {
                    draggedRow = row;
                    row.classList.add('is-dragging');
                });

                row.addEventListener('dragend', () => {
                    row.classList.remove('is-dragging');
                    draggedRow = null;
                    updateNumbers();
                    saveOrder();
                });

                row.addEventListener('dragover', event => {
                    event.preventDefault();
                    if (!draggedRow || draggedRow === row) return;

                    const box = row.getBoundingClientRect();
                    const placeAfter = event.clientY > box.top + box.height / 2;
                    list.insertBefore(draggedRow, placeAfter ? row.nextSibling : row);
                });
            });
        });
    </script>
@endsection
