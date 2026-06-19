@extends('admin.layouts.app')

@section('content')
    <div class="admin-students-page">
        <header class="admin-students-header">
            <div>
                <p class="admin-students-kicker">Mahasiswa</p>
                <h1 class="admin-students-title">Daftar Mahasiswa</h1>
            </div>
            <div class="admin-students-actions">
                <label class="admin-students-search-wrap">
                    <span class="admin-students-search-icon">Cari</span>
                    <input type="search" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Cari mahasiswa..." class="admin-students-search" oninput="filterStudents()" autocomplete="off">
                </label>
                <a href="{{ route('admin.students.create') }}" class="admin-students-primary">+ Tambah Mahasiswa</a>
            </div>
        </header>

        @if (session('success'))
            <div class="admin-students-alert">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-students-alert danger">{{ session('error') }}</div>
        @endif

        <section class="admin-students-stats" aria-label="Ringkasan mahasiswa">
            <article class="admin-students-stat admin-surface">
                <p>Total Mahasiswa</p>
                <strong>{{ number_format($studentStats['total']) }}</strong>
            </article>
            <article class="admin-students-stat admin-surface">
                <p>Total Kelas</p>
                <strong>{{ number_format($studentStats['classes']) }}</strong>
            </article>
            <article class="admin-students-stat admin-surface">
                <p>Semester Aktif</p>
                <strong>{{ number_format($studentStats['semesters']) }}</strong>
            </article>
        </section>

        <section class="admin-card-white admin-students-import">
            <div>
                <p class="admin-students-head-kicker">Tambah Banyak</p>
                <h2 class="admin-students-head-title">Impor Mahasiswa</h2>
                <p class="admin-students-import-copy">Pilih file CSV/Excel, lalu tekan impor. Password awal mahasiswa otomatis memakai NIM.</p>
            </div>
            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" class="admin-students-import-form">
                @csrf
                <input type="file" id="student_file" name="student_file" accept=".csv,.txt,.xlsx" required>
                <label for="student_file" class="admin-file-picker" title="Pilih data mahasiswa" aria-label="Pilih data mahasiswa">
                    <span class="admin-file-picker-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 15V4m0 0 4 4m-4-4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20 15v3.5A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>
                        <strong>Pilih file</strong>
                        <small id="studentFileName">CSV / Excel</small>
                    </span>
                </label>
                <button type="submit" class="admin-icon-action" title="Impor data" aria-label="Impor data">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h11m0 0-4-4m4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
                <a href="{{ asset('templates/import-mahasiswa-template.csv') }}" class="admin-icon-action" download
                    title="Unduh contoh data" aria-label="Unduh contoh data">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 4v10m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </a>
            </form>
        </section>

        <section class="admin-card-white admin-students-card">
            <div class="admin-students-head">
                <div>
                    <p class="admin-students-head-kicker">Daftar Mahasiswa</p>
                    <h2 class="admin-students-head-title">Data mahasiswa</h2>
                </div>
                <div class="admin-students-toolbar">
                    <a href="{{ route('admin.students.index', ['perPage' => $perPage]) }}" class="toolbar-btn reset">Reset</a>
                    <form method="GET" action="{{ route('admin.students.index') }}" class="admin-students-perpage">
                        <label for="perPage">Tampilkan</label>
                        <select name="perPage" id="perPage" onchange="this.form.submit()">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            <option value="all" {{ $perPage == 'all' ? 'selected' : '' }}>Semua</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-students-table">
                    <thead>
                        <tr>
                            <th class="admin-students-number">No</th>
                            <th>Nama</th>
                            <th>NIM</th>
                            <th>Kelas</th>
                            <th>Semester</th>
                            <th>Email / Telepon</th>
                            <th>Jenis Kelamin</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="students-table-body">
                        @forelse ($students as $student)
                            <tr class="student-row">
                                <td class="admin-students-number">{{ $pagination ? $students->firstItem() + $loop->index : $loop->iteration }}</td>
                                <td><strong>{{ $student->user->name }}</strong></td>
                                <td>{{ $student->nim }}</td>
                                <td>{{ $student->class ?? '-' }}</td>
                                <td>{{ $student->semester ?? '-' }}</td>
                                <td>
                                    <div class="admin-students-name">
                                        <strong>{{ $student->user->email }}</strong>
                                        <span>{{ $student->phone_number ?? '-' }}</span>
                                    </div>
                                </td>
                                <td><span class="admin-students-pill">{{ $student->gender ?? '-' }}</span></td>
                                <td>
                                    <div class="admin-students-row-actions">
                                        <a href="{{ route('admin.students.show', $student->id) }}" class="row-btn detail">Detail</a>
                                        <a href="{{ route('admin.students.edit', $student->id) }}" class="row-btn edit">Edit</a>
                                        <form action="{{ route('admin.students.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Hapus mahasiswa ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="row-btn danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noDataRow"><td colspan="8"><div class="admin-students-empty">Belum ada data mahasiswa.</div></td></tr>
                        @endforelse
                        <tr id="noResultsRow" style="display:none;"><td colspan="8"><div class="admin-students-empty">Tidak ada mahasiswa yang cocok dengan pencarian.</div></td></tr>
                    </tbody>
                </table>
            </div>

            @if ($pagination)
                <div class="admin-students-pagination">
                    <p class="admin-students-page-info">Halaman {{ $students->currentPage() }} dari {{ $students->lastPage() }}</p>
                    @if ($students->hasPages())
                        <nav class="admin-students-pagination-nav" aria-label="Navigasi halaman mahasiswa admin">
                            <a href="{{ $students->onFirstPage() ? '#' : $students->previousPageUrl() }}"
                                class="admin-students-page-link admin-students-page-arrow {{ $students->onFirstPage() ? 'is-disabled' : '' }}">Sebelumnya</a>
                            <div class="admin-students-page-numbers">
                                @foreach ($students->getUrlRange(1, $students->lastPage()) as $page => $url)
                                    <a href="{{ $url }}" class="admin-students-page-link {{ $page === $students->currentPage() ? 'is-active' : '' }}">{{ $page }}</a>
                                @endforeach
                            </div>
                            <a href="{{ $students->hasMorePages() ? $students->nextPageUrl() : '#' }}"
                                class="admin-students-page-link admin-students-page-arrow {{ $students->hasMorePages() ? '' : 'is-disabled' }}">Berikutnya</a>
                        </nav>
                    @endif
                </div>
            @endif
        </section>
    </div>

    <style>
        .admin-students-page { max-width:1220px; margin:0 auto; }
        .admin-students-header { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:22px; }
        .admin-students-kicker { margin:0 0 7px; font-size:11px; letter-spacing:.26em; text-transform:uppercase; color:rgba(220, 231, 243,.68); }
        .admin-students-title { margin:0; font-size:34px; line-height:1.2; color:#fff; }
        .admin-students-actions { display:flex; gap:12px; align-items:center; }
        .admin-students-search-wrap { display:flex; align-items:center; gap:8px; min-width:250px; padding:0 14px; border-radius:14px; border:1px solid rgba(191,219,254,.18); background:rgba(255,255,255,.08); color:rgba(220, 231, 243,.72); }
        .admin-students-search-icon { font-size:11px; font-weight:900; letter-spacing:.12em; text-transform:uppercase; color:rgba(220, 231, 243,.62); }
        .admin-students-search { width:100%; padding:12px 0; border:0; outline:0; box-shadow:none; background:transparent; color:#fff; appearance:none; -webkit-appearance:none; }
        .admin-students-search:focus { outline:0; box-shadow:none; }
        .admin-students-search::placeholder { color:rgba(220, 231, 243,.58); }
        .admin-students-primary { display:inline-flex; align-items:center; justify-content:center; padding:12px 17px; border-radius:14px; background:var(--admin-accent); color:#fff; text-decoration:none; font-weight:800; white-space:nowrap; }
        .admin-students-alert { margin-bottom:16px; padding:14px 18px; border-radius:18px; background:rgba(220,252,231,.95); color:#166534; font-weight:700; }
        .admin-students-alert.danger { background:rgba(254,226,226,.96); color:#991b1b; }
        .admin-students-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:20px; }
        .admin-students-stat { min-height:126px; padding:22px 24px; border-radius:20px; }
        .admin-students-stat p { margin:0; color:rgba(220, 231, 243,.68); font-size:12px; font-weight:900; letter-spacing:.16em; text-transform:uppercase; }
        .admin-students-stat strong { display:block; margin-top:12px; color:#fff; font-size:36px; line-height:1; }
        .admin-students-import { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:18px 22px; border-radius:22px; margin-bottom:20px; }
        .admin-students-import-copy { max-width:520px; margin:8px 0 0; color:#6A7C93; font-size:13px; line-height:1.55; }
        .admin-students-import-form { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .admin-students-import-form input[type="file"] { position:absolute; width:1px; height:1px; opacity:0; overflow:hidden; pointer-events:none; }
        .admin-file-picker { min-width:210px; height:56px; display:inline-flex; align-items:center; gap:12px; padding:0 15px; border-radius:17px; background:#E8F0F8; color:#1D5FD6; text-decoration:none; cursor:pointer; box-shadow:inset 0 0 0 1px #B7CCE6; transition:.18s ease; }
        .admin-file-picker:hover { transform:translateY(-1px); background:#DCE7F3; box-shadow:0 12px 24px rgba(37,99,235,.14), inset 0 0 0 1px #9CB8D8; }
        .admin-file-picker-icon { width:36px; height:36px; flex:0 0 36px; display:inline-flex; align-items:center; justify-content:center; border-radius:13px; background:#fff; color:#1D5FD6; }
        .admin-file-picker-icon svg, .admin-icon-action svg { width:22px; height:22px; display:block; }
        .admin-file-picker strong { display:block; color:#123A68; font-size:13px; line-height:1.1; }
        .admin-file-picker small { display:block; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#6A7C93; font-size:11px; margin-top:4px; }
        .admin-icon-action { width:56px; height:56px; display:inline-flex; align-items:center; justify-content:center; border:0; border-radius:17px; background:#E8F0F8; color:#1D5FD6; text-decoration:none; font-size:22px; font-weight:900; cursor:pointer; box-shadow:inset 0 0 0 1px #B7CCE6; transition:.18s ease; }
        .admin-icon-action:hover { transform:translateY(-1px); background:linear-gradient(90deg,#1D5FD6,#2BA7D8); color:#fff; box-shadow:0 12px 24px rgba(37,99,235,.2); }
        .admin-students-card { padding:22px; border-radius:22px; }
        .admin-students-head { display:flex; justify-content:space-between; align-items:center; gap:18px; margin-bottom:16px; }
        .admin-students-head-kicker { margin:0; font-size:11px; letter-spacing:.2em; text-transform:uppercase; color:#1D5FD6; font-weight:900; }
        .admin-students-head-title { margin:5px 0 0; font-size:24px; color:#09254A; }
        .admin-students-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .toolbar-btn.reset { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:11px; background:#6A7C93; color:#fff; text-decoration:none; font-size:12px; font-weight:900; }
        .admin-students-perpage { display:flex; align-items:center; gap:10px; color:#6A7C93; font-size:13px; font-weight:700; }
        .admin-students-perpage select { padding:8px 10px; border-radius:10px; border:1px solid #B7CCE6; color:#09254A; }
        .admin-students-table { width:100%; border-collapse:collapse; }
        .admin-students-table thead th { padding:13px 14px; text-align:left; background:#0A2342; color:#fff; font-size:11px; letter-spacing:.14em; text-transform:uppercase; }
        .admin-students-table tbody td { padding:14px; border-bottom:1px solid #DCE7F3; color:#263E5C; vertical-align:middle; }
        .admin-students-table tbody tr:hover { background:#E8F0F8; }
        .admin-students-number { width:64px; }
        .admin-students-name { display:flex; flex-direction:column; gap:5px; }
        .admin-students-name strong { color:#09254A; }
        .admin-students-name span { color:#94a3b8; font-size:12px; }
        .admin-students-pill { display:inline-flex; padding:7px 11px; border-radius:999px; background:#D9EEF7; color:#0369a1; font-size:12px; font-weight:900; }
        .admin-students-row-actions { display:flex; justify-content:center; gap:7px; flex-wrap:wrap; }
        .row-btn { display:inline-flex; align-items:center; justify-content:center; padding:8px 11px; border-radius:10px; border:0; text-decoration:none; font-size:12px; font-weight:900; cursor:pointer; }
        .row-btn.detail { background:#E8F0F8; color:#1D5FD6; }
        .row-btn.edit { background:#E8F0F8; color:#1D5FD6; }
        .row-btn.danger { background:#fee2e2; color:#b91c1c; }
        .admin-students-empty { padding:24px; text-align:center; color:#6A7C93; }
        .admin-students-pagination { margin-top:18px; }
        .admin-students-page-info { margin-bottom:12px; color:#6A7C93; font-size:14px; font-weight:600; }
        .admin-students-pagination-nav { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .admin-students-page-numbers { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .admin-students-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:44px; padding:10px 14px; border-radius:14px; border:1px solid #B7CCE6; background:#E8F0F8; color:#1D5FD6; font-weight:700; text-decoration:none; transition:.2s ease; }
        .admin-students-page-link:hover { background:#DCE7F3; border-color:#2BA7D8; }
        .admin-students-page-link.is-active { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); border-color:transparent; color:#fff; box-shadow:0 12px 24px rgba(37,99,235,.22); }
        .admin-students-page-link.is-disabled { pointer-events:none; opacity:.45; }
        .admin-students-page-arrow { min-width:112px; }
        @media (max-width:980px) { .admin-students-header,.admin-students-import { align-items:flex-start; } .admin-students-actions,.admin-students-import { flex-wrap:wrap; } .admin-students-stats { grid-template-columns:1fr; } .admin-students-stat { min-height:auto; } }
        @media (max-width:768px) { .admin-students-header,.admin-students-head { flex-direction:column; align-items:stretch; } .admin-students-title { font-size:29px; } .admin-students-actions,.admin-students-search-wrap,.admin-students-import-form input { width:100%; max-width:none; } .admin-students-primary { flex:1; } .admin-students-pagination-nav { flex-direction:column; align-items:stretch; } .admin-students-page-numbers { justify-content:center; } .admin-students-page-arrow { width:100%; } }
    </style>

    <script>
        function filterStudents() {
            const input = document.getElementById('search').value.toLowerCase();
            const rows = document.querySelectorAll('.student-row');
            const noResultsRow = document.getElementById('noResultsRow');
            let anyVisible = false;
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const isMatch = text.includes(input);
                row.style.display = isMatch ? '' : 'none';
                if (isMatch) anyVisible = true;
            });
            if (noResultsRow) noResultsRow.style.display = anyVisible ? 'none' : '';
        }

        const studentFileInput = document.getElementById('student_file');
        const studentFileName = document.getElementById('studentFileName');
        if (studentFileInput && studentFileName) {
            studentFileInput.addEventListener('change', () => {
                studentFileName.textContent = studentFileInput.files?.[0]?.name || 'CSV / Excel';
            });
        }
    </script>
@endsection
