<x-guest-layout>
    <div class="register-panel">
        <div class="register-heading">
            <p>Daftar akun</p>
            <h1>Buat akun mahasiswa</h1>
            <span>Lengkapi data utama untuk mulai menggunakan CTG.</span>
        </div>

        <form method="POST" action="{{ route('register') }}" class="register-form">
            @csrf

            <div class="register-grid two">
                <div class="register-field">
                    <label for="name">Nama Lengkap</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="register-field">
                    <label for="nim">NIM</label>
                    <input id="nim" type="text" name="nim" value="{{ old('nim') }}" required>
                    <x-input-error :messages="$errors->get('nim')" class="mt-2" />
                </div>
            </div>

            <div class="register-field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="register-grid two">
                <div class="register-field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div class="register-field">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>

            <div class="register-grid two">
                <div class="register-field">
                    <label for="phone_number">No. Telepon</label>
                    <input id="phone_number" type="text" name="phone_number" value="{{ old('phone_number') }}">
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                </div>
                <div class="register-field">
                    <label for="birth_date">Tanggal Lahir</label>
                    <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}">
                    <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                </div>
            </div>

            <div class="register-grid two">
                <div class="register-field">
                    <label for="religion">Agama</label>
                    <select id="religion" name="religion">
                        <option value="">Pilih agama</option>
                        @foreach (['Islam','Protestan','Katolik','Hindu','Buddha','Konghucu','Lainnya'] as $religion)
                            <option value="{{ $religion }}" {{ old('religion') === $religion ? 'selected' : '' }}>{{ $religion }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('religion')" class="mt-2" />
                </div>
                <div class="register-field">
                    <label for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender">
                        <option value="">Pilih jenis kelamin</option>
                        <option value="Laki-laki" {{ old('gender') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                </div>
            </div>

            <div class="register-field">
                <label for="address">Alamat</label>
                <input id="address" type="text" name="address" value="{{ old('address') }}" placeholder="Opsional">
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div class="register-grid three">
                <div class="register-field">
                    <label for="prodi">Program Studi</label>
                    <select id="prodi" name="prodi">
                        <option value="">Pilih prodi</option>
                        <option value="Teknik Informatika" {{ old('prodi') === 'Teknik Informatika' ? 'selected' : '' }}>Teknik Informatika</option>
                        <option value="Sistem Informasi Bisnis" {{ old('prodi') === 'Sistem Informasi Bisnis' ? 'selected' : '' }}>Sistem Informasi Bisnis</option>
                    </select>
                    <x-input-error :messages="$errors->get('prodi')" class="mt-2" />
                </div>
                <div class="register-field">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester">
                        <option value="">Pilih</option>
                        @foreach (range(1, 8) as $semester)
                            <option value="{{ $semester }}" {{ (string) old('semester') === (string) $semester ? 'selected' : '' }}>{{ $semester }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('semester')" class="mt-2" />
                </div>
                <div class="register-field">
                    <label for="class">Kelas</label>
                    <input id="class" type="text" name="class" value="{{ old('class') }}" placeholder="Contoh: TI-4A">
                    <x-input-error :messages="$errors->get('class')" class="mt-2" />
                </div>
            </div>

            <button type="submit" class="register-submit">Daftar</button>

            <div class="register-footer">
                <span>Sudah punya akun?</span>
                <a href="{{ route('login') }}">Masuk</a>
            </div>
        </form>
    </div>

    <style>
        .register-panel { display:flex; flex-direction:column; gap:22px; }
        .register-heading p { margin:0; color:#1D5FD6; font-size:12px; font-weight:800; letter-spacing:.22em; text-transform:uppercase; }
        .register-heading h1 { margin:8px 0 0; color:#09254A; font-size:30px; line-height:1.12; font-weight:800; }
        .register-heading span { display:block; margin-top:9px; color:#6A7C93; font-size:14px; line-height:1.6; }
        .register-form { display:flex; flex-direction:column; gap:15px; }
        .register-grid { display:grid; gap:14px; }
        .register-grid.two { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .register-grid.three { grid-template-columns:1.2fr .8fr 1fr; }
        .register-field label { display:block; margin-bottom:7px; color:#263E5C; font-size:13px; font-weight:800; }
        .register-field input, .register-field select { width:100%; height:48px; border:1px solid #9CB8D8; border-radius:15px; background:#fff; color:#09254A; padding:0 14px; outline:0; transition:.18s ease; }
        .register-field input:focus, .register-field select:focus { border-color:#2BA7D8; box-shadow:0 0 0 4px rgba(37,99,235,.12); }
        .register-field input::placeholder { color:#94a3b8; }
        .register-submit { width:100%; height:52px; border:0; border-radius:16px; background:linear-gradient(90deg,#1D5FD6,#2BA7D8); color:#fff; font-size:15px; font-weight:900; cursor:pointer; box-shadow:0 14px 26px rgba(37,99,235,.22); }
        .register-footer { display:flex; justify-content:center; gap:6px; color:#6A7C93; font-size:14px; }
        .register-footer a { color:#1D5FD6; font-weight:800; text-decoration:none; }
        .register-footer a:hover { text-decoration:underline; }
        @media (max-width:640px) { .register-grid.two, .register-grid.three { grid-template-columns:1fr; } }
    </style>
</x-guest-layout>
