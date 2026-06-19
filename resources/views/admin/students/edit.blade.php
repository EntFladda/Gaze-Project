@extends('admin.layouts.app')

@section('content')
    <div class="admin-form-page">
        <section class="admin-form-hero admin-surface">
            <p class="admin-form-kicker">Students</p>
            <h1 class="admin-form-title">Perbarui data mahasiswa</h1>
        </section>

        @if ($errors->any())
            <div class="admin-form-alert">
                <strong>Perubahan belum bisa disimpan.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.students.update', $student->id) }}" method="POST" enctype="multipart/form-data" class="admin-card-white admin-form-card">
            @csrf
            @method('PUT')

            <div class="admin-form-profile">
            <img src="{{ $student->user->profile_photo ? asset('storage/' . $student->user->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}" alt="{{ $student->user->name }}">
                <div class="admin-form-profile-meta">
                    <strong>{{ $student->user->name }}</strong>
                    <span>{{ $student->user->email }}</span>
                </div>
            </div>

            <div class="admin-form-grid two">
                <div><label>Nama</label><input type="text" name="name" value="{{ old('name', $student->user->name) }}" required></div>
                <div><label>Email</label><input type="email" name="email" value="{{ old('email', $student->user->email) }}" required></div>
            </div>
            <div class="admin-form-grid two">
                <div><label>NIM</label><input type="text" name="nim" value="{{ old('nim', $student->nim) }}" required></div>
                <div>
                    <label>Ganti Foto Profil</label>
                    <input type="file" name="profile_photo" accept="image/*">
                    @if ($student->user->profile_photo && $student->user->profile_photo !== 'profile_photos/default-3d.svg')
                        <label class="admin-checkbox"><input type="checkbox" name="delete_photo" value="1"> Hapus foto saat ini</label>
                    @endif
                </div>
            </div>
            <div class="admin-form-grid two">
                <div><label>Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date) }}"></div>
                <div>
                    <label>Agama</label>
                    <select name="religion">
                        @foreach (['Islam','Protestan','Katolik','Hindu','Buddha','Konghucu','Lainnya'] as $religion)
                            <option value="{{ $religion }}" {{ old('religion', $student->religion) === $religion ? 'selected' : '' }}>{{ $religion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="admin-form-grid three">
                <div>
                    <label>Jenis Kelamin</label>
                    <select name="gender">
                        <option value="Laki-laki" {{ old('gender', $student->gender) === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender', $student->gender) === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div><label>No. Telepon</label><input type="text" name="phone_number" value="{{ old('phone_number', $student->phone_number) }}"></div>
                <div><label>Alamat</label><input type="text" name="address" value="{{ old('address', $student->address) }}"></div>
            </div>
            <div class="admin-form-grid three">
                <div>
                    <label>Program Studi</label>
                    <select name="prodi">
                        <option value="Sistem Informasi Bisnis" {{ old('prodi', $student->prodi) === 'Sistem Informasi Bisnis' ? 'selected' : '' }}>Sistem Informasi Bisnis</option>
                        <option value="Teknik Informatika" {{ old('prodi', $student->prodi) === 'Teknik Informatika' ? 'selected' : '' }}>Teknik Informatika</option>
                    </select>
                </div>
                <div><label>Semester</label><input type="number" min="1" max="8" name="semester" value="{{ old('semester', $student->semester) }}"></div>
                <div><label>Kelas</label><input type="text" name="class" value="{{ old('class', $student->class) }}"></div>
            </div>
            <div class="admin-form-grid four">
                <div><label>Streak</label><input type="number" min="0" name="streak" value="{{ old('streak', $student->streak) }}"></div>
                <div><label>EXP</label><input type="number" min="0" name="exp" value="{{ old('exp', $student->exp) }}"></div>
                <div><label>Poin Mingguan</label><input type="number" min="0" name="weekly_score" value="{{ old('weekly_score', $student->weekly_score) }}"></div>
                <div><label>Total Poin</label><input type="number" min="0" name="total_score" value="{{ old('total_score', $student->total_score) }}"></div>
            </div>
            <div class="admin-form-grid one">
                <div>
                    <label>Challenge Aktif</label>
                    <select name="current_challenge_id">
                        <option value="">Tidak ada challenge aktif</option>
                        @foreach ($challenges as $challenge)
                            <option value="{{ $challenge->id }}" {{ (string) old('current_challenge_id', $student->current_challenge_id) === (string) $challenge->id ? 'selected' : '' }}>
                                {{ $challenge->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="admin-form-actions">
                <a href="{{ route('admin.students.show', $student->id) }}" class="btn-neutral">Kembali</a>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <style>
        .admin-form-page { max-width: 1040px; margin: 0 auto; }
        .admin-form-hero { padding:28px; border-radius:30px; margin-bottom:24px; }
        .admin-form-kicker { margin:0; font-size:12px; letter-spacing:.32em; text-transform:uppercase; color:rgba(220, 231, 243,.72); }
        .admin-form-title { margin:12px 0 0; color:#fff; font-size:40px; }
        .admin-form-copy { margin:14px 0 0; color:rgba(220, 231, 243,.75); line-height:1.8; }
        .admin-form-alert { margin-bottom:16px; padding:16px 18px; border-radius:18px; background:rgba(254,226,226,.96); color:#991b1b; }
        .admin-form-card { padding:24px; border-radius:30px; }
        .admin-form-profile { display:flex; align-items:center; gap:16px; margin-bottom:22px; padding:18px; border-radius:22px; background:#E8F0F8; }
        .admin-form-profile img { width:72px; height:72px; object-fit:cover; border-radius:999px; }
        .admin-form-profile-meta { display:flex; flex-direction:column; gap:4px; color:#09254A; }
        .admin-form-profile-meta span { color:#6A7C93; font-size:14px; }
        .admin-form-grid { display:grid; gap:20px; margin-bottom:20px; }
        .admin-form-grid.one { grid-template-columns:1fr; }
        .admin-form-grid.two { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .admin-form-grid.three { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .admin-form-grid.four { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .admin-form-card label { display:block; margin-bottom:10px; font-weight:700; color:#263E5C; }
        .admin-form-card input, .admin-form-card select { width:100%; padding:14px 16px; border-radius:16px; border:1px solid #9CB8D8; background:#fff; color:#09254A; }
        .admin-checkbox { margin-top:10px; display:flex; align-items:center; gap:10px; color:#6A7C93; }
        .admin-checkbox input { width:auto; }
        .admin-form-actions { display:flex; justify-content:space-between; gap:14px; margin-top:24px; }
        .btn-primary, .btn-neutral { display:inline-flex; align-items:center; justify-content:center; padding:14px 18px; border-radius:16px; text-decoration:none; font-weight:700; border:0; cursor:pointer; color:#fff; }
        .btn-primary { background:var(--admin-accent); }
        .btn-neutral { background:#6A7C93; }
        @media (max-width:768px) { .admin-form-grid.two, .admin-form-grid.three, .admin-form-grid.four { grid-template-columns:1fr; } .admin-form-actions { flex-direction:column; } .admin-form-title { font-size:32px; } }
    </style>
@endsection
