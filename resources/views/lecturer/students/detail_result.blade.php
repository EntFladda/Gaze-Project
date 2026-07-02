@extends('lecturer.layouts.app')

@section('content')
    <div class="student-result-page">
        <section class="student-result-hero">
            <div>
                <p class="student-result-kicker">Detail Percobaan</p>
                <h1 class="student-result-title">{{ $student->user->name }} - Percobaan {{ $attempt }}</h1>
                <p class="student-result-copy">Tinjau jawaban mahasiswa per soal untuk melihat proses koreksi dan pemahaman konsep pada mission ini.</p>
            </div>
            <a href="{{ route('lecturer.students.show', $student->id) }}" class="student-result-back">Kembali ke profil mahasiswa</a>
        </section>

        <section class="student-result-summary bg-white">
            <div class="student-result-summary-box">
                <span>Mission</span>
                <strong>{{ $challenge->title }}</strong>
            </div>
            <div class="student-result-summary-box">
                <span>Poin</span>
                <strong>{{ $result->total_score }}</strong>
            </div>
            <div class="student-result-summary-box">
                <span>EXP</span>
                <strong>{{ $result->total_exp }}</strong>
            </div>
            <div class="student-result-summary-box">
                <span>Percobaan</span>
                <strong>#{{ $result->attempt_number }}</strong>
            </div>
        </section>

        <!-- Gaze Focus Summary Cards -->
        <section class="grid gap-4 md:grid-cols-4 mb-6">
            <div class="student-result-summary-box" style="background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border-color: rgba(22, 163, 74, .25);">
                <span style="color: #166534;">Tingkat Fokus</span>
                <strong style="color: #166534;">{{ $result->focus_percentage ?? 'N/A' }}{{ isset($result->focus_percentage) ? '%' : '' }}</strong>
            </div>
            <div class="student-result-summary-box" style="background: linear-gradient(135deg, #FEF2F2, #FEE2E2); border-color: rgba(220, 38, 38, .25);">
                <span style="color: #991B1B;">Frekuensi Terganggu</span>
                <strong style="color: #991B1B;">{{ $result->unfocused_count ?? 'N/A' }} kali</strong>
            </div>
            <div class="student-result-summary-box" style="background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border-color: rgba(22, 163, 74, .25);">
                <span style="color: #166534;">Durasi Fokus</span>
                <strong style="color: #166534;">{{ $result->focused_duration ?? '0' }} detik</strong>
            </div>
            <div class="student-result-summary-box" style="background: linear-gradient(135deg, #FEF2F2, #FEE2E2); border-color: rgba(220, 38, 38, .25);">
                <span style="color: #991B1B;">Durasi Terganggu</span>
                <strong style="color: #991B1B;">{{ $result->unfocused_duration ?? '0' }} detik</strong>
            </div>
        </section>

        <section class="student-result-card bg-white">
            <div class="student-result-card-head">
                <div>
                    <p class="student-result-card-kicker">Jawaban Mahasiswa</p>
                    <h2 class="student-result-card-title">Ringkasan jawaban per soal</h2>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="student-result-table">
                    <thead>
                        <tr>
                            <th>Pertanyaan</th>
                            <th>Jawaban Mahasiswa</th>
                            <th>Detail Jawaban</th>
                            <th>Hasil</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($answers as $questionId => $groupedAnswers)
                            <tr>
                                <td>{{ Str::limit($groupedAnswers->first()->question->question_text ?? 'Soal tidak ditemukan', 120) }}</td>
                                <td>{{ $groupedAnswers->pluck('selected_answer')->join(', ') }}</td>
                                <td>{{ $groupedAnswers->pluck('selectedAnswer.answer')->filter()->join(', ') }}</td>
                                <td>
                                    @php
                                        $isAllCorrect = $groupedAnswers->every(fn($ans) => $ans->is_correct);
                                        $isAnyCorrect = $groupedAnswers->contains(fn($ans) => $ans->is_correct);
                                    @endphp

                                    @if ($isAllCorrect)
                                        <span class="student-result-pill success">Semua benar</span>
                                    @elseif ($isAnyCorrect)
                                        <span class="student-result-pill warn">Sebagian benar</span>
                                    @else
                                        <span class="student-result-pill danger">Perlu koreksi</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($groupedAnswers->first()->question)
                                        <a href="{{ route('lecturer.questions.show', $groupedAnswers->first()->question->id) }}" class="student-result-btn">
                                            Lihat Soal
                                        </a>
                                    @else
                                        <span class="text-gray-400">Tidak tersedia</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="students-empty">Belum ada jawaban untuk percobaan ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <style>
        .student-result-page { max-width: 1200px; margin: 0 auto; }
        .student-result-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:24px; padding:28px; border-radius:30px; border:1px solid rgba(191,219,254,.14); background: rgba(10,35,66,.78); box-shadow: 0 20px 50px rgba(0,0,0,.22); }
        .student-result-kicker { margin:0; font-size:12px; letter-spacing:.34em; text-transform:uppercase; color:rgba(191,219,254,.75); }
        .student-result-title { margin:12px 0 0; color:#fff; font-size:40px; font-weight:700; }
        .student-result-copy { margin:14px 0 0; max-width:760px; color:rgba(219,234,254,.76); line-height:1.8; }
        .student-result-back { display:inline-flex; align-items:center; justify-content:center; padding:14px 20px; border-radius:18px; background:rgba(255,255,255,.1); color:#fff; font-weight:700; text-decoration:none; }
        .student-result-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; padding:24px; border-radius:30px; margin-bottom:24px; }
        .student-result-summary-box { padding:18px; border-radius:20px; background:#E8F0F8; }
        .student-result-summary-box span { display:block; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:.12em; margin-bottom:10px; }
        .student-result-summary-box strong { color:#09254A; font-size:24px; }
        .student-result-card { padding:24px; border-radius:30px; }
        .student-result-card-head { margin-bottom:18px; }
        .student-result-card-kicker { margin:0; font-size:12px; text-transform:uppercase; letter-spacing:.3em; color:#1D5FD6; }
        .student-result-card-title { margin:8px 0 0; color:#09254A; font-size:30px; font-weight:700; }
        .student-result-table { width:100%; border-collapse:collapse; }
        .student-result-table thead th { padding:14px 16px; text-align:left; background:linear-gradient(90deg,#1D5FD6,#2B7FD8); color:#fff; font-size:12px; letter-spacing:.18em; text-transform:uppercase; }
        .student-result-table thead th:first-child { border-top-left-radius:18px; }
        .student-result-table thead th:last-child { border-top-right-radius:18px; }
        .student-result-table tbody td { padding:18px 16px; border-bottom:1px solid #DCE7F3; color:#263E5C; vertical-align:middle; }
        .student-result-table tbody tr:hover { background:#E8F0F8; }
        .student-result-pill { display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; font-weight:700; font-size:12px; }
        .student-result-pill.success { background:#dcfce7; color:#166534; }
        .student-result-pill.warn { background:#fff7ed; color:#c2410c; }
        .student-result-pill.danger { background:#fee2e2; color:#991b1b; }
        .student-result-btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:14px; background:#2BA7D8; color:#fff; font-weight:700; text-decoration:none; }
        @media (max-width:768px) {
            .student-result-hero { flex-direction:column; align-items:stretch; }
            .student-result-title { font-size:32px; }
            .student-result-summary { grid-template-columns:1fr; }
        }
    </style>
@endsection
