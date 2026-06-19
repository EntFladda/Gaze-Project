@extends('student.layouts.app')

@section('content')
    <div class="history-page">
        <div class="history-wrap">
            <section class="history-hero">
                <div>
                    <p class="history-eyebrow">Riwayat Belajar</p>
                    <h1>Jejak pengerjaan mission</h1>
                    <p class="history-subtitle">
                        Lihat kembali hasil setiap percobaan, poin, EXP, dan review pembahasan yang sudah dikerjakan.
                    </p>
                </div>
            </section>

            <section class="history-stats" aria-label="Ringkasan riwayat belajar">
                <article class="history-stat-card">
                    <span>Mission Selesai</span>
                    <strong>{{ $summary['completed_missions'] }}</strong>
                </article>
                <article class="history-stat-card">
                    <span>Total Percobaan</span>
                    <strong>{{ $summary['total_attempts'] }}</strong>
                </article>
                <article class="history-stat-card">
                    <span>Poin Tertinggi</span>
                    <strong>{{ number_format($summary['best_score']) }}</strong>
                </article>
                <article class="history-stat-card">
                    <span>Rata-rata Poin</span>
                    <strong>{{ number_format($summary['average_score'], 1) }}</strong>
                </article>
            </section>

            <div class="history-grid">
                <section class="history-panel history-panel-main">
                    <div class="history-section-head">
                        <div>
                            <p class="history-eyebrow">Daftar Percobaan</p>
                            <h2>Riwayat pengerjaan</h2>
                        </div>
                        <span class="history-chip">Terbaru</span>
                    </div>

                    <div class="history-attempt-list">
                        @forelse ($results as $result)
                            @php
                                $totalQuestions = $result->challenge?->questions_count ?? 0;
                                $donePercent = $totalQuestions > 0 ? min(100, round(($result->correct_answers / $totalQuestions) * 100)) : 0;
                            @endphp

                            <article class="history-attempt-card">
                                <div class="history-attempt-main">
                                    <div class="history-attempt-icon">
                                        {{ str_pad($result->attempt_number, 2, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div>
                                        <p class="history-attempt-meta">
                                            Section {{ $result->challenge?->section?->order ?? '-' }} - {{ $result->challenge?->section?->name ?? 'Section' }}
                                        </p>
                                        <h3>{{ $result->challenge?->title ?? 'Mission' }}</h3>
                                        <p class="history-attempt-time">
                                            {{ optional($result->ended_at)->format('d M Y, H:i') }} - {{ $totalQuestions }} soal
                                        </p>
                                    </div>
                                </div>

                                <div class="history-attempt-progress" aria-label="Progress pengerjaan">
                                    <div class="history-progress-top">
                                        <span>Tuntas</span>
                                        <strong>{{ $result->correct_answers }}/{{ $totalQuestions }} soal</strong>
                                    </div>
                                    <div class="history-progress-track">
                                        <span style="width: {{ $donePercent }}%"></span>
                                    </div>
                                </div>

                                <div class="history-attempt-score">
                                    <span class="history-score-pill history-score-point">{{ $result->total_score }} Poin</span>
                                    <span class="history-score-pill history-score-exp">{{ $result->total_exp }} EXP</span>
                                </div>

                                <a href="{{ route('student.review', ['challenge' => $result->challenge_id, 'attempt' => $result->attempt_number]) }}"
                                    class="history-action">
                                    Lihat review
                                </a>
                            </article>
                        @empty
                            <div class="history-empty">
                                <strong>Belum ada riwayat.</strong>
                                <span>Selesaikan satu mission dulu, nanti hasilnya muncul di sini.</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="history-pagination">
                        <p>Halaman {{ $results->currentPage() }} dari {{ $results->lastPage() }}</p>

                        <nav class="history-pagination-nav" aria-label="Navigasi halaman riwayat">
                            <a href="{{ $results->onFirstPage() ? '#' : $results->previousPageUrl() }}"
                                class="history-page-link history-page-arrow {{ $results->onFirstPage() ? 'is-disabled' : '' }}"
                                @if ($results->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                                Sebelumnya
                            </a>

                            <div class="history-page-numbers">
                                @foreach ($results->getUrlRange(1, max(1, $results->lastPage())) as $page => $url)
                                    <a href="{{ $url }}"
                                        class="history-page-link {{ $page === $results->currentPage() ? 'is-active' : '' }}"
                                        aria-current="{{ $page === $results->currentPage() ? 'page' : 'false' }}">
                                        {{ $page }}
                                    </a>
                                @endforeach
                            </div>

                            <a href="{{ $results->hasMorePages() ? $results->nextPageUrl() : '#' }}"
                                class="history-page-link history-page-arrow {{ $results->hasMorePages() ? '' : 'is-disabled' }}"
                                @if (! $results->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
                                Berikutnya
                            </a>
                        </nav>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <style>
        .history-page {
            min-height: 100vh;
            padding: 36px 18px 56px;
        }

        .history-wrap {
            width: min(1180px, 100%);
            margin: 0 auto;
        }

        .history-hero,
        .history-panel,
        .history-stat-card {
            border: 1px solid rgba(156, 184, 216, 0.25);
            color: #fff;
        }

        .history-hero {
            border-radius: 30px;
            background: linear-gradient(135deg, #0A2342 0%, #0F2F57 58%, #1D5FD6 100%);
            padding: 34px;
            box-shadow: 0 22px 48px rgba(0, 0, 0, 0.22);
        }

        .history-eyebrow {
            margin: 0;
            color: #9CB8D8;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.34em;
            text-transform: uppercase;
        }

        .history-hero h1,
        .history-section-head h2 {
            margin: 10px 0 0;
            color: #fff;
            font-weight: 900;
            line-height: 1.1;
        }

        .history-hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
        }

        .history-subtitle {
            max-width: 720px;
            margin: 14px 0 0;
            color: rgba(220, 231, 243, 0.82);
            font-size: 1rem;
            line-height: 1.7;
        }

        .history-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 18px;
        }

        .history-stat-card {
            border-radius: 22px;
            background: rgba(11, 47, 107, 0.92);
            padding: 22px;
        }

        .history-stat-card span,
        .history-attempt-card small {
            display: block;
            color: rgba(220, 231, 243, 0.68);
        }

        .history-stat-card span {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        .history-stat-card strong {
            display: block;
            margin-top: 10px;
            color: #fff;
            font-size: 2rem;
            line-height: 1;
        }

        .history-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
            margin-top: 18px;
            align-items: start;
        }

        .history-panel {
            border-radius: 28px;
            background: linear-gradient(135deg, #0A2342 0%, #0F2F57 100%);
            padding: 24px;
        }

        .history-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .history-section-head h2 {
            font-size: 1.8rem;
        }

        .history-chip {
            border-radius: 999px;
            background: rgba(183, 204, 230, 0.12);
            color: #F4C45D;
            padding: 10px 15px;
            font-size: 0.85rem;
            font-weight: 800;
        }

        .history-attempt-list {
            display: grid;
            gap: 14px;
        }

        .history-attempt-card {
            display: grid;
            grid-template-columns: minmax(260px, 1.35fr) minmax(180px, 0.8fr) auto auto;
            gap: 16px;
            align-items: center;
            border: 1px solid rgba(156, 184, 216, 0.25);
            border-radius: 22px;
            background: rgba(255, 248, 248, 0.08);
            padding: 18px;
        }

        .history-attempt-main {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .history-attempt-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            border-radius: 18px;
            background: linear-gradient(135deg, #1D5FD6, #F2A93B);
            color: #fff;
            font-weight: 900;
            box-shadow: 0 12px 24px rgba(29, 95, 214, 0.22);
        }

        .history-attempt-meta,
        .history-attempt-time {
            margin: 0;
            color: rgba(220, 231, 243, 0.7);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .history-attempt-card h3 {
            margin: 5px 0;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .history-progress-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: rgba(220, 231, 243, 0.72);
            font-size: 0.85rem;
            font-weight: 800;
        }

        .history-progress-top strong {
            color: #7cf7a6;
        }

        .history-progress-track {
            height: 9px;
            margin-top: 9px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
        }

        .history-progress-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #1D5FD6, #F2A93B);
        }

        .history-attempt-score {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .history-score-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 9px 12px;
            font-size: 0.86rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .history-score-point {
            background: #fff3bf;
            color: #8a4b00;
        }

        .history-score-exp {
            background: #D9EEF7;
            color: #0369a1;
        }

        .history-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(90deg, #1D5FD6, #2BA7D8);
            color: #fff;
            padding: 12px 16px;
            font-size: 0.9rem;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .history-action:hover {
            filter: brightness(1.04);
        }

        .history-empty {
            display: grid;
            gap: 6px;
            border-radius: 18px;
            background: rgba(183, 204, 230, 0.12);
            padding: 22px;
            text-align: center;
            color: rgba(220, 231, 243, 0.72);
        }

        .history-empty strong {
            color: #fff;
        }

        .history-empty-small {
            text-align: left;
        }

        .history-pagination {
            margin-top: 20px;
        }

        .history-pagination p {
            margin: 0 0 12px;
            color: rgba(220, 231, 243, 0.72);
            font-weight: 800;
        }

        .history-pagination-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .history-page-numbers {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .history-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            border: 1px solid rgba(156, 184, 216, 0.25);
            border-radius: 14px;
            background: rgba(255, 248, 248, 0.08);
            color: #fff;
            padding: 10px 13px;
            font-weight: 900;
            text-decoration: none;
        }

        .history-page-link.is-active {
            border-color: transparent;
            background: #1D5FD6;
            color: #fff;
        }

        .history-page-link.is-disabled {
            pointer-events: none;
            opacity: 0.45;
        }

        .history-page-arrow {
            min-width: 110px;
        }

        @media (max-width: 1180px) {
            .history-grid {
                grid-template-columns: 1fr;
            }

            .history-attempt-card {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .history-attempt-score {
                justify-content: flex-start;
            }

            .history-action {
                width: 100%;
            }
        }

        @media (max-width: 860px) {
            .history-page {
                padding-inline: 12px;
            }

            .history-hero,
            .history-panel {
                border-radius: 24px;
                padding: 22px;
            }

            .history-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .history-stats {
                grid-template-columns: 1fr;
            }

            .history-section-head,
            .history-pagination-nav {
                align-items: stretch;
                flex-direction: column;
            }

            .history-page-numbers {
                justify-content: center;
            }

            .history-page-arrow {
                width: 100%;
            }
        }
    </style>
@endsection
