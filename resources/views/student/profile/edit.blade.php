@extends('student.layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-10">
        <div class="rounded-[30px] border border-sky-200/25 bg-[#0A2342] p-8 text-white shadow-2xl">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-sky-200/80">Edit Profil</p>
                    <h1 class="mt-2 text-3xl font-bold">Perbarui Data Mahasiswa</h1>
                </div>

                <a href="{{ route('student.profile.index') }}"
                    class="inline-flex items-center rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                    Kembali ke Dashboard
                </a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-sky-300/40 bg-blue-950/30 px-5 py-4 text-sky-100">
                    <p class="font-semibold">Ada data yang perlu diperbaiki:</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $currentPhoto = old('avatar_choice', $student->user->profile_photo ?? 'profile_photos/default-3d.svg');
                $selectedPresetAvatar = in_array($currentPhoto, $presetAvatars, true) ? $currentPhoto : '';
                $showAvatarPicker = $selectedPresetAvatar !== '';
                $showExtraAvatars = $selectedPresetAvatar !== '' && in_array($selectedPresetAvatar, array_slice($presetAvatars, 4), true);
            @endphp

            <form action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data" class="mt-8">
                @csrf
                @method('PUT')

                <div class="grid gap-8 lg:grid-cols-[320px_1fr]">
                    <aside class="rounded-[26px] border border-white/10 bg-white/10 p-6">
                        <div class="flex flex-col items-center text-center">
                            <img id="profile-preview"
                                src="{{ asset('storage/' . ($student->user->profile_photo ?? 'profile_photos/default-3d.svg')) }}"
                                alt="Foto profil"
                                class="h-36 w-36 rounded-full border-4 border-sky-200 object-cover shadow-lg">

                            <h2 class="mt-5 text-xl font-bold">{{ $user->name }}</h2>
                            <p class="mt-1 text-sm text-sky-100/75">{{ $user->email }}</p>

                            <div class="mt-6 flex w-full flex-col gap-3">
                                <button type="button" id="toggle-avatar-picker"
                                    class="rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                                    Ganti Avatar
                                </button>

                                <label for="profile_photo"
                                    class="cursor-pointer rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                                    Upload Foto
                                </label>

                                <button type="button" onclick="deleteProfilePhoto()"
                                    class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                                    Avatar Default
                                </button>
                            </div>

                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="hidden">
                            <input type="hidden" id="delete_photo" name="delete_photo" value="0">
                            <input type="hidden" id="avatar_choice" name="avatar_choice" value="{{ $selectedPresetAvatar }}">

                            <div class="mt-6 w-full rounded-2xl bg-black/10 px-4 py-4 text-left">
                                <p class="text-xs uppercase tracking-[0.2em] text-sky-200/70">Data Tetap</p>
                                <div class="mt-3 space-y-3 text-sm">
                                    <div>
                                        <p class="text-sky-100/65">NIM</p>
                                        <p class="font-semibold text-white">{{ $student->nim ?: '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sky-100/65">Nama</p>
                                        <p class="font-semibold text-white">{{ $user->name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="rounded-[26px] border border-white/10 bg-white/10 p-6">
                        <div id="avatar-picker-panel"
                            class="{{ $showAvatarPicker ? '' : 'hidden' }} rounded-[24px] border border-white/10 bg-black/10 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="text-lg font-bold text-white">Pilih Avatar</h2>
                                <button type="button" id="hide-avatar-picker"
                                    class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-sky-100 transition hover:bg-white/10">
                                    Tutup
                                </button>
                            </div>

                            <div id="avatar-grid" class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($presetAvatars as $index => $avatar)
                                    <button type="button"
                                        class="avatar-option {{ $index >= 4 && ! $showExtraAvatars ? 'avatar-extra hidden' : ($index >= 4 ? 'avatar-extra' : '') }} group rounded-3xl border border-white/10 bg-white/5 p-3 transition hover:-translate-y-1 hover:border-sky-300/40 hover:bg-white/10 {{ $selectedPresetAvatar === $avatar ? 'ring-2 ring-sky-300 border-sky-300/50 bg-white/10' : '' }}"
                                        data-avatar="{{ $avatar }}"
                                        data-avatar-url="{{ asset('storage/' . $avatar) }}">
                                        <img src="{{ asset('storage/' . $avatar) }}" alt="Avatar preset"
                                            class="mx-auto h-20 w-20 rounded-full border-2 border-white/10 object-cover shadow-md">
                                    </button>
                                @endforeach
                            </div>

                            @if (count($presetAvatars) > 4)
                                <div class="mt-4 flex justify-center">
                                    <button type="button" id="toggle-avatar-list"
                                        class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-sky-100 transition hover:bg-white/10">
                                        {{ $showExtraAvatars ? 'Sembunyikan' : 'Lihat Semua' }}
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="{{ $showAvatarPicker ? 'mt-6' : '' }} grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Alamat</label>
                                <input type="text" name="address" value="{{ old('address', $student->address) }}"
                                    class="w-full rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-white placeholder:text-sky-100/45 focus:border-sky-300 focus:outline-none">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Tanggal Lahir</label>
                                <input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date) }}"
                                    class="w-full rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-white focus:border-sky-300 focus:outline-none">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Agama</label>
                                <select name="religion"
                                    class="w-full rounded-2xl border border-white/15 bg-[#0F2F57] px-4 py-3 text-white focus:border-sky-300 focus:outline-none">
                                    @foreach (['Islam', 'Protestan', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'] as $religion)
                                        <option value="{{ $religion }}"
                                            {{ old('religion', $student->religion) == $religion ? 'selected' : '' }}>
                                            {{ $religion }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Jenis Kelamin</label>
                                <select name="gender"
                                    class="w-full rounded-2xl border border-white/15 bg-[#0F2F57] px-4 py-3 text-white focus:border-sky-300 focus:outline-none">
                                    <option value="Laki-laki" {{ old('gender', $student->gender) == 'Laki-laki' ? 'selected' : '' }}>
                                        Laki-laki
                                    </option>
                                    <option value="Perempuan" {{ old('gender', $student->gender) == 'Perempuan' ? 'selected' : '' }}>
                                        Perempuan
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Nomor Telepon</label>
                                <input type="text" name="phone_number" value="{{ old('phone_number', $student->phone_number) }}"
                                    class="w-full rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-white placeholder:text-sky-100/45 focus:border-sky-300 focus:outline-none">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Program Studi</label>
                                <select name="prodi"
                                    class="w-full rounded-2xl border border-white/15 bg-[#0F2F57] px-4 py-3 text-white focus:border-sky-300 focus:outline-none">
                                    <option value="Sistem Informasi Bisnis"
                                        {{ old('prodi', $student->prodi) == 'Sistem Informasi Bisnis' ? 'selected' : '' }}>
                                        Sistem Informasi Bisnis
                                    </option>
                                    <option value="Teknik Informatika"
                                        {{ old('prodi', $student->prodi) == 'Teknik Informatika' ? 'selected' : '' }}>
                                        Teknik Informatika
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-sky-100">Semester</label>
                                <select name="semester"
                                    class="w-full rounded-2xl border border-white/15 bg-[#0F2F57] px-4 py-3 text-white focus:border-sky-300 focus:outline-none">
                                    @for ($i = 1; $i <= 8; $i++)
                                        <option value="{{ $i }}" {{ old('semester', $student->semester) == $i ? 'selected' : '' }}>
                                            Semester {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <button type="submit"
                                class="rounded-2xl bg-gradient-to-r from-blue-600 to-sky-500 px-6 py-3 font-semibold text-white shadow-lg transition hover:scale-[1.02] hover:shadow-sky-300/30">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('student.profile.index') }}"
                                class="rounded-2xl border border-white/15 bg-white/10 px-6 py-3 font-semibold text-white transition hover:bg-white/15">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const profilePreview = document.getElementById('profile-preview');
        const profilePhotoInput = document.getElementById('profile_photo');
        const deletePhotoInput = document.getElementById('delete_photo');
        const avatarChoiceInput = document.getElementById('avatar_choice');
        const avatarOptions = document.querySelectorAll('.avatar-option');
        const avatarPickerPanel = document.getElementById('avatar-picker-panel');
        const toggleAvatarPickerButton = document.getElementById('toggle-avatar-picker');
        const hideAvatarPickerButton = document.getElementById('hide-avatar-picker');
        const toggleAvatarListButton = document.getElementById('toggle-avatar-list');
        const extraAvatars = document.querySelectorAll('.avatar-extra');
        const defaultAvatarPath = 'profile_photos/default-3d.svg';
        const defaultAvatarUrl = "{{ asset('storage/profile_photos/default-3d.svg') }}";
        let avatarListExpanded = {{ $showExtraAvatars ? 'true' : 'false' }};

        profilePhotoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
                deletePhotoInput.value = '0';
                avatarChoiceInput.value = '';
                clearAvatarSelection();
            };
            reader.readAsDataURL(file);
        });

        avatarOptions.forEach(option => {
            option.addEventListener('click', () => {
                const selectedAvatar = option.dataset.avatar;
                const selectedAvatarUrl = option.dataset.avatarUrl;

                avatarChoiceInput.value = selectedAvatar;
                deletePhotoInput.value = '0';
                profilePhotoInput.value = '';
                profilePreview.src = selectedAvatarUrl;

                clearAvatarSelection();
                option.classList.add('ring-2', 'ring-sky-300', 'border-sky-300/50', 'bg-white/10');
            });
        });

        toggleAvatarPickerButton.addEventListener('click', () => {
            avatarPickerPanel.classList.toggle('hidden');
        });

        if (hideAvatarPickerButton) {
            hideAvatarPickerButton.addEventListener('click', () => {
                avatarPickerPanel.classList.add('hidden');
            });
        }

        if (toggleAvatarListButton) {
            toggleAvatarListButton.addEventListener('click', () => {
                avatarListExpanded = !avatarListExpanded;
                extraAvatars.forEach(option => option.classList.toggle('hidden', !avatarListExpanded));
                toggleAvatarListButton.textContent = avatarListExpanded ? 'Sembunyikan' : 'Lihat Semua';
            });
        }

        function clearAvatarSelection() {
            avatarOptions.forEach(option => {
                option.classList.remove('ring-2', 'ring-sky-300', 'border-sky-300/50', 'bg-white/10');
            });
        }

        function deleteProfilePhoto() {
            deletePhotoInput.value = '1';
            avatarChoiceInput.value = defaultAvatarPath;
            profilePhotoInput.value = '';
            profilePreview.src = defaultAvatarUrl;
            clearAvatarSelection();
            avatarPickerPanel.classList.remove('hidden');

            avatarOptions.forEach(option => {
                if (option.dataset.avatar === defaultAvatarPath) {
                    option.classList.add('ring-2', 'ring-sky-300', 'border-sky-300/50', 'bg-white/10');
                }
            });
        }
    </script>
@endsection
