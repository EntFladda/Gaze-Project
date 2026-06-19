@extends('lecturer.layouts.app')

@section('content')
    <div class="section-form-page">
        <section class="section-form-hero">
            <p class="section-form-kicker">Bagian Belajar</p>
            <h1 class="section-form-title">Edit {{ $section->name }}</h1>
            <p class="section-form-copy">
                Gunakan halaman ini untuk memperbaiki nama atau mengatur ulang posisi bagian belajar dalam alur pembelajaran mahasiswa.
            </p>
        </section>

        @if ($errors->any())
            <div class="section-form-alert">
                <strong>Data belum bisa diperbarui.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lecturer.sections.update', $section->id) }}" method="POST" class="section-form-card bg-white">
            @csrf
            @method('PUT')

            <div class="section-form-grid">
                <div>
                    <label for="order" class="section-form-label">Urutan</label>
                    <input type="number" name="order" id="order" value="{{ old('order', $section->order) }}" class="section-form-input" required>
                    <p class="section-form-hint">Pastikan urutan tidak sama dengan bagian lain.</p>
                </div>

                <div>
                    <label for="name" class="section-form-label">Nama Bagian</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $section->name) }}" class="section-form-input" required>
                    <p class="section-form-hint">Gunakan nama yang mudah dipahami mahasiswa.</p>
                </div>
            </div>

            <div class="section-form-actions">
                <a href="{{ route('lecturer.sections.index') }}" class="section-form-btn neutral">Kembali</a>
                <button type="submit" class="section-form-btn primary">Perbarui</button>
            </div>
        </form>
    </div>

    <style>
        .section-form-page {
            max-width: 960px;
            margin: 0 auto;
        }

        .section-form-hero {
            margin-bottom: 24px;
            padding: 28px;
            border-radius: 30px;
            border: 1px solid rgba(183, 204, 230, 0.14);
            background: rgba(11, 47, 107, 0.78);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22);
        }

        .section-form-kicker {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.34em;
            text-transform: uppercase;
            color: rgba(183, 204, 230, 0.75);
        }

        .section-form-title {
            margin: 12px 0 0;
            color: #fff;
            font-size: 40px;
            font-weight: 700;
        }

        .section-form-copy {
            margin: 14px 0 0;
            color: rgba(220, 231, 243, 0.76);
            line-height: 1.8;
            max-width: 760px;
        }

        .section-form-alert {
            margin-bottom: 16px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(254, 226, 226, 0.96);
            color: #991b1b;
        }

        .section-form-alert ul {
            margin: 10px 0 0 18px;
        }

        .section-form-card {
            padding: 24px;
            border-radius: 30px;
        }

        .section-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .section-form-label {
            display: block;
            margin-bottom: 10px;
            color: #263E5C;
            font-weight: 700;
        }

        .section-form-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #9CB8D8;
            box-sizing: border-box;
            color: #09254A;
        }

        .section-form-hint {
            margin: 8px 0 0;
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.6;
        }

        .section-form-actions {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            margin-top: 24px;
        }

        .section-form-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 18px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            border: 0;
            cursor: pointer;
        }

        .section-form-btn.neutral {
            background: #6A7C93;
            color: #fff;
        }

        .section-form-btn.primary {
            background: linear-gradient(90deg, #1D5FD6, #2BA7D8);
            color: #fff;
        }

        @media (max-width: 768px) {
            .section-form-grid {
                grid-template-columns: 1fr;
            }

            .section-form-title {
                font-size: 32px;
            }

            .section-form-actions {
                flex-direction: column;
            }
        }
    </style>
@endsection
