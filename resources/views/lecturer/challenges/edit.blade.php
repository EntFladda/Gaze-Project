@extends('lecturer.layouts.app')

@section('content')
    <div class="mission-form-page">
        <section class="mission-form-hero">
            <p class="mission-form-kicker">Edit Mission</p>
            <h1 class="mission-form-title">{{ $challenge->title }}</h1>
            <p class="mission-form-copy">Perbarui bagian materi atau judul mission bila alur belajar berubah.</p>
        </section>

        @if ($errors->any())
            <div class="mission-form-alert">
                <strong>Data belum bisa diperbarui.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lecturer.challenges.update', $challenge->id) }}" method="POST" class="mission-form-card bg-white">
            @csrf
            @method('PUT')

            <div class="mission-form-grid">
                <div>
                    <label for="section_id" class="mission-form-label">Bagian Materi</label>
                    <select name="section_id" id="section_id" class="mission-form-input" required>
                        <option value="">Pilih bagian</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" {{ (string) old('section_id', $challenge->section_id) === (string) $section->id ? 'selected' : '' }}>
                                {{ $section->order }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mission-form-hint">Pindahkan ke bagian lain jika diperlukan.</p>
                </div>

                <div>
                    <label for="title" class="mission-form-label">Judul Mission</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $challenge->title) }}" class="mission-form-input" required>
                    <p class="mission-form-hint">Gunakan judul yang singkat dan jelas.</p>
                </div>
            </div>

            <div class="mission-form-summary">
                <div class="mission-summary-box">
                    <span>EXP Saat Ini</span>
                    <strong>{{ $challenge->total_exp }}</strong>
                    <small>Akan menyesuaikan bila soal berubah.</small>
                </div>
                <div class="mission-summary-box">
                    <span>Poin Saat Ini</span>
                    <strong>{{ $challenge->total_score }}</strong>
                    <small>Mengikuti total skor seluruh soal.</small>
                </div>
            </div>

            <div class="mission-form-actions">
                <a href="{{ route('lecturer.challenges.index') }}" class="mission-form-btn neutral">Kembali</a>
                <button type="submit" class="mission-form-btn primary">Perbarui</button>
            </div>
        </form>
    </div>

    <style>
        .mission-form-page { max-width:980px; margin:0 auto; }
        .mission-form-hero { margin-bottom:18px; padding:22px 24px; border-radius:26px; border:1px solid rgba(191,219,254,.14); background:linear-gradient(135deg,#0A2342,#0F2F57); box-shadow:0 16px 34px rgba(15,23,42,.18); }
        .mission-form-kicker { margin:0; font-size:11px; font-weight:900; letter-spacing:.18em; text-transform:uppercase; color:rgba(191,219,254,.76); }
        .mission-form-title { margin:8px 0 0; color:#fff; font-size:34px; line-height:1.1; font-weight:900; }
        .mission-form-copy { margin:8px 0 0; color:rgba(219,234,254,.75); line-height:1.55; }
        .mission-form-alert { margin-bottom:14px; padding:14px 16px; border-radius:16px; background:rgba(254,226,226,.96); color:#991b1b; font-weight:700; }
        .mission-form-alert ul { margin:10px 0 0 18px; }
        .mission-form-card { padding:20px; border-radius:24px; border:1px solid #B7CCE6; box-shadow:0 12px 28px rgba(15,23,42,.08); }
        .mission-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .mission-form-label { display:block; margin-bottom:8px; color:#263E5C; font-weight:900; font-size:14px; }
        .mission-form-input { width:100%; height:50px; padding:0 14px; border-radius:15px; border:1px solid #9CB8D8; box-sizing:border-box; color:#09254A; outline:0; }
        .mission-form-input:focus { border-color:#2BA7D8; box-shadow:0 0 0 4px rgba(37,99,235,.12); }
        .mission-form-hint { margin:8px 0 0; color:#94a3b8; font-size:13px; line-height:1.5; }
        .mission-form-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-top:16px; }
        .mission-summary-box { padding:16px; border-radius:20px; background:#E8F0F8; border:1px solid #B7CCE6; }
        .mission-summary-box span { display:block; color:#1D5FD6; font-size:12px; font-weight:900; letter-spacing:.14em; text-transform:uppercase; }
        .mission-summary-box strong { display:block; margin-top:8px; color:#09254A; font-size:21px; }
        .mission-summary-box small { display:block; margin-top:6px; color:#6A7C93; line-height:1.5; }
        .mission-form-actions { display:flex; justify-content:space-between; gap:12px; margin-top:20px; }
        .mission-form-btn { display:inline-flex; align-items:center; justify-content:center; padding:13px 17px; border-radius:15px; text-decoration:none; font-weight:900; border:0; cursor:pointer; }
        .mission-form-btn.neutral { background:#6A7C93; color:#fff; }
        .mission-form-btn.primary { background:linear-gradient(90deg,#1D5FD6,#2BA7D8); color:#fff; }
        @media (max-width:768px) { .mission-form-grid,.mission-form-summary { grid-template-columns:1fr; } .mission-form-title { font-size:29px; } .mission-form-actions { flex-direction:column; } }
    </style>
@endsection
