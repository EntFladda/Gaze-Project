@extends('admin.layouts.app')

@section('content')
    <div class="admin-detail-page">
        <section class="admin-detail-hero admin-surface">
            <div class="admin-detail-profile">
    <img src="{{ $student->user->profile_photo ? asset('storage/' . $student->user->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}" alt="{{ $student->user->name }}">
                <div>
                    <p class="admin-detail-kicker">Students</p>
                    <h1 class="admin-detail-title">{{ $student->user->name }}</h1>
                    <p class="admin-detail-copy">{{ $student->nim }} | {{ $student->prodi ?? 'Program studi belum diisi' }}</p>
                </div>
            </div>
            <div class="admin-detail-actions">
                <a href="{{ route('admin.students.edit', $student->id) }}" class="btn-primary">Edit Mahasiswa</a>
                <a href="{{ route('admin.students.index') }}" class="btn-neutral">Kembali</a>
            </div>
        </section>

        <div class="admin-detail-grid">
            <section class="admin-card-white admin-detail-card">
                <h2>Akademik</h2>
                <div class="admin-detail-info">
                    <div><span>NIM</span><strong>{{ $student->nim }}</strong></div>
                    <div><span>Email</span><strong>{{ $student->user->email }}</strong></div>
                    <div><span>Program Studi</span><strong>{{ $student->prodi ?? '-' }}</strong></div>
                    <div><span>Kelas</span><strong>{{ $student->class ?? '-' }}</strong></div>
                    <div><span>Semester</span><strong>{{ $student->semester ?? '-' }}</strong></div>
                    <div><span>Agama</span><strong>{{ $student->religion ?? '-' }}</strong></div>
                    <div><span>Jenis Kelamin</span><strong>{{ $student->gender ?? '-' }}</strong></div>
                    <div><span>No. Telepon</span><strong>{{ $student->phone_number ?? '-' }}</strong></div>
                    <div><span>Alamat</span><strong>{{ $student->address ?? '-' }}</strong></div>
                    <div><span>Tanggal Lahir</span><strong>{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->translatedFormat('d F Y') : '-' }}</strong></div>
                </div>
            </section>

            <section class="admin-card-white admin-detail-card">
                <h2>Progress</h2>
                <div class="admin-detail-info">
                    <div><span>Rank</span><strong>{{ $student->current_rank?->name ?? 'Belum ada rank' }}</strong></div>
                    <div><span>EXP</span><strong>{{ $student->exp }}</strong></div>
                    <div><span>Streak</span><strong>{{ $student->streak }} hari</strong></div>
                    <div><span>Poin Mingguan</span><strong>{{ $student->weekly_score }}</strong></div>
                    <div><span>Total Poin</span><strong>{{ $student->total_score }}</strong></div>
                    <div><span>Challenge Aktif</span><strong>{{ $student->currentChallenge?->title ?? 'Tidak ada' }}</strong></div>
                    <div><span>Section Aktif</span><strong>{{ $student->currentSection?->name ?? 'Tidak ada' }}</strong></div>
                </div>
            </section>
        </div>
    </div>

    <style>
        .admin-detail-page { max-width: 1200px; margin: 0 auto; }
        .admin-detail-hero { display:flex; justify-content:space-between; align-items:center; gap:20px; padding:28px; border-radius:30px; margin-bottom:24px; }
        .admin-detail-profile { display:flex; align-items:center; gap:18px; }
        .admin-detail-profile img { width:88px; height:88px; border-radius:999px; object-fit:cover; background:#fff; }
        .admin-detail-kicker { margin:0; font-size:12px; letter-spacing:.32em; text-transform:uppercase; color:rgba(220, 231, 243,.72); }
        .admin-detail-title { margin:10px 0 0; color:#fff; font-size:40px; }
        .admin-detail-copy { margin:10px 0 0; color:rgba(220, 231, 243,.75); }
        .admin-detail-actions { display:flex; gap:12px; flex-wrap:wrap; }
        .admin-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:24px; }
        .admin-detail-card { padding:24px; border-radius:30px; }
        .admin-detail-card h2 { margin:0 0 18px; color:#09254A; font-size:28px; }
        .admin-detail-info { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .admin-detail-info div { padding:16px; border-radius:18px; background:#E8F0F8; }
        .admin-detail-info span { display:block; margin-bottom:8px; font-size:12px; letter-spacing:.12em; text-transform:uppercase; color:#94a3b8; }
        .admin-detail-info strong { color:#09254A; }
        .btn-primary, .btn-neutral { display:inline-flex; align-items:center; justify-content:center; padding:14px 18px; border-radius:16px; text-decoration:none; font-weight:700; border:0; color:#fff; }
        .btn-primary { background:var(--admin-accent); }
        .btn-neutral { background:#6A7C93; }
        @media (max-width:768px) { .admin-detail-hero { flex-direction:column; align-items:stretch; } .admin-detail-grid, .admin-detail-info { grid-template-columns:1fr; } .admin-detail-title { font-size:32px; } }
    </style>
@endsection
