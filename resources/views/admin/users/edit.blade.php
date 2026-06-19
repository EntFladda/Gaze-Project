@extends('admin.layouts.app')

@section('content')
    <div class="admin-form-page">
        <section class="admin-form-hero admin-surface">
            <p class="admin-form-kicker">Users</p>
            <h1 class="admin-form-title">Perbarui akun pengguna</h1>
        </section>

        @if ($errors->any())
            <div class="admin-form-alert">
                <strong>Perubahan belum bisa disimpan.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data" class="admin-card-white admin-form-card">
            @csrf
            @method('PUT')

            <div class="admin-form-profile">
                    <img id="profile-photo-preview" src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}" alt="{{ $user->name }}">
                <div class="admin-form-profile-meta">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                </div>
            </div>

            <div class="admin-form-grid two">
                <div><label>Nama</label><input type="text" name="name" value="{{ old('name', $user->name) }}"></div>
                <div><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}"></div>
            </div>

            <div class="admin-form-grid two">
                <div><label>Password Baru</label><input type="password" name="password"></div>
                <div><label>Konfirmasi Password</label><input type="password" name="password_confirmation"></div>
            </div>

            <div class="admin-form-grid two">
                <div>
                    <label>Role</label>
                    <select name="role" id="role" onchange="toggleStudentFields()" required>
                        <option value="admin" {{ $user->hasRole('admin') ? 'selected' : '' }}>Admin</option>
                        <option value="lecturer" {{ $user->hasRole('lecturer') ? 'selected' : '' }}>Lecturer</option>
                        <option value="student" {{ $user->hasRole('student') ? 'selected' : '' }}>Student</option>
                    </select>
                </div>
                <div>
                    <label>Ganti Foto Profil</label>
                    <input type="file" name="profile_photo" accept="image/*">
                    @if ($user->profile_photo && $user->profile_photo !== 'profile_photos/default-3d.svg')
                        <label class="admin-checkbox"><input type="checkbox" name="delete_photo" value="1"> Hapus foto saat ini</label>
                    @endif
                </div>
            </div>

            <div id="studentFields" class="admin-form-student" style="display:none;">
                <h2>Data Mahasiswa</h2>
                <div class="admin-form-grid two">
                    <div><label>NIM</label><input type="text" name="nim" value="{{ old('nim', $user->student->nim ?? '') }}"></div>
                    <div><label>No. Telepon</label><input type="text" name="phone_number" value="{{ old('phone_number', $user->student->phone_number ?? '') }}"></div>
                </div>
                <div class="admin-form-grid two">
                    <div><label>Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $user->student->birth_date ?? '') }}"></div>
                    <div>
                        <label>Agama</label>
                        <select name="religion">
                            @foreach (['Islam','Protestan','Katolik','Hindu','Buddha','Konghucu','Lainnya'] as $religion)
                                <option value="{{ $religion }}" {{ old('religion', $user->student->religion ?? '') === $religion ? 'selected' : '' }}>{{ $religion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="admin-form-grid two">
                    <div>
                        <label>Jenis Kelamin</label>
                        <select name="gender">
                            <option value="Laki-laki" {{ old('gender', $user->student->gender ?? '') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ old('gender', $user->student->gender ?? '') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div><label>Alamat</label><input type="text" name="address" value="{{ old('address', $user->student->address ?? '') }}"></div>
                </div>
                <div class="admin-form-grid three">
                    <div>
                        <label>Program Studi</label>
                        <select name="prodi">
                            <option value="Sistem Informasi Bisnis" {{ old('prodi', $user->student->prodi ?? '') === 'Sistem Informasi Bisnis' ? 'selected' : '' }}>Sistem Informasi Bisnis</option>
                            <option value="Teknik Informatika" {{ old('prodi', $user->student->prodi ?? '') === 'Teknik Informatika' ? 'selected' : '' }}>Teknik Informatika</option>
                        </select>
                    </div>
                    <div><label>Semester</label><input type="number" min="1" max="8" name="semester" value="{{ old('semester', $user->student->semester ?? '') }}"></div>
                    <div><label>Kelas</label><input type="text" name="class" value="{{ old('class', $user->student->class ?? '') }}"></div>
                </div>
            </div>

            <div class="admin-form-actions">
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn-neutral">Kembali</a>
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
        .admin-form-grid.two { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .admin-form-grid.three { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .admin-form-card label { display:block; margin-bottom:10px; font-weight:700; color:#263E5C; }
        .admin-form-card input, .admin-form-card select { width:100%; padding:14px 16px; border-radius:16px; border:1px solid #9CB8D8; background:#fff; color:#09254A; }
        .admin-form-student { margin-top:10px; padding:22px; border-radius:24px; background:#E8F0F8; border:1px solid #B7CCE6; }
        .admin-form-student h2 { margin:0 0 18px; color:#1D5FD6; }
        .admin-checkbox { margin-top:10px; display:flex; align-items:center; gap:10px; color:#6A7C93; }
        .admin-checkbox input { width:auto; }
        .admin-form-actions { display:flex; justify-content:space-between; gap:14px; margin-top:24px; }
        .btn-primary, .btn-neutral { display:inline-flex; align-items:center; justify-content:center; padding:14px 18px; border-radius:16px; text-decoration:none; font-weight:700; border:0; cursor:pointer; color:#fff; }
        .btn-primary { background:var(--admin-accent); }
        .btn-neutral { background:#6A7C93; }
        @media (max-width:768px) { .admin-form-grid.two, .admin-form-grid.three { grid-template-columns:1fr; } .admin-form-actions { flex-direction:column; } .admin-form-title { font-size:32px; } }
    </style>

    <script>
        function toggleStudentFields() {
            const role = document.getElementById('role').value;
            document.getElementById('studentFields').style.display = role === 'student' ? 'block' : 'none';
        }
        toggleStudentFields();
    </script>
@endsection
