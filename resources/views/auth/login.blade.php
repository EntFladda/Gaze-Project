<x-guest-layout>
    <div class="login-panel">
        <div class="login-heading">
            <p>Selamat datang</p>
            <h1>Masuk ke akun</h1>
            <span>Gunakan email atau NIM untuk melanjutkan.</span>
        </div>

        <x-auth-session-status class="mb-2" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf

            <div class="login-field">
                <label for="email">Email / NIM</label>
                <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username" placeholder="contoh: 987654321">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="login-field">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        placeholder="Masukkan password">
                    <button type="button" id="togglePassword">Lihat</button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="login-options">
                <label for="remember_me">
                    <input id="remember_me" type="checkbox" name="remember">
                    Ingat saya
                </label>
            </div>

            <button type="submit" class="login-submit">Masuk</button>

            <div class="login-footer">
                <span>Belum punya akun?</span>
                <a href="{{ route('register') }}">Daftar</a>
            </div>
        </form>
    </div>

    <style>
        .login-panel { display:flex; flex-direction:column; gap:18px; }
        .login-heading p { margin:0; color:#1D5FD6; font-size:12px; font-weight:800; letter-spacing:.22em; text-transform:uppercase; }
        .login-heading h1 { margin:8px 0 0; color:#09254A; font-size:28px; line-height:1.12; font-weight:800; }
        .login-heading span { display:block; margin-top:9px; color:#6A7C93; font-size:14px; line-height:1.6; }
        .login-form { display:flex; flex-direction:column; gap:14px; }
        .login-field label { display:block; margin-bottom:8px; color:#263E5C; font-size:14px; font-weight:800; }
        .login-field input { width:100%; height:46px; border:1px solid #9CB8D8; border-radius:14px; background:#fff; color:#09254A; padding:0 15px; outline:0; transition:.18s ease; }
        .login-field input:focus { border-color:#2BA7D8; box-shadow:0 0 0 4px rgba(37,99,235,.12); }
        .login-field input::placeholder { color:#94a3b8; }
        .password-wrap { position:relative; }
        .password-wrap input { padding-right:78px; }
        .password-wrap button { position:absolute; top:50%; right:10px; transform:translateY(-50%); border:0; background:#E8F0F8; color:#1D5FD6; border-radius:12px; padding:8px 10px; font-size:12px; font-weight:800; cursor:pointer; }
        .login-options { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .login-options label { display:inline-flex; align-items:center; gap:9px; color:#6A7C93; font-size:14px; font-weight:600; }
        .login-options input { width:17px; height:17px; accent-color:#1D5FD6; }
        .login-submit { width:100%; height:48px; border:0; border-radius:14px; background:linear-gradient(90deg,#1D5FD6,#2BA7D8); color:#fff; font-size:15px; font-weight:900; cursor:pointer; box-shadow:0 12px 22px rgba(37,99,235,.20); transition:.18s ease; }
        .login-submit:hover { transform:translateY(-1px); box-shadow:0 18px 32px rgba(37,99,235,.28); }
        .login-footer { display:flex; justify-content:center; gap:6px; color:#6A7C93; font-size:14px; }
        .login-footer a { font-weight:800; text-decoration:none; }
        .login-footer a:hover { text-decoration:underline; }
    </style>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword?.addEventListener('click', function() {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassword.textContent = isHidden ? 'Sembunyi' : 'Lihat';
        });
    </script>
</x-guest-layout>
