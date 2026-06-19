@php
    use Carbon\Carbon;

    $start = Carbon::parse($challengeResult->created_at);
    $end = Carbon::parse($challengeResult->ended_at ?? now());
    $duration = $start->diff($end);
    $durationText = trim(($duration->h ? $duration->h . ' jam ' : '') . ($duration->i ? $duration->i . ' menit ' : '') . $duration->s . ' detik');
    $pesan = $challengeResult->correct_answers > $challengeResult->wrong_answers
        ? $motivasiBenar[array_rand($motivasiBenar)]
        : $motivasiSalah[array_rand($motivasiSalah)];
    $challenge = $challengeResult->challenge;
    $missionName = $challenge?->title ?? 'Mission';
    $sectionName = $challenge?->section?->name ?? '-';
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('favicon-ctg.png') }}">
    <title>Mission Selesai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #071426, #0A2342 48%, #123A68);
        }

        .summary-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 34px 18px 54px;
        }

        .summary-card {
            background: #F4F8FC;
            border: 1px solid rgba(29, 95, 214, .25);
            border-radius: 22px;
            box-shadow: 0 18px 42px rgba(34, 7, 20, .14);
        }

        .summary-eyebrow {
            color: #1D5FD6;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .summary-title {
            color: #0A2342;
            font-weight: 900;
            line-height: 1.08;
        }

        .summary-stat {
            min-height: 126px;
            padding: 20px;
        }

        .summary-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 8px;
            color: #0A2342;
            font-size: 34px;
            font-weight: 950;
            line-height: 1;
        }

        .summary-note {
            margin-top: 10px;
            color: #6A7C93;
            font-size: 13px;
            line-height: 1.5;
        }

        .table-head {
            background: #0A2342;
            color: #fff;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 850;
            white-space: nowrap;
        }

        .status-good {
            background: #dcfce7;
            color: #166534;
        }

        .status-help {
            background: #fef3c7;
            color: #92400e;
        }

        .status-fix {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 13px;
            border-radius: 12px;
            background: #DCE7F3;
            color: #1D5FD6;
            font-size: 12px;
            font-weight: 850;
            text-decoration: none;
        }

        .top-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 15px;
            font-weight: 850;
            text-decoration: none;
        }

        .top-action.primary {
            background: linear-gradient(135deg, #1D5FD6, #2BA7D8);
            color: #fff;
        }

        .top-action.secondary {
            border: 1px solid #B7CCE6;
            background: #F4F8FC;
            color: #1D5FD6;
        }

        .top-action.dark {
            background: #111827;
            color: #fff;
        }
    </style>
</head>

<body class="min-h-screen text-slate-900">
    <main class="summary-shell">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="summary-eyebrow text-sky-100/80">Mission Selesai</p>
                <h1 class="summary-title mt-2 text-3xl md:text-4xl text-white">Review hasil pengerjaan</h1>
                <p class="mt-2 max-w-3xl text-sky-100/80">{{ $pesan }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('student.start.question', ['challenge_id' => $challengeResult->challenge_id]) }}" class="top-action primary">Coba Lagi</a>
                <a href="{{ route('student.mission.index') }}" class="top-action secondary">Kembali ke Mission</a>
            </div>
        </div>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="summary-card summary-stat">
                <p class="summary-label">Total Poin</p>
                <p class="summary-value">{{ number_format($challengeResult->total_score) }}</p>
            </div>
            <div class="summary-card summary-stat">
                <p class="summary-label">Total EXP</p>
                <p class="summary-value">{{ number_format($challengeResult->total_exp) }}</p>
            </div>
            <div class="summary-card summary-stat">
                <p class="summary-label">Jumlah Soal Selesai</p>
                <p class="summary-value">{{ $challengeResult->correct_answers + $challengeResult->wrong_answers }} / {{ $totalQuestions }}</p>
            </div>
            <div class="summary-card summary-stat">
                <p class="summary-label">Hint Digunakan</p>
                <p class="summary-value">{{ $usedHelpCount }}</p>
                <p class="summary-note">Hint membantu memahami arah penyelesaian soal.</p>
            </div>
            <div class="summary-card summary-stat">
                <p class="summary-label">Attempt Mission</p>
                <p class="summary-value">{{ $challengeResult->attempt_number }}</p>
                <p class="summary-note">Ini percobaan pengerjaan mission ke-{{ $challengeResult->attempt_number }}. Jawaban belum tepat: {{ $wrongAnswerAttemptCount }} kali.</p>
            </div>
            <div class="summary-card summary-stat">
                <p class="summary-label">Durasi</p>
                <p class="summary-value text-2xl md:text-3xl">{{ $durationText }}</p>
            </div>
        </section>

        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_360px]">
            <section class="summary-card p-5 md:p-6">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="summary-eyebrow">Daftar Pembahasan</p>
                        <h2 class="summary-title mt-1 text-2xl">Review setiap soal</h2>
                    </div>
                    <span class="status-pill {{ $isPerfect ? 'status-good' : 'status-help' }}">
                        {{ $isPerfect ? 'Mission selesai sempurna' : 'Mission selesai' }}
                    </span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-sky-100">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-4 uppercase tracking-[0.18em]">Soal</th>
                                <th class="px-5 py-4 uppercase tracking-[0.18em]">Status Pengerjaan</th>
                                <th class="px-5 py-4 uppercase tracking-[0.18em]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-100 bg-white/45">
                            @foreach ($questionSummaries as $item)
                                @php
                                    $statusClass = $item['is_correct']
                                        ? ($item['used_help'] ? 'status-help' : 'status-good')
                                        : 'status-fix';
                                    $statusText = $item['is_correct']
                                        ? ($item['used_help'] ? 'Selesai dengan hint' : 'Selesai tanpa bantuan')
                                        : 'Perlu dipelajari lagi';
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-slate-700">Soal {{ $item['number'] }}</td>
                                    <td class="px-5 py-4"><span class="status-pill {{ $statusClass }}">{{ $statusText }}</span></td>
                                    <td class="px-5 py-4">
                                        <a class="action-link" href="{{ route('student.review', ['challenge' => $challengeResult->challenge_id, 'attempt' => $challengeResult->attempt_number]) }}#question-{{ $item['question_id'] }}">
                                            Lihat Pembahasan
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="grid gap-4">
                <section class="summary-card p-5">
                    <p class="summary-eyebrow">Mission</p>
                    <h3 class="summary-title mt-1 text-2xl">{{ $missionName }}</h3>
                    <p class="summary-note">Section: {{ $sectionName }}</p>
                </section>

                <section class="summary-card p-5">
                    <p class="summary-eyebrow">Hasil</p>
                    <h3 class="summary-title mt-1 text-2xl">{{ $challengeResult->correct_answers }} benar / {{ $challengeResult->wrong_answers }} belum tepat</h3>
                    <p class="summary-note">{{ $usedHelpCount }} soal memakai hint, {{ $wrongAnswerAttemptCount }} kali jawaban belum tepat. Buka pembahasan untuk memperkuat konsep CT.</p>
                </section>

                <section class="summary-card p-5">
                    <p class="summary-eyebrow">Langkah Berikutnya</p>
                    <p class="summary-note text-base">Baca pembahasan, pahami jawaban benar, lalu coba lagi jika masih ada soal yang belum tepat.</p>
                    <a href="{{ route('student.review', ['challenge' => $challengeResult->challenge_id, 'attempt' => $challengeResult->attempt_number]) }}" class="top-action dark mt-4 w-full">
                        Lihat Pembahasan dan Jawaban
                    </a>
                </section>
            </aside>
        </div>
    </main>
</body>

</html>
