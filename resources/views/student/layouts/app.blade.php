<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gamified Dashboard</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="{{ asset('favicon-ctg.png') }}">

    <!-- Custom CSS -->
    <style>
        :root {
            --student-bg-1: #071426;
            --student-bg-2: #0A2342;
            --student-bg-3: #0F2F57;
            --student-accent: rgba(29, 95, 214, 0.16);
            --student-accent-soft: rgba(156, 184, 216, 0.08);
            --student-panel: rgba(255, 255, 255, 0.03);
            --student-border: rgba(255, 255, 255, 0.08);
        }

        html {
            background: linear-gradient(160deg, var(--student-bg-1) 0%, var(--student-bg-2) 52%, var(--student-bg-3) 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, var(--student-accent-soft), transparent 30%),
                radial-gradient(circle at top right, var(--student-accent), transparent 24%),
                radial-gradient(circle at bottom left, rgba(22, 163, 163, 0.16), transparent 26%),
                linear-gradient(160deg, var(--student-bg-1) 0%, var(--student-bg-2) 52%, var(--student-bg-3) 100%);
            color: white;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(rgba(255, 255, 255, 0.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.018) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(circle at center, black 45%, transparent 100%);
            opacity: 0.4;
            z-index: 0;
        }

        #main-content {
            position: relative;
            z-index: 1;
        }

        .glow {
            box-shadow: 0px 10px 30px rgba(9, 4, 7, 0.28);
        }
    </style>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="flex min-h-screen overflow-x-hidden">

    <!-- Sidebar -->
    @include('student.layouts.sidebar')

    <!-- Content Wrapper -->
    <div id="main-content" class="flex-1 transition-all duration-500 p-4 md:p-8 ml-24 md:ml-64 max-w-full overflow-x-hidden">
        @yield('content')
    </div>

    <!-- Sidebar Animation -->
    <script>
        $(document).ready(function() {
            const mobileQuery = window.matchMedia('(max-width: 767px)');
            let isCollapsed = mobileQuery.matches;

            function applySidebarState() {
                $('#sidebar').removeClass('w-24 w-64 md:w-24 md:w-64');
                $('#main-content').removeClass('ml-24 ml-64 md:ml-24 md:ml-64');
                $('.sidebar-link').removeClass('justify-center justify-start md:justify-start px-3 px-6');
                $('#logout-button').removeClass('justify-center justify-start md:justify-start px-3 px-4');
                $('.sidebar-badge').removeClass('mr-0 mr-3');

                if (isCollapsed) {
                    $('#sidebar').addClass('w-24 md:w-24');
                    $('#main-content').addClass('ml-24 md:ml-24');
                    $('.sidebar-text').addClass('hidden');
                    $('#sidebar-logo').addClass('justify-center');
                    $('.sidebar-link').addClass('justify-center px-3');
                    $('#logout-button').addClass('justify-center px-3');
                    $('.sidebar-badge').addClass('mr-0');
                } else {
                    $('#sidebar').addClass('w-64 md:w-64');
                    $('#main-content').addClass('ml-64 md:ml-64');
                    $('.sidebar-text').removeClass('hidden');
                    $('#sidebar-logo').removeClass('justify-center');
                    $('.sidebar-link').addClass('justify-start px-6 md:justify-start');
                    $('#logout-button').addClass('justify-start px-4 md:justify-start');
                    $('.sidebar-badge').addClass('mr-3');
                }

                $('#toggleSidebarIcon').toggleClass('rotate-180', !isCollapsed);
            }

            applySidebarState();

            mobileQuery.addEventListener('change', function(event) {
                isCollapsed = event.matches;
                applySidebarState();
            });

            $('#toggleSidebar').click(function() {
                isCollapsed = !isCollapsed;
                applySidebarState();
            });
        });
    </script>
    <!-- Gaming-Style Loading Overlay -->
    <div id="loadingOverlay"
        class="fixed inset-0 z-50 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_30%),linear-gradient(145deg,_rgba(6,27,79,0.96),_rgba(10,35,66,0.98),_rgba(15,78,168,0.92))] flex items-center justify-center hidden flex-col animate-fadeIn px-6">
        <div
            class="w-full max-w-md rounded-[2rem] border border-sky-200/20 bg-white/5 backdrop-blur-md px-8 py-10 text-center shadow-[0_20px_60px_rgba(0,0,0,0.45)]">
            <div
                class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-[1.4rem] border border-sky-200/20 bg-white/10 shadow-[0_0_35px_rgba(59,130,246,0.18)] loading-emblem">
                <img src="{{ asset('favicon-ctg.png') }}" alt="Loading mission" class="h-10 w-10 object-contain">
            </div>

            <div class="mx-auto mb-6 flex w-fit items-center gap-3">
                <span class="loading-node loading-node-1"></span>
                <span class="loading-link"></span>
                <span class="loading-node loading-node-2"></span>
                <span class="loading-link"></span>
                <span class="loading-node loading-node-3"></span>
            </div>

            <p class="text-xs font-semibold uppercase tracking-[0.38em] text-sky-200/80">Menyiapkan Mission</p>
            <h2 class="mt-3 text-2xl font-bold text-white loading-title">Tantangan sedang dimuat</h2>
            <p class="mt-3 text-sm leading-7 text-sky-100/75">
                Sistem sedang menyiapkan halaman berikutnya agar pengalaman belajar tetap halus.
            </p>

            <div class="mt-6 h-2 overflow-hidden rounded-full bg-white/10">
                <div class="loading-bar h-full rounded-full"></div>
            </div>

            <div class="mt-5 flex items-center justify-center gap-2 text-xs text-sky-100/65">
                <span class="loading-dot"></span>
                <span>Memuat progres dan tantangan</span>
                <span class="loading-dot loading-dot-delay"></span>
            </div>
        </div>
    </div>
    @if (session('show_tutorial_popup'))
        <div id="tutorialPopup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
            <div
                class="w-full max-w-lg rounded-[2rem] border border-sky-200/20 bg-[linear-gradient(180deg,rgba(10,35,66,0.98),rgba(6,27,79,0.98))] p-8 text-white shadow-[0_24px_70px_rgba(0,0,0,0.45)] animate-pop">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-200/70">Panduan Awal</p>
                        <h2 class="mt-3 text-3xl font-bold text-yellow-300">
                            Halo {{ explode(' ', auth()->user()->name)[0] }}, siap mulai?
                        </h2>
                        <p class="mt-3 text-sm leading-7 text-sky-100/80">
                            Biar tidak bingung saat pertama masuk, kamu bisa lihat demo singkat dulu sebelum mulai mission.
                        </p>
                    </div>
                    <button onclick="closeTutorialIntro(false)"
                        class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-sm font-semibold text-sky-100 transition hover:bg-white/10">
                        Nanti
                    </button>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-left">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-200/65">Step 1</p>
                        <p class="mt-2 text-sm font-semibold text-white">Kenali menu utama</p>
                        <p class="mt-2 text-xs leading-6 text-sky-100/70">Lihat dulu dashboard, mission, riwayat, dan tutorial.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-left">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-200/65">Step 2</p>
                        <p class="mt-2 text-sm font-semibold text-white">Pahami cara main</p>
                        <p class="mt-2 text-xs leading-6 text-sky-100/70">Tutorial bantu kamu paham alur soal dan progres.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-left">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-200/65">Step 3</p>
                        <p class="mt-2 text-sm font-semibold text-white">Mulai mission</p>
                        <p class="mt-2 text-xs leading-6 text-sky-100/70">Setelah itu kamu bisa langsung coba challenge pertama.</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button onclick="closeTutorialIntro(true)"
                        class="rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-sky-100 transition hover:bg-white/10">
                        Lewati Dulu
                    </button>
                    <button type="button" onclick="startStudentGuide(true)"
                        class="w-full rounded-xl bg-gradient-to-r from-blue-500 via-sky-500 to-teal-400 px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(59,130,246,0.25)] transition hover:scale-[1.01] hover:brightness-105 sm:w-auto">
                        Mulai Panduan Berjalan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div id="studentGuideOverlay" class="student-guide-overlay hidden" aria-hidden="true">
        <div id="studentGuideSpotlight" class="student-guide-spotlight"></div>
        <div id="studentGuidePointer" class="student-guide-pointer" aria-hidden="true">
            <span class="student-guide-pointer-hand"></span>
            <span class="student-guide-pointer-ring"></span>
        </div>
        <div id="studentGuideCard" class="student-guide-card">
            <div class="student-guide-card-head">
                <div>
                    <p id="studentGuideStepLabel" class="student-guide-kicker">Panduan</p>
                    <div class="student-guide-title-row">
                        <span id="studentGuideIcon" class="student-guide-icon">&rarr;</span>
                        <h2 id="studentGuideTitle">Selamat datang di CTG</h2>
                    </div>
                </div>
                <button type="button" onclick="finishStudentGuide(true)" class="student-guide-close">&times;</button>
            </div>
            <div id="studentGuideGesture" class="student-guide-gesture" aria-hidden="true">
                <span class="guide-cursor">&#128070;</span>
                <span class="guide-pulse-ring"></span>
            </div>
            <p id="studentGuideText" class="student-guide-copy"></p>
            <div class="student-guide-progress">
                <span id="studentGuideProgressText">1/6</span>
                <div><b id="studentGuideProgressBar"></b></div>
            </div>
            <div class="student-guide-actions">
                <button type="button" id="studentGuidePrev" onclick="prevStudentGuideStep()">Sebelumnya</button>
                <button type="button" onclick="finishStudentGuide(true)">Lewati</button>
                <button type="button" id="studentGuideNext" onclick="nextStudentGuideStep()">Berikutnya</button>
            </div>
        </div>
    </div>

    <!-- Custom Animations -->
    <style>
        @keyframes pop {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .animate-pop {
            animation: pop 0.4s ease-in-out;
        }

        @keyframes spinSlow {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .animate-spin-slow {
            animation: spinSlow 3s linear infinite;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes missionPulse {
            0%,
            100% {
                transform: scale(0.92);
                opacity: 0.4;
                box-shadow: 0 0 0 rgba(43, 167, 216, 0);
            }

            50% {
                transform: scale(1.08);
                opacity: 1;
                box-shadow: 0 0 20px rgba(43, 167, 216, 0.55);
            }
        }

        @keyframes missionBar {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(220%);
            }
        }

        @keyframes emblemFloat {
            0%,
            100% {
                transform: translateY(0px) scale(1);
            }

            50% {
                transform: translateY(-4px) scale(1.03);
            }
        }

        @keyframes titleGlow {
            0%,
            100% {
                text-shadow: 0 0 0 rgba(255, 255, 255, 0);
            }

            50% {
                text-shadow: 0 0 18px rgba(29, 95, 214, 0.22);
            }
        }

        @keyframes blinkDot {
            0%,
            100% {
                opacity: 0.25;
                transform: scale(0.9);
            }

            50% {
                opacity: 1;
                transform: scale(1.15);
            }
        }

        .loading-node {
            width: 20px;
            height: 20px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #2BA7D8, #2BA7D8);
            border: 2px solid rgba(255, 255, 255, 0.55);
            display: inline-block;
            animation: missionPulse 1.4s ease-in-out infinite;
        }

        .loading-node-2 {
            animation-delay: 0.2s;
        }

        .loading-node-3 {
            animation-delay: 0.4s;
        }

        .loading-link {
            width: 56px;
            height: 4px;
            border-radius: 9999px;
            background: linear-gradient(90deg, rgba(43, 167, 216, 0.25), rgba(255, 255, 255, 0.35), rgba(43, 167, 216, 0.25));
        }

        .loading-bar {
            width: 45%;
            background: linear-gradient(90deg, #2BA7D8, #2BA7D8, #F4C45D);
            animation: missionBar 1.6s ease-in-out infinite;
        }

        .loading-emblem {
            animation: emblemFloat 2s ease-in-out infinite;
        }

        .loading-title {
            animation: titleGlow 1.8s ease-in-out infinite;
        }

        .loading-dot {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: #9CB8D8;
            display: inline-block;
            animation: blinkDot 1.2s ease-in-out infinite;
        }

        .loading-dot-delay {
            animation-delay: 0.3s;
        }

        .student-guide-overlay {
            position: fixed;
            inset: 0;
            z-index: 80;
            background: rgba(7, 2, 6, 0.68);
            pointer-events: auto;
        }

        .student-guide-spotlight {
            display: none;
        }

        .student-guide-card {
            position: fixed;
            width: min(360px, calc(100vw - 32px));
            z-index: 95;
            max-height: none;
            overflow: visible;
            border-radius: 18px;
            border: 1px solid rgba(183, 204, 230, 0.42);
            background: linear-gradient(160deg, rgba(248, 251, 255, 0.99), rgba(220, 231, 243, 0.99));
            color: #0A2342;
            padding: 13px 14px 12px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.36);
            transition: left 0.25s ease, bottom 0.25s ease, width 0.25s ease;
        }

        .student-guide-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .student-guide-kicker {
            margin: 0;
            color: #1D5FD6;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.24em;
            text-transform: uppercase;
        }

        .student-guide-title-row {
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 10px;
            margin-top: 6px;
        }

        .student-guide-icon {
            display: inline-flex;
            width: 30px;
            height: 30px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: linear-gradient(135deg, #F4C45D, #9CB8D8);
            box-shadow: 0 14px 28px rgba(29, 95, 214, 0.2);
            animation: guideIconFloat 1.8s ease-in-out infinite;
        }

        .student-guide-card h2 {
            margin: 0;
            color: #0A2342;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.2;
        }

        .student-guide-close {
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 999px;
            background: #DCE7F3;
            color: #15509A;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .student-guide-copy {
            margin: 7px 0 0;
            color: #53657A;
            line-height: 1.3;
            font-weight: 700;
            font-size: 13px;
        }

        .student-guide-gesture {
            display: none;
        }

        .guide-cursor {
            position: absolute;
            left: 22px;
            top: 2px;
            font-size: 24px;
            animation: guideCursorTap 1.55s ease-in-out infinite;
            z-index: 2;
        }

        .guide-pulse-ring {
            position: absolute;
            left: 62px;
            top: 8px;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            border: 3px solid #1D5FD6;
            animation: guidePulseRing 1.55s ease-out infinite;
        }

        .student-guide-pointer {
            position: fixed;
            z-index: 96;
            width: 34px;
            height: 34px;
            pointer-events: none;
            transform: translate(8px, -6px);
            transition: left 0.25s ease, top 0.25s ease;
            filter: drop-shadow(0 0 10px rgba(244, 196, 93, 0.7));
        }

        .student-guide-pointer-hand {
            position: absolute;
            left: 3px;
            top: 0;
            width: 19px;
            height: 25px;
            background: linear-gradient(135deg, #F4C45D, #2BA7D8);
            clip-path: polygon(8% 0%, 8% 82%, 32% 64%, 48% 98%, 65% 90%, 50% 58%, 88% 58%);
            filter: drop-shadow(0 6px 10px rgba(43, 167, 216, 0.35));
            transform: rotate(-18deg);
            animation: guidePointerTap 1.15s ease-in-out infinite;
        }

        .student-guide-pointer-ring {
            display: none;
        }

        .student-guide-progress {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            align-items: center;
            margin-top: 11px;
            color: #1D5FD6;
            font-size: 12px;
            font-weight: 900;
        }

        .student-guide-progress div {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #DCE7F3;
        }

        .student-guide-progress b {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #1D5FD6, #F2A93B);
            transition: width 0.25s ease;
        }

        .student-guide-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: nowrap;
            margin-top: 12px;
        }

        .student-guide-actions button {
            border: 0;
            border-radius: 14px;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            white-space: nowrap;
        }

        .student-guide-actions button:first-child,
        .student-guide-actions button:nth-child(2) {
            background: #fff;
            color: #123A68;
            border: 1px solid #B7CCE6;
        }

        .student-guide-actions button:last-child {
            background: linear-gradient(90deg, #1D5FD6, #F2A93B);
            color: #fff;
        }

        .student-guide-highlight {
            z-index: 81 !important;
        }

        .student-guide-highlight:not(.fixed) {
            position: relative;
        }

        @media (max-width: 768px) {
            .student-guide-card {
                left: 14px !important;
                right: 14px !important;
                bottom: 14px !important;
                top: auto !important;
                width: auto !important;
                max-height: none;
                padding: 14px;
            }

            .student-guide-card h2 {
                font-size: 16px;
            }

            .student-guide-actions {
                justify-content: space-between;
            }

            .student-guide-actions button {
                padding: 8px 10px;
                font-size: 12px;
            }
        }

        @keyframes guideSmallGlow {
            0%, 100% { opacity: .9; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.12); }
        }

        @keyframes guidePointerTap {
            0%, 100% { transform: rotate(-18deg) translate(0, 0) scale(1); }
            45% { transform: rotate(-18deg) translate(9px, 9px) scale(0.94); }
            62% { transform: rotate(-18deg) translate(9px, 9px) scale(0.86); }
        }

        @keyframes guidePointerRing {
            0% { opacity: 0; transform: scale(0.3); }
            48% { opacity: 1; transform: scale(0.8); }
            100% { opacity: 0; transform: scale(2.6); }
        }

        @keyframes guideIconFloat {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-4px) rotate(3deg); }
        }

        @keyframes guideCursorTap {
            0%, 100% { transform: translate(0, 0) scale(1); }
            45% { transform: translate(42px, 5px) scale(0.92); }
            60% { transform: translate(42px, 5px) scale(0.82); }
        }

        @keyframes guidePulseRing {
            0% { opacity: 0; transform: scale(0.4); }
            45% { opacity: 0; transform: scale(0.4); }
            60% { opacity: 1; transform: scale(0.8); }
            100% { opacity: 0; transform: scale(2.4); }
        }
    </style>

    <script>
        const STUDENT_GUIDE_PENDING_KEY = "ctg_student_guide_pending_index";
        const studentGuideSteps = [{
                target: '[data-guide="profile"]',
                icon: 'DB',
                title: 'Dashboard mahasiswa',
                text: 'Lihat ringkasan identitas, progres, poin, dan peringkat kamu di sini.'
            },
            {
                target: '[data-guide="mission"]',
                icon: 'MS',
                title: 'Masuk ke Missions',
                text: 'Klik Missions untuk mulai belajar lewat mission.',
                url: "{{ route('student.mission.index') }}"
            },
            {
                target: '[data-guide="mission-page-intro"]',
                icon: 'MAP',
                title: 'Halaman Missions',
                text: 'Semua mission ada di area ini.',
                url: "{{ route('student.mission.index') }}"
            },
            {
                target: '[data-guide="mission-card"]',
                icon: 'GO',
                title: 'Pilih mission yang terbuka',
                text: 'Klik mission yang statusnya terbuka.',
                url: "{{ route('student.mission.index') }}"
            },
            {
                target: '#missionStartBtn',
                icon: 'CT',
                title: 'Detail mission',
                text: 'Cek ringkasan mission, lalu mulai dari tombol START.',
                url: "{{ route('student.mission.index') }}",
                openMissionDetail: true,
                scrollMissionBoxToTarget: true
            },
            {
                target: '#missionStartBtn',
                icon: 'GO',
                title: 'Mulai mengerjakan',
                text: 'Klik START untuk masuk ke soal.',
                url: "{{ route('student.mission.index') }}",
                openMissionDetail: true,
                scrollMissionBoxToTarget: true
            },
            {
                target: '[data-guide="history"]',
                icon: 'RH',
                title: 'Riwayat pengerjaan',
                text: 'Lihat hasil dan review pengerjaan.'
            },
            {
                target: '[data-guide="tutorial"]',
                icon: 'TD',
                title: 'Tutorial bisa dibuka ulang',
                text: 'Buka lagi panduan kapan saja.'
            }
        ];
        let studentGuideIndex = 0;
        let studentGuideActiveElement = null;

        function dismissTutorialIntro() {
            fetch("{{ route('student.dismiss.tutorial') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "X-Requested-With": "XMLHttpRequest"
                }
            }).catch(() => {});
        }

        function closeTutorialIntro(remember = false) {
            const popup = document.getElementById('tutorialPopup');
            if (popup) popup.remove();
            if (remember) dismissTutorialIntro();
        }

        function startStudentGuide(rememberIntro = false) {
            closeTutorialIntro(false);
            if (rememberIntro) dismissTutorialIntro();
            studentGuideIndex = 0;
            document.getElementById('studentGuideOverlay').classList.remove('hidden');
            document.getElementById('studentGuideOverlay').setAttribute('aria-hidden', 'false');
            goToStudentGuideStep(0);
        }

        function clearStudentGuideHighlight() {
            if (studentGuideActiveElement) {
                studentGuideActiveElement.classList.remove('student-guide-highlight');
            }
            studentGuideActiveElement = null;
        }

        function normalizeGuideUrl(url) {
            if (!url) return '';
            const anchor = document.createElement('a');
            anchor.href = url;
            return anchor.pathname.replace(/\/$/, '');
        }

        function currentGuidePath() {
            return window.location.pathname.replace(/\/$/, '');
        }

        function visibleGuideTarget(selector) {
            const element = document.querySelector(selector);
            if (!element) return null;
            const rect = element.getBoundingClientRect();
            if (rect.width <= 0 || rect.height <= 0) return null;
            return element;
        }

        function isGuideTargetComfortablyVisible(element) {
            if (!element) return true;
            const rect = element.getBoundingClientRect();
            const topSafe = 90;
            const bottomSafe = 190;
            return rect.top >= topSafe && rect.bottom <= (window.innerHeight - bottomSafe);
        }

        function scrollGuideTargetIntoView(element) {
            if (!element) return false;
            if (isGuideTargetComfortablyVisible(element)) return false;
            element.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            return true;
        }

        function waitForGuideTarget(selector, callback, attempt = 0) {
            const element = visibleGuideTarget(selector);
            if (element && !element.classList.contains('hidden')) {
                callback();
                return;
            }

            if (attempt >= 12) {
                callback();
                return;
            }

            setTimeout(() => waitForGuideTarget(selector, callback, attempt + 1), 250);
        }

        function findNextRenderableGuideIndex(fromIndex, direction = 1) {
            let index = fromIndex;
            while (index >= 0 && index < studentGuideSteps.length) {
                const step = studentGuideSteps[index];
                if (step.url && normalizeGuideUrl(step.url) !== currentGuidePath()) {
                    return index;
                }

                if (step.openMissionDetail) {
                    return index;
                }

                if (visibleGuideTarget(step.target)) {
                    return index;
                }

                index += direction;
            }
            return Math.max(0, Math.min(studentGuideSteps.length - 1, fromIndex));
        }

        function scrollMissionPanelToGuideTarget(selector) {
            const missionBox = document.getElementById('missionBox');
            const target = document.querySelector(selector);
            if (!missionBox || !target || missionBox.classList.contains('hidden')) return false;

            const boxRect = missionBox.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const targetOffset = targetRect.top - boxRect.top + missionBox.scrollTop;
            const centeredOffset = targetOffset - (missionBox.clientHeight / 2) + (target.offsetHeight / 2);
            missionBox.scrollTo({ top: Math.max(0, centeredOffset), behavior: 'smooth' });
            return true;
        }

        function openMissionDetailForGuide(callback) {
            const step = studentGuideSteps[studentGuideIndex];
            const missionBox = document.getElementById('missionBox');
            const finish = () => {
                if (step.scrollMissionBoxToTarget) {
                    scrollMissionPanelToGuideTarget(step.target);
                    setTimeout(callback, 420);
                    return;
                }
                callback();
            };

            if (missionBox && !missionBox.classList.contains('hidden')) {
                finish();
                return;
            }

            const missionCard = document.querySelector('[data-guide="mission-card"]');
            if (!missionCard) {
                finish();
                return;
            }

            scrollGuideTargetIntoView(missionCard);
            setTimeout(() => {
                missionCard.click();
                waitForGuideTarget('#missionBox', finish);
            }, 350);
        }

        function goToStudentGuideStep(index, direction = 1) {
            studentGuideIndex = findNextRenderableGuideIndex(index, direction);
            const step = studentGuideSteps[studentGuideIndex];

            if (step.url && normalizeGuideUrl(step.url) !== currentGuidePath()) {
                sessionStorage.setItem(STUDENT_GUIDE_PENDING_KEY, String(studentGuideIndex));
                window.location.href = step.url;
                return;
            }

            document.getElementById('studentGuideOverlay').classList.remove('hidden');
            document.getElementById('studentGuideOverlay').setAttribute('aria-hidden', 'false');

            if (step.openMissionDetail) {
                openMissionDetailForGuide(renderStudentGuideStep);
                return;
            }

            renderStudentGuideStep();
        }

        function renderStudentGuideStep(skipScroll = false) {
            clearStudentGuideHighlight();
            const step = studentGuideSteps[studentGuideIndex];
            const target = visibleGuideTarget(step.target) || document.getElementById('main-content');
            if (!skipScroll && scrollGuideTargetIntoView(target)) {
                setTimeout(() => renderStudentGuideStep(true), 450);
                return;
            }

            const rect = target.getBoundingClientRect();
            const spotlight = document.getElementById('studentGuideSpotlight');
            const card = document.getElementById('studentGuideCard');

            target.classList.add('student-guide-highlight');
            studentGuideActiveElement = target;

            const rawTargetX = rect.left + (rect.width / 2);
            const rawTargetY = rect.top + (rect.height / 2);
            const targetX = Math.min(window.innerWidth - 34, Math.max(34, rawTargetX));
            const targetY = Math.min(window.innerHeight - 180, Math.max(34, rawTargetY));

            spotlight.style.left = `${targetX}px`;
            spotlight.style.top = `${targetY}px`;
            spotlight.style.width = '46px';
            spotlight.style.height = '46px';

            const pointer = document.getElementById('studentGuidePointer');
            pointer.style.left = `${targetX}px`;
            pointer.style.top = `${targetY}px`;

            // Model ringkas: layar tetap gelap, hanya cursor kecil + glow yang menunjuk target.
            const sidebar = document.getElementById('sidebar');
            const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : null;
            const mainLeft = window.innerWidth > 768 && sidebarRect && sidebarRect.width > 80 ? sidebarRect.right : 0;
            const cardWidth = Math.min(360, window.innerWidth - mainLeft - 36);
            const left = mainLeft + Math.max(18, ((window.innerWidth - mainLeft) - cardWidth) / 2);

            card.style.width = `${Math.max(280, cardWidth)}px`;
            card.style.left = `${left}px`;
            card.style.right = 'auto';

            const cardHeight = Math.min(260, card.offsetHeight || 230);
            if (targetY > window.innerHeight - cardHeight - 90) {
                card.style.top = '18px';
                card.style.bottom = 'auto';
            } else {
                card.style.top = 'auto';
                card.style.bottom = '18px';
            }

            document.getElementById('studentGuideStepLabel').textContent = `Panduan ${studentGuideIndex + 1}`;
            document.getElementById('studentGuideIcon').textContent = step.icon || 'CT';
            document.getElementById('studentGuideTitle').textContent = step.title;
            document.getElementById('studentGuideText').textContent = step.text;
            document.getElementById('studentGuideProgressText').textContent = `${studentGuideIndex + 1}/${studentGuideSteps.length}`;
            document.getElementById('studentGuideProgressBar').style.width = `${((studentGuideIndex + 1) / studentGuideSteps.length) * 100}%`;
            document.getElementById('studentGuidePrev').disabled = studentGuideIndex === 0;
            document.getElementById('studentGuidePrev').style.opacity = studentGuideIndex === 0 ? '.45' : '1';
            document.getElementById('studentGuideNext').textContent = studentGuideIndex === studentGuideSteps.length - 1 ? 'Selesai' : 'Berikutnya';
        }

        function nextStudentGuideStep() {
            if (studentGuideIndex >= studentGuideSteps.length - 1) {
                finishStudentGuide(true);
                return;
            }
            goToStudentGuideStep(studentGuideIndex + 1, 1);
        }

        function prevStudentGuideStep() {
            if (studentGuideIndex <= 0) return;
            goToStudentGuideStep(studentGuideIndex - 1, -1);
        }

        function finishStudentGuide(remember = false) {
            clearStudentGuideHighlight();
            const overlay = document.getElementById('studentGuideOverlay');
            overlay.classList.add('hidden');
            overlay.setAttribute('aria-hidden', 'true');
            sessionStorage.removeItem(STUDENT_GUIDE_PENDING_KEY);
            if (remember) dismissTutorialIntro();
        }

        window.startStudentGuide = startStudentGuide;
        window.addEventListener('resize', () => {
            const overlay = document.getElementById('studentGuideOverlay');
            if (overlay && !overlay.classList.contains('hidden')) {
                renderStudentGuideStep();
            }
        });

        window.addEventListener("DOMContentLoaded", () => {
            const pendingGuideIndex = sessionStorage.getItem(STUDENT_GUIDE_PENDING_KEY);
            if (pendingGuideIndex !== null) {
                sessionStorage.removeItem(STUDENT_GUIDE_PENDING_KEY);
                const parsedIndex = Number(pendingGuideIndex);
                if (!Number.isNaN(parsedIndex)) {
                    setTimeout(() => goToStudentGuideStep(parsedIndex), 250);
                }
            }

            const links = document.querySelectorAll("a:not([target='_blank']):not([href^='#'])");
            const overlay = document.getElementById("loadingOverlay");

            links.forEach(link => {
                link.addEventListener("click", () => {
                    const href = link.getAttribute("href");
                    if (href && !href.startsWith("javascript:") && !link.classList.contains('no-loading')) {
                        overlay.classList.remove("hidden");
                    }
                });
            });

            document.addEventListener('error', function(event) {
                const image = event.target;
                if (image?.tagName === 'IMG' && image.src.includes('/storage/profile_photos/')) {
                    const fallbackSrc = "{{ asset('storage/profile_photos/default-3d.svg') }}";
                    if (!image.src.includes('default-3d.svg')) {
                        image.src = fallbackSrc;
                    }
                }
            }, true);
        });
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('SW registration failed: ', err);
                });
            });
        }
    </script>
</body>

</html>
