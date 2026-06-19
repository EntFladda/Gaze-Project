@extends('student.layouts.app')

@section('content')
    @php
        $profilePhoto = $user->profile_photo;
        $normalizedProfilePhoto = strtolower((string) $profilePhoto);
        $hasCustomProfilePhoto = filled($profilePhoto)
            && ! str_contains($normalizedProfilePhoto, 'default');
        $studentInitial = strtoupper(mb_substr($user->name ?: 'M', 0, 1));
        $fields = [
            ['label' => 'NIM', 'value' => $student->nim ?: '-'],
            ['label' => 'Nama', 'value' => $user->name ?: '-'],
            ['label' => 'Email', 'value' => $user->email ?: '-'],
            ['label' => 'Kelas', 'value' => $student->class ?: '-'],
            ['label' => 'Program Studi', 'value' => $student->prodi ?: '-'],
            ['label' => 'Semester', 'value' => $student->semester ?: '-'],
            ['label' => 'No. Telepon', 'value' => $student->phone_number ?: '-'],
            ['label' => 'Jenis Kelamin', 'value' => $student->gender ?: '-'],
            ['label' => 'Agama', 'value' => $student->religion ?: '-'],
            ['label' => 'Tanggal Lahir', 'value' => $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->translatedFormat('d F Y') : '-'],
            ['label' => 'Alamat', 'value' => $student->address ?: 'Belum diisi', 'wide' => true],
        ];
    @endphp

    <style>
        .student-detail-page {
            max-width: 1040px;
            margin: 0 auto;
            padding: 34px 18px 54px;
            color: #0A2342;
        }

        .detail-hero {
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: #0A2342;
            color: #fff;
            padding: 28px;
            box-shadow: 0 18px 42px rgba(34, 7, 20, .18);
        }

        .detail-top {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .detail-eyebrow {
            margin: 0 0 8px;
            color: #B7CCE6;
            font-size: 13px;
            font-weight: 850;
            letter-spacing: .26em;
            text-transform: uppercase;
        }

        .detail-title {
            margin: 0;
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 900;
            line-height: 1.08;
        }

        .detail-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .detail-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 16px;
            color: #fff;
            font-weight: 850;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(255, 255, 255, .1);
        }

        .detail-btn.primary {
            border: 0;
            background: linear-gradient(135deg, #1D5FD6, #2BA7D8);
            box-shadow: 0 12px 24px rgba(29, 95, 214, .24);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 290px minmax(0, 1fr);
            gap: 18px;
            margin-top: 18px;
        }

        .detail-card {
            border-radius: 26px;
            border: 1px solid rgba(29, 95, 214, .25);
            background: #F4F8FC;
            padding: 24px;
            box-shadow: 0 18px 42px rgba(34, 7, 20, .13);
        }

        .detail-avatar {
            display: block;
            width: 132px;
            height: 132px;
            margin: 0 auto;
            border-radius: 999px;
            border: 5px solid #B7CCE6;
            object-fit: cover;
            background: #fff;
            box-shadow: 0 12px 28px rgba(29, 95, 214, .22);
        }

        .detail-avatar-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 48px;
            font-weight: 900;
            background:
                radial-gradient(circle at 35% 22%, rgba(255, 230, 109, .95), transparent 28%),
                linear-gradient(135deg, #1D5FD6 0%, #123A68 55%, #071426 100%);
        }

        .student-name {
            margin: 16px 0 4px;
            font-size: 26px;
            font-weight: 900;
        }

        .student-email {
            margin: 0;
            color: #6A7C93;
            overflow-wrap: anywhere;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field-box {
            min-width: 0;
            border: 1px solid #B7CCE6;
            border-radius: 18px;
            background: #F4F8FC;
            padding: 16px;
        }

        .field-box.wide {
            grid-column: 1 / -1;
        }

        .field-label {
            margin: 0;
            color: #1D5FD6;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .field-value {
            margin: 8px 0 0;
            color: #0A2342;
            font-size: 18px;
            font-weight: 850;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        @media (max-width: 860px) {
            .detail-grid,
            .field-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <main class="student-detail-page">
        <section class="detail-hero">
            <div class="detail-top">
                <div>
                    <p class="detail-eyebrow">Detail Akademik</p>
                    <h1 class="detail-title">Data Mahasiswa</h1>
                </div>
                <div class="detail-actions">
                    <a class="detail-btn" href="{{ route('student.profile.index') }}">Kembali</a>
                    <a class="detail-btn primary" href="{{ route('student.profile.edit') }}">Edit Profil</a>
                </div>
            </div>
        </section>

        <div class="detail-grid">
            <aside class="detail-card" style="text-align:center;">
                @if ($hasCustomProfilePhoto)
                    <img class="detail-avatar" src="{{ asset('storage/' . $profilePhoto) }}" alt="Foto profil {{ $user->name }}">
                @else
                    <div class="detail-avatar detail-avatar-fallback" aria-label="Foto profil belum diatur">
                        {{ $studentInitial }}
                    </div>
                @endif
                <h2 class="student-name">{{ $user->name }}</h2>
                <p class="student-email">{{ $user->email }}</p>

                <div style="margin-top:18px; display:grid; gap:10px; text-align:left;">
                    <div class="field-box">
                        <p class="field-label">NIM</p>
                        <p class="field-value">{{ $student->nim ?: '-' }}</p>
                    </div>
                    <div class="field-box">
                        <p class="field-label">Kelas</p>
                        <p class="field-value">{{ $student->class ?: '-' }}</p>
                    </div>
                </div>
            </aside>

            <section class="detail-card">
                <p class="detail-eyebrow" style="color:#1D5FD6;">Informasi Lengkap</p>
                <h2 class="detail-title" style="font-size:30px; color:#0A2342;">Identitas dan Akademik</h2>

                <div class="field-grid" style="margin-top:18px;">
                    @foreach ($fields as $field)
                        <div class="field-box {{ ! empty($field['wide']) ? 'wide' : '' }}">
                            <p class="field-label">{{ $field['label'] }}</p>
                            <p class="field-value">{{ $field['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
@endsection
