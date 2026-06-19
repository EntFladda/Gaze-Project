@extends('admin.layouts.app')

@section('content')
    <div class="admin-users-page">
        <header class="admin-users-header">
            <div>
                <p class="admin-users-kicker">Akun</p>
                <h1 class="admin-users-title">Daftar Pengguna</h1>
            </div>
            <div class="admin-users-actions">
                <label class="admin-users-search-wrap">
                    <span aria-hidden="true" class="admin-users-search-icon">Cari</span>
                    <input type="search" id="search" placeholder="Cari pengguna..." class="admin-users-search"
                        oninput="filterUsers()" autocomplete="off">
                </label>
                <a href="{{ route('admin.users.create') }}" class="admin-users-primary">+ Tambah Pengguna</a>
            </div>
        </header>

        @if (session('success'))
            <div class="admin-users-alert">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-users-alert danger">{{ session('error') }}</div>
        @endif

        <section class="admin-users-stats" aria-label="Ringkasan pengguna">
            <article class="admin-users-stat admin-surface">
                <p>Total Pengguna</p>
                <strong>{{ number_format($userStats['total']) }}</strong>
            </article>
            <article class="admin-users-stat admin-surface">
                <p>Total Dosen</p>
                <strong>{{ number_format($userStats['lecturers']) }}</strong>
            </article>
            <article class="admin-users-stat admin-surface">
                <p>Total Mahasiswa</p>
                <strong>{{ number_format($userStats['students']) }}</strong>
            </article>
        </section>

        <section class="admin-card-white admin-users-card">
            <div class="admin-users-head">
                <div>
                    <p class="admin-users-head-kicker">Daftar Pengguna</p>
                    <h2 class="admin-users-head-title">Data pengguna</h2>
                </div>
                <div class="admin-users-toolbar">
                    <div class="admin-users-role-filter" aria-label="Filter peran pengguna">
                        <a href="{{ route('admin.users.index', array_filter(['perPage' => $perPage == 10 ? null : $perPage])) }}"
                            class="{{ $roleFilter === 'all' ? 'active' : '' }}">Semua</a>
                        <a href="{{ route('admin.users.index', ['role' => 'admin', 'perPage' => $perPage]) }}"
                            class="{{ $roleFilter === 'admin' ? 'active' : '' }}">Admin</a>
                        <a href="{{ route('admin.users.index', ['role' => 'lecturer', 'perPage' => $perPage]) }}"
                            class="{{ $roleFilter === 'lecturer' ? 'active' : '' }}">Dosen</a>
                        <a href="{{ route('admin.users.index', ['role' => 'student', 'perPage' => $perPage]) }}"
                            class="{{ $roleFilter === 'student' ? 'active' : '' }}">Mahasiswa</a>
                    </div>
                    <form method="GET" action="{{ route('admin.users.index') }}" class="admin-users-perpage">
                        <input type="hidden" name="role" value="{{ $roleFilter }}">
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
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th class="admin-users-number">No</th>
                            <th>Nama</th>
                            <th>Email / NIM</th>
                            <th>Peran</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="user-table">
                        @forelse ($users as $user)
                            @php
                                $roleName = $user->roles->first()?->name ?? 'user';
                                $roleLabel = match ($roleName) {
                                    'admin' => 'Admin',
                                    'lecturer' => 'Dosen',
                                    'student' => 'Mahasiswa',
                                    default => ucfirst($roleName),
                                };
                            @endphp
                            <tr>
                                <td class="admin-users-number">{{ $users->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="admin-users-name">
                                        <strong>{{ $user->name }}</strong>
                                        @if ($user->student?->class)
                                            <span>Kelas {{ $user->student->class }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="admin-users-name">
                                        <strong>{{ $user->email }}</strong>
                                        @if ($user->student?->nim)
                                            <span>NIM {{ $user->student->nim }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="admin-users-pill role-{{ $roleName }}">{{ $roleLabel }}</span></td>
                                <td>
                                    <div class="admin-users-row-actions">
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="row-btn detail">Detail</a>
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="row-btn edit">Edit</a>
                                        @if ($roleName === 'admin' && $adminCount <= 1)
                                            <button type="button" class="row-btn locked" title="Admin utama tidak bisa dihapus">Terkunci</button>
                                        @else
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus pengguna ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="row-btn danger">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noDataRow">
                                <td colspan="5"><div class="admin-users-empty">Belum ada data pengguna.</div></td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" style="display:none;">
                            <td colspan="5"><div class="admin-users-empty">Tidak ada pengguna yang cocok dengan pencarian.</div></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-users-pagination">
                <p class="admin-users-page-info">Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</p>
                @if ($users->hasPages())
                    <nav class="admin-users-pagination-nav" aria-label="Navigasi halaman pengguna">
                        <a href="{{ $users->onFirstPage() ? '#' : $users->previousPageUrl() }}"
                            class="admin-users-page-link admin-users-page-arrow {{ $users->onFirstPage() ? 'is-disabled' : '' }}"
                            @if ($users->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>
                        <div class="admin-users-page-numbers">
                            @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                                <a href="{{ $url }}" class="admin-users-page-link {{ $page === $users->currentPage() ? 'is-active' : '' }}">{{ $page }}</a>
                            @endforeach
                        </div>
                        <a href="{{ $users->hasMorePages() ? $users->nextPageUrl() : '#' }}"
                            class="admin-users-page-link admin-users-page-arrow {{ $users->hasMorePages() ? '' : 'is-disabled' }}"
                            @if (! $users->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>Berikutnya</a>
                    </nav>
                @endif
            </div>
        </section>
    </div>

    <style>
        .admin-users-page { max-width: 1220px; margin: 0 auto; }
        .admin-users-header { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:22px; }
        .admin-users-kicker { margin:0 0 7px; font-size:11px; letter-spacing:.26em; text-transform:uppercase; color:rgba(220, 231, 243,.68); }
        .admin-users-title { margin:0; font-size:34px; line-height:1.2; color:#fff; }
        .admin-users-actions { display:flex; gap:12px; align-items:center; }
        .admin-users-search-wrap { display:flex; align-items:center; gap:8px; min-width:240px; padding:0 14px; border-radius:14px; border:1px solid rgba(191,219,254,.18); background:rgba(255,255,255,.08); color:rgba(220, 231, 243,.72); }
        .admin-users-search-icon { font-size:11px; font-weight:900; letter-spacing:.12em; text-transform:uppercase; color:rgba(220, 231, 243,.62); }
        .admin-users-search { width:100%; padding:12px 0; border:0; outline:0; box-shadow:none; background:transparent; color:#fff; appearance:none; -webkit-appearance:none; }
        .admin-users-search:focus { outline:0; box-shadow:none; }
        .admin-users-search::placeholder { color:rgba(220, 231, 243,.58); }
        .admin-users-primary { display:inline-flex; align-items:center; justify-content:center; padding:12px 17px; border-radius:14px; background:var(--admin-accent); color:#fff; text-decoration:none; font-weight:800; white-space:nowrap; }
        .admin-users-alert { margin-bottom:16px; padding:14px 18px; border-radius:18px; background:rgba(220,252,231,.95); color:#166534; font-weight:700; }
        .admin-users-alert.danger { background:rgba(254,226,226,.96); color:#991b1b; }
        .admin-users-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:20px; }
        .admin-users-stat { min-height:126px; padding:22px 24px; border-radius:20px; }
        .admin-users-stat p { margin:0; color:rgba(220, 231, 243,.68); font-size:12px; font-weight:900; letter-spacing:.16em; text-transform:uppercase; }
        .admin-users-stat strong { display:block; margin-top:12px; color:#fff; font-size:36px; line-height:1; }
        .admin-users-card { padding:22px; border-radius:22px; }
        .admin-users-head { display:flex; justify-content:space-between; align-items:center; gap:18px; margin-bottom:16px; }
        .admin-users-head-kicker { margin:0; font-size:11px; letter-spacing:.2em; text-transform:uppercase; color:#1D5FD6; font-weight:900; }
        .admin-users-head-title { margin:5px 0 0; font-size:24px; color:#09254A; }
        .admin-users-toolbar { display:flex; align-items:center; justify-content:flex-end; gap:12px; flex-wrap:wrap; }
        .admin-users-role-filter { display:flex; gap:6px; padding:5px; border-radius:14px; background:#E8F0F8; }
        .admin-users-role-filter a { padding:8px 11px; border-radius:11px; text-decoration:none; color:#1D5FD6; font-size:12px; font-weight:900; }
        .admin-users-role-filter a.active { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); color:#fff; box-shadow:0 10px 20px rgba(37,99,235,.18); }
        .admin-users-perpage { display:flex; align-items:center; gap:10px; color:#6A7C93; font-size:13px; font-weight:700; }
        .admin-users-perpage select { padding:8px 10px; border-radius:10px; border:1px solid #B7CCE6; color:#09254A; }
        .admin-users-table { width:100%; border-collapse:collapse; }
        .admin-users-table thead th { padding:13px 14px; text-align:left; background:#0A2342; color:#fff; font-size:11px; letter-spacing:.14em; text-transform:uppercase; }
        .admin-users-table tbody td { padding:14px; border-bottom:1px solid #DCE7F3; color:#263E5C; vertical-align:middle; }
        .admin-users-table tbody tr:hover { background:#E8F0F8; }
        .admin-users-number { width:64px; }
        .admin-users-name { display:flex; flex-direction:column; gap:5px; }
        .admin-users-name strong { color:#09254A; }
        .admin-users-name span { color:#94a3b8; font-size:12px; }
        .admin-users-pill { display:inline-flex; padding:7px 11px; border-radius:999px; font-size:12px; font-weight:900; }
        .admin-users-pill.role-admin { background:#e2e8f0; color:#53657A; }
        .admin-users-pill.role-lecturer { background:#DCE7F3; color:#15509A; }
        .admin-users-pill.role-student { background:#D9EEF7; color:#0369a1; }
        .admin-users-row-actions { display:flex; justify-content:center; gap:7px; flex-wrap:wrap; }
        .row-btn { display:inline-flex; align-items:center; justify-content:center; padding:8px 11px; border-radius:10px; border:0; text-decoration:none; font-size:12px; font-weight:900; cursor:pointer; }
        .row-btn.detail { background:#E8F0F8; color:#1D5FD6; }
        .row-btn.edit { background:#E8F0F8; color:#1D5FD6; }
        .row-btn.danger { background:#fee2e2; color:#b91c1c; }
        .row-btn.locked { background:#e2e8f0; color:#6A7C93; cursor:not-allowed; }
        .admin-users-empty { padding:24px; text-align:center; color:#6A7C93; }
        .admin-users-pagination { margin-top:18px; }
        .admin-users-page-info { margin-bottom:12px; color:#6A7C93; font-size:14px; font-weight:600; }
        .admin-users-pagination-nav { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .admin-users-page-numbers { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .admin-users-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:44px; padding:10px 14px; border-radius:14px; border:1px solid #B7CCE6; background:#E8F0F8; color:#1D5FD6; font-weight:700; text-decoration:none; transition:.2s ease; }
        .admin-users-page-link:hover { background:#DCE7F3; border-color:#2BA7D8; }
        .admin-users-page-link.is-active { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); border-color:transparent; color:#fff; box-shadow:0 12px 24px rgba(37,99,235,.22); }
        .admin-users-page-link.is-disabled { pointer-events:none; opacity:.45; }
        .admin-users-page-arrow { min-width:112px; }
        @media (max-width:980px) { .admin-users-header { align-items:flex-start; } .admin-users-actions { flex-wrap:wrap; justify-content:flex-end; } .admin-users-stats { grid-template-columns:1fr; } .admin-users-stat { min-height:auto; } }
        @media (max-width:768px) { .admin-users-header,.admin-users-head { flex-direction:column; align-items:stretch; } .admin-users-title { font-size:29px; } .admin-users-actions,.admin-users-search-wrap { width:100%; } .admin-users-primary { flex:1; } .admin-users-toolbar { justify-content:flex-start; } .admin-users-role-filter { overflow-x:auto; } .admin-users-pagination-nav { flex-direction:column; align-items:stretch; } .admin-users-page-numbers { justify-content:center; } .admin-users-page-arrow { width:100%; } }
    </style>

    <script>
        function filterUsers() {
            const input = document.getElementById('search').value.toLowerCase();
            const rows = document.querySelectorAll('#user-table tr');
            const noResultsRow = document.getElementById('noResultsRow');
            let anyVisible = false;
            rows.forEach(row => {
                if (row.id === 'noResultsRow' || row.id === 'noDataRow') return;
                const text = row.textContent.toLowerCase();
                const isMatch = text.includes(input);
                row.style.display = isMatch ? '' : 'none';
                if (isMatch) anyVisible = true;
            });
            if (noResultsRow) noResultsRow.style.display = anyVisible ? 'none' : '';
        }
    </script>
@endsection
