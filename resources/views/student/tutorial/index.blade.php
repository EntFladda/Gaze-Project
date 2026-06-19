@extends('student.layouts.app')

@section('content')
    <div class="tutorial-page">
        <div class="tutorial-wrap">
            <section class="tutorial-hero">
                <div>
                    <p class="tutorial-eyebrow">Tutorial Belajar</p>
                    <h1>Pahami alurnya, lalu mulai mission.</h1>
                    <p>
                        Ikuti panduan singkat ini agar tahu cara memilih mission, menjawab soal, memakai bantuan,
                        dan membaca progres belajar.
                    </p>
                </div>

                <button type="button" onclick="startStudentGuide(true)" class="tutorial-main-action">
                    Mulai Panduan Berjalan
                </button>
            </section>

            <section class="tutorial-tabs" aria-label="Menu tutorial">
                <button type="button" onclick="showTutorialTab('mission')" class="tutorial-tab is-active" data-tab="mission">
                    <span>01</span>
                    <strong>Mission</strong>
                    <small>Memilih dan memulai mission.</small>
                </button>

                <button type="button" onclick="showTutorialTab('question')" class="tutorial-tab" data-tab="question">
                    <span>02</span>
                    <strong>Soal</strong>
                    <small>Menjawab, hint, dan pembahasan.</small>
                </button>

                <button type="button" onclick="showTutorialTab('progress')" class="tutorial-tab" data-tab="progress">
                    <span>03</span>
                    <strong>Progres</strong>
                    <small>Poin, EXP, rank, dan riwayat.</small>
                </button>
            </section>

            <section id="tutorial-mission" class="tutorial-panel">
                <div class="tutorial-panel-head">
                    <div>
                        <p class="tutorial-eyebrow">Modul 1</p>
                        <h2>Mulai mission</h2>
                    </div>
                    <a href="{{ route('student.mission.index') }}" class="tutorial-secondary-action">Buka Missions</a>
                </div>

                <div class="tutorial-steps">
                    <article>
                        <span>1</span>
                        <strong>Pilih mission terbuka</strong>
                        <p>Mission yang siap dikerjakan tampil lebih terang dan bisa dipilih.</p>
                    </article>
                    <article>
                        <span>2</span>
                        <strong>Cek detail mission</strong>
                        <p>Lihat target soal, poin, EXP, dan konsep sebelum menekan start.</p>
                    </article>
                    <article>
                        <span>3</span>
                        <strong>Mulai pengerjaan</strong>
                        <p>Tekan tombol start untuk masuk ke soal pertama.</p>
                    </article>
                </div>
            </section>

            <section id="tutorial-question" class="tutorial-panel hidden">
                <div class="tutorial-panel-head">
                    <div>
                        <p class="tutorial-eyebrow">Modul 2</p>
                        <h2>Mengerjakan soal</h2>
                    </div>
                </div>

                <div class="tutorial-steps">
                    <article>
                        <span>1</span>
                        <strong>Jawab dengan yakin</strong>
                        <p>Pilih jawaban, lalu konfirmasi agar tidak asal klik.</p>
                    </article>
                    <article>
                        <span>2</span>
                        <strong>Gunakan hint jika perlu</strong>
                        <p>Hint memberi arahan saat jawaban belum tepat.</p>
                    </article>
                    <article>
                        <span>3</span>
                        <strong>Baca pembahasan</strong>
                        <p>Setelah mission selesai, buka review untuk memahami solusi.</p>
                    </article>
                </div>

                <div class="tutorial-note">
                    Poin dan EXP lebih besar jika jawaban benar tanpa bantuan. Jika sempat salah atau memakai hint,
                    nilainya tetap ada, tetapi lebih kecil.
                </div>
            </section>

            <section id="tutorial-progress" class="tutorial-panel hidden">
                <div class="tutorial-panel-head">
                    <div>
                        <p class="tutorial-eyebrow">Modul 3</p>
                        <h2>Membaca progres</h2>
                    </div>
                    <a href="{{ route('student.profile.index') }}" class="tutorial-secondary-action">Buka Dashboard</a>
                </div>

                <div class="tutorial-steps">
                    <article>
                        <span>1</span>
                        <strong>Dashboard</strong>
                        <p>Menampilkan identitas, poin, EXP, rank, dan hari beruntun.</p>
                    </article>
                    <article>
                        <span>2</span>
                        <strong>Riwayat</strong>
                        <p>Menyimpan setiap percobaan mission dan tombol review pembahasan.</p>
                    </article>
                    <article>
                        <span>3</span>
                        <strong>Leaderboard</strong>
                        <p>Menampilkan peringkat berdasarkan poin mingguan mahasiswa.</p>
                    </article>
                </div>
            </section>
        </div>
    </div>

    <style>
        .tutorial-page {
            min-height: 100vh;
            padding: 34px 18px 56px;
        }

        .tutorial-wrap {
            width: min(1060px, 100%);
            margin: 0 auto;
        }

        .tutorial-hero,
        .tutorial-tab,
        .tutorial-panel {
            border: 1px solid rgba(156, 184, 216, 0.28);
            color: #fff;
        }

        .tutorial-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            border-radius: 30px;
            background: linear-gradient(135deg, #0A2342 0%, #0F2F57 58%, #1D5FD6 100%);
            padding: 30px;
            box-shadow: 0 22px 48px rgba(0, 0, 0, 0.22);
        }

        .tutorial-eyebrow {
            margin: 0;
            color: #9CB8D8;
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.34em;
            text-transform: uppercase;
        }

        .tutorial-hero h1,
        .tutorial-panel h2 {
            margin: 10px 0 0;
            color: #fff;
            font-weight: 900;
            line-height: 1.1;
        }

        .tutorial-hero h1 {
            font-size: clamp(2rem, 4vw, 3.1rem);
        }

        .tutorial-hero p {
            max-width: 680px;
            margin: 12px 0 0;
            color: rgba(220, 231, 243, 0.82);
            line-height: 1.65;
        }

        .tutorial-main-action,
        .tutorial-secondary-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 16px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .tutorial-main-action {
            background: linear-gradient(90deg, #F2A93B, #2BA7D8);
            color: #0A2342;
            padding: 14px 18px;
        }

        .tutorial-secondary-action {
            border: 1px solid #B7CCE6;
            background: #fff;
            color: #1D5FD6;
            padding: 12px 16px;
        }

        .tutorial-tabs {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .tutorial-tab {
            display: grid;
            gap: 7px;
            border-radius: 22px;
            background: rgba(11, 47, 107, 0.92);
            padding: 18px;
            text-align: left;
            cursor: pointer;
            transition: 0.18s ease;
        }

        .tutorial-tab:hover,
        .tutorial-tab.is-active {
            border-color: #F2A93B;
            background: linear-gradient(135deg, #0F2F57, #1D5FD6);
            transform: translateY(-2px);
        }

        .tutorial-tab span {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(183, 204, 230, 0.14);
            color: #F4C45D;
            font-weight: 900;
        }

        .tutorial-tab strong {
            font-size: 1.2rem;
        }

        .tutorial-tab small {
            color: rgba(220, 231, 243, 0.72);
            line-height: 1.45;
        }

        .tutorial-panel {
            margin-top: 18px;
            border-radius: 30px;
            background: linear-gradient(135deg, #0A2342 0%, #0F2F57 100%);
            padding: 28px;
        }

        .tutorial-panel.hidden {
            display: none;
        }

        .tutorial-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .tutorial-panel h2 {
            font-size: 2rem;
        }

        .tutorial-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .tutorial-steps article {
            border: 1px solid rgba(156, 184, 216, 0.25);
            border-radius: 22px;
            background: rgba(255, 248, 248, 0.08);
            padding: 18px;
        }

        .tutorial-steps span {
            display: inline-flex;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #1D5FD6, #F2A93B);
            color: #fff;
            font-weight: 900;
        }

        .tutorial-steps strong {
            display: block;
            margin-top: 13px;
            color: #fff;
            font-size: 1.05rem;
        }

        .tutorial-steps p {
            margin: 8px 0 0;
            color: rgba(220, 231, 243, 0.76);
            line-height: 1.55;
        }

        .tutorial-note {
            margin-top: 14px;
            border: 1px solid #E7C37A;
            border-radius: 20px;
            background: #fff8db;
            color: #6f4300;
            padding: 16px 18px;
            font-weight: 800;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .tutorial-hero,
            .tutorial-panel-head {
                align-items: stretch;
                flex-direction: column;
            }

            .tutorial-tabs,
            .tutorial-steps {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function showTutorialTab(tabName) {
            document.querySelectorAll('.tutorial-panel').forEach((panel) => {
                panel.classList.add('hidden');
            });

            document.querySelectorAll('.tutorial-tab').forEach((tab) => {
                tab.classList.remove('is-active');
            });

            document.getElementById(`tutorial-${tabName}`)?.classList.remove('hidden');
            document.querySelector(`.tutorial-tab[data-tab="${tabName}"]`)?.classList.add('is-active');
        }
    </script>
@endsection
