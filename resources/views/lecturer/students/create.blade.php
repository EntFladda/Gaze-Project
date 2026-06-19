@extends('lecturer.layouts.app')

@section('content')
    <div class="student-form-page">
        <section class="student-form-hero">
            <p class="student-form-kicker">Tambah Mahasiswa</p>
            <h1 class="student-form-title">Masukkan data mahasiswa baru</h1>
            <p class="student-form-copy">Akun mahasiswa dibuat otomatis. Password awal memakai NIM agar mudah dibagikan saat kelas dimulai.</p>
        </section>

        @if ($errors->any())
            <div class="student-form-alert">
                <strong>Data belum bisa disimpan.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lecturer.students.store') }}" method="POST" class="student-form-card bg-white">
            @csrf

            <div class="student-form-grid two">
                <div>
                    <label for="name" class="student-form-label">Nama</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="student-form-input" required>
                </div>
                <div>
                    <label for="email" class="student-form-label">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="student-form-input" required>
                </div>
            </div>

            <div class="student-form-grid three">
                <div>
                    <label for="nim" class="student-form-label">NIM</label>
                    <input type="text" name="nim" id="nim" value="{{ old('nim') }}" class="student-form-input" required>
                </div>
                <div>
                    <label for="prodi" class="student-form-label">Program Studi</label>
                    <input type="text" name="prodi" id="prodi" value="{{ old('prodi') }}" class="student-form-input">
                </div>
                <div>
                    <label for="class" class="student-form-label">Kelas</label>
                    <input type="text" name="class" id="class" value="{{ old('class') }}" class="student-form-input">
                </div>
            </div>

            <div class="student-form-grid two">
                <div>
                    <label for="semester" class="student-form-label">Semester</label>
                    <input type="number" name="semester" id="semester" value="{{ old('semester') }}" class="student-form-input">
                </div>
                <div>
                    <label for="exp" class="student-form-label">EXP Awal</label>
                    <input type="number" name="exp" id="exp" value="{{ old('exp', 0) }}" class="student-form-input" required>
                </div>
            </div>

            <div class="student-form-note">
                Password awal mahasiswa otomatis mengikuti <strong>NIM</strong>. Mahasiswa dapat menggantinya setelah login.
            </div>

            <div class="student-form-actions">
                <a href="{{ route('lecturer.students.index') }}" class="student-form-btn neutral">Kembali</a>
                <button type="submit" class="student-form-btn primary">Simpan Mahasiswa</button>
            </div>
        </form>
    </div>

    <style>
        .student-form-page { max-width: 1000px; margin: 0 auto; }
        .student-form-hero { margin-bottom: 24px; padding: 28px; border-radius: 30px; border: 1px solid rgba(191,219,254,.14); background: rgba(10,35,66,.78); box-shadow: 0 20px 50px rgba(0,0,0,.22); }
        .student-form-kicker { margin: 0; font-size: 12px; letter-spacing: .34em; text-transform: uppercase; color: rgba(191,219,254,.75); }
        .student-form-title { margin: 12px 0 0; color: #fff; font-size: 40px; font-weight: 700; }
        .student-form-copy { margin: 14px 0 0; color: rgba(219,234,254,.76); line-height: 1.8; max-width: 760px; }
        .student-form-alert { margin-bottom: 16px; padding: 16px 18px; border-radius: 18px; background: rgba(254,226,226,.96); color: #991b1b; }
        .student-form-alert ul { margin: 10px 0 0 18px; }
        .student-form-card { padding: 24px; border-radius: 30px; }
        .student-form-grid { display: grid; gap: 20px; margin-bottom: 20px; }
        .student-form-grid.two { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .student-form-grid.three { grid-template-columns: repeat(3, minmax(0,1fr)); }
        .student-form-label { display:block; margin-bottom:10px; color:#263E5C; font-weight:700; }
        .student-form-input { width:100%; padding:14px 16px; border-radius:16px; border:1px solid #9CB8D8; box-sizing:border-box; color:#09254A !important; background:#fff !important; }
        .student-form-note { padding: 16px 18px; border-radius: 18px; background: #fff7ed; color: #9a3412; line-height: 1.7; }
        .student-form-actions { display:flex; justify-content:space-between; gap:14px; margin-top:24px; }
        .student-form-btn { display:inline-flex; align-items:center; justify-content:center; padding:14px 18px; border-radius:16px; text-decoration:none; font-weight:700; border:0; cursor:pointer; color:#fff; }
        .student-form-btn.neutral { background:#6A7C93; }
        .student-form-btn.primary { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); }
        @media (max-width:768px) {
            .student-form-grid.two, .student-form-grid.three { grid-template-columns:1fr; }
            .student-form-title { font-size:32px; }
            .student-form-actions { flex-direction:column; }
        }
    </style>
@endsection
