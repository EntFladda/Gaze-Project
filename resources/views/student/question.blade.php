<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge Question</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="{{ asset('favicon-ctg.png') }}">
    
    <!-- ONNX Runtime Web & MediaPipe FaceMesh for gaze tracking -->
    <script src="https://cdn.jsdelivr.net/npm/onnxruntime-web/dist/ort.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/gaze-tracker.js') }}"></script>
</head>

<body class="question-theme-root min-h-screen text-white">
    <div class="max-w-7xl mx-auto px-4 py-8 grid lg:grid-cols-[310px_1fr] gap-6 items-start">
        <!-- GAZE FOCUS MONITOR SIDEBAR -->
        <div class="gaze-monitoring-panel bg-slate-900/90 text-white rounded-3xl p-5 border border-slate-700/50 shadow-2xl backdrop-blur-md sticky top-8 flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span id="cameraActiveIndicator" class="animate-pulse absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                    </span>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-200">Gaze Monitor</h2>
                </div>
                <span id="cacheBadge" class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-400 border border-slate-700/50 text-[10px] font-semibold">ONNX Offline</span>
            </div>

            <!-- Mirrored Camera Preview Container -->
            <div id="camera-preview-container" class="relative w-36 aspect-square mx-auto bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-inner flex items-center justify-center">
                <!-- Camera is mirrored using transform: scaleX(-1) -->
                <video id="videoInput" class="w-full h-full object-cover hidden" style="transform: scaleX(-1);" autoplay muted playsinline></video>
                <canvas id="canvas" class="absolute inset-0 w-full h-full object-cover" style="transform: scaleX(-1);"></canvas>
                <div id="camera-loading" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-950 text-slate-400 text-xs">
                    <svg class="animate-spin h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mengakses Kamera...</span>
                </div>
            </div>

            <!-- Status and stats -->
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-400 font-medium">Status Fokus:</span>
                    <span id="focusStatusBadge" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">IDLE</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                    <div class="bg-slate-950/40 rounded-xl p-2 border border-slate-800/80">
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Tidak Fokus</p>
                        <p id="unfocusCountVal" class="text-base font-black text-red-400 mt-1">0 kali</p>
                    </div>
                    <div class="bg-slate-950/40 rounded-xl p-2 border border-slate-800/80">
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Tingkat Fokus</p>
                        <p id="focusPercentageVal" class="text-base font-black text-sky-400 mt-1">100%</p>
                    </div>
                </div>
            </div>

            <!-- Options -->
            <div class="flex items-center justify-between border-t border-slate-800 pt-3">
                <label class="inline-flex items-center cursor-pointer text-[10px] text-slate-400 select-none">
                    <input type="checkbox" id="showCameraToggle" onchange="document.getElementById('camera-preview-container').style.display = this.checked ? 'flex' : 'none';" class="sr-only peer" checked>
                    <div class="relative w-7 h-4 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-sky-600 peer-checked:after:bg-white"></div>
                    <span class="ms-2">Tampilkan Kamera</span>
                </label>
                <button onclick="window.tracker?.poseCalculator.recalibrate()" class="text-[10px] text-sky-400 font-extrabold hover:text-sky-300 transition uppercase tracking-wider">Kalibrasi</button>
            </div>
        </div>

        <div class="question-shell text-slate-900 rounded-3xl shadow-xl overflow-hidden w-full">
            <div class="question-hero px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="question-kicker text-sm uppercase tracking-[0.24em]">Misi Berjalan</p>
                    <h1 class="text-2xl font-bold mt-1">Jawab soal dengan teliti</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button id="theme-toggle" type="button" onclick="openThemeModal()"
                        class="question-action-btn rounded-full px-4 py-2 text-sm transition">
                        Tema
                    </button>
                    <button id="bgm-toggle" type="button" onclick="toggleBackgroundMusic()"
                        class="question-action-btn rounded-full px-4 py-2 text-sm transition">
                        Musik: On
                    </button>
                    <button onclick="openExitModal()"
                        class="question-action-btn rounded-full px-4 py-2 text-sm transition">
                        Keluar
                    </button>
                </div>
            </div>

            <div id="question-session-container">
                @include('student.partials.question_session', [
                    'question' => $question,
                    'progress' => $progress,
                    'questionNumber' => $questionNumber ?? 1,
                    'totalQuestions' => $totalQuestions ?? 1,
                ])
            </div>
        </div>
    </div>

    <div id="theme-modal" class="hidden fixed inset-0 bg-slate-950/70 px-4 z-50">
        <div class="min-h-screen flex items-center justify-center">
            <div class="theme-modal-panel max-w-3xl w-full rounded-3xl p-6">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="theme-modal-kicker">Tema</p>
                        <h2 class="text-2xl font-bold text-white mt-2">Pilih nuansa teknologi informasi</h2>
                        <p class="theme-modal-copy mt-2">Setiap tema mengubah warna, suasana, dan atmosfer belajar tanpa
                            mengganggu alur soal.</p>
                    </div>
                    <button type="button" onclick="closeThemeModal()"
                        class="theme-close-btn rounded-full h-11 w-11 text-xl font-bold">
                        x
                    </button>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button type="button" class="theme-card" data-theme="quiz-pop" onclick="applyTheme('quiz-pop')">
                        <span class="theme-card-preview theme-quiz-pop-preview"></span>
                        <span class="theme-card-name">Quiz Pop</span>
                        <span class="theme-card-copy">Warna game show, kartu besar, dan suasana playful seperti arena kuis.</span>
                    </button>

                    <button type="button" class="theme-card" data-theme="terminal-mode" onclick="applyTheme('terminal-mode')">
                        <span class="theme-card-preview theme-terminal-mode-preview"></span>
                        <span class="theme-card-name">Terminal Mode</span>
                        <span class="theme-card-copy">Monokrom hijau-hitam seperti command center.</span>
                    </button>

                    <button type="button" class="theme-card" data-theme="data-stream" onclick="applyTheme('data-stream')">
                        <span class="theme-card-preview theme-data-stream-preview"></span>
                        <span class="theme-card-name">Data Stream</span>
                        <span class="theme-card-copy">Aliran data biru-teal yang terang dan responsif.</span>
                    </button>

                    <button type="button" class="theme-card" data-theme="cloud-ops" onclick="applyTheme('cloud-ops')">
                        <span class="theme-card-preview theme-cloud-ops-preview"></span>
                        <span class="theme-card-name">Cloud Ops</span>
                        <span class="theme-card-copy">Nuansa dashboard infrastruktur yang bersih.</span>
                    </button>

                    <button type="button" class="theme-card" data-theme="ai-core" onclick="applyTheme('ai-core')">
                        <span class="theme-card-preview theme-ai-core-preview"></span>
                        <span class="theme-card-name">AI Core</span>
                        <span class="theme-card-copy">Gradien cerdas dengan sorotan amber-cyan modern.</span>
                    </button>

                    <button type="button" class="theme-card" data-theme="night-shift" onclick="applyTheme('night-shift')">
                        <span class="theme-card-preview theme-night-shift-preview"></span>
                        <span class="theme-card-name">Night Shift</span>
                        <span class="theme-card-copy">Mode malam biru tua untuk sesi belajar panjang.</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="exit-modal" class="hidden fixed inset-0 bg-slate-950/70 px-4">
        <div class="min-h-screen flex items-center justify-center">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
                <h3 class="text-xl font-bold text-slate-900">Keluar dari challenge?</h3>
                <p class="text-slate-600 mt-2">Progress attempt yang sedang berjalan akan dihapus.</p>
                <div class="flex gap-3 mt-6">
                    <button onclick="closeExitModal()"
                        class="flex-1 rounded-2xl border border-slate-300 px-4 py-3 font-semibold text-slate-700">
                        Batal
                    </button>
                    <button onclick="confirmExit()"
                        class="flex-1 rounded-2xl bg-red-600 text-white px-4 py-3 font-semibold">
                        Ya, keluar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="answer-confirm-modal" class="hidden fixed inset-0 z-[70] bg-slate-950/70 px-4">
        <div class="min-h-screen flex items-center justify-center">
            <div class="answer-confirm-panel w-full max-w-md rounded-3xl p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="answer-confirm-icon">✓</div>
                    <div>
                        <p class="answer-confirm-kicker">Konfirmasi Jawaban</p>
                        <h3 class="answer-confirm-title">Kunci jawaban?</h3>
                        <p id="answer-confirm-message" class="answer-confirm-copy">
                            Setelah dikirim, jawaban akan tercatat.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closeAnswerConfirm(false)" class="answer-confirm-cancel">
                        Batal
                    </button>
                    <button type="button" onclick="closeAnswerConfirm(true)" class="answer-confirm-submit">
                        Ya, kunci
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- MODAL WARNING: TIDAK FOKUS -->
    <div id="unfocus-modal" class="hidden fixed inset-0 z-[100] bg-slate-950/85 px-4 flex items-center justify-center backdrop-blur-md">
        <div class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-8 shadow-2xl border border-red-200 transform scale-100 transition-all duration-300">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 text-red-600 mb-4 animate-bounce">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 tracking-tight">Peringatan: Tidak Fokus!</h3>
                <p class="text-slate-600 mt-3 text-sm leading-relaxed font-semibold">
                    Kamu terdeteksi sedang tidak fokus atau melihat ke luar area soal. Silakan klik tombol di bawah untuk melanjutkan misi.
                </p>
            </div>
            <div class="mt-8">
                <button onclick="resumeFromUnfocus()" class="w-full rounded-2xl bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-500 hover:to-amber-400 text-white py-4 font-bold text-md shadow-lg transition-all transform hover:-translate-y-0.5">
                    Oke, Lanjutkan Misi
                </button>
            </div>
        </div>
    </div>

    <div id="question-image-modal" class="hidden fixed inset-0 bg-slate-950/85 px-4 z-50">
        <div class="min-h-screen flex items-center justify-center">
            <div class="question-image-modal-panel relative w-full max-w-6xl rounded-3xl p-4">
                <button type="button" onclick="closeQuestionImageModal()"
                    class="question-image-modal-close absolute right-4 top-4 rounded-full h-11 w-11 text-xl font-bold">
                    x
                </button>
                <img id="question-image-modal-img" src="" alt=""
                    class="mx-auto max-h-[86vh] max-w-full rounded-2xl object-contain shadow-2xl">
            </div>
        </div>
    </div>

    <audio id="answer-correct-sound" preload="auto">
        <source src="{{ asset('sfx/correct.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="answer-wrong-sound" preload="auto">
        <source src="{{ asset('sfx/showquestion.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="background-music" preload="auto" loop>
        <source src="{{ asset('audio/menu.mp3') }}" type="audio/mpeg">
    </audio>

    <style>
        body.question-theme-root {
            --theme-body-bg:
                radial-gradient(circle at 10% 14%, rgba(255, 214, 10, 0.28), transparent 18%),
                radial-gradient(circle at 90% 8%, rgba(255, 74, 140, 0.28), transparent 18%),
                radial-gradient(circle at 82% 78%, rgba(22, 163, 163, 0.24), transparent 20%),
                linear-gradient(135deg, #0A2342 0%, #1D5FD6 42%, #2BA7D8 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(244, 248, 252, 0.99), rgba(239, 249, 255, 0.96));
            --theme-shell-border: rgba(255, 255, 255, 0.45);
            --theme-hero-bg: linear-gradient(135deg, #1D5FD6 0%, #1D5FD6 48%, #F2A93B 100%);
            --theme-kicker: rgba(255, 255, 255, 0.86);
            --theme-action-bg: rgba(255, 255, 255, 0.08);
            --theme-action-border: rgba(255, 255, 255, 0.32);
            --theme-action-hover: rgba(255, 255, 255, 0.18);
            --theme-card-bg: rgba(7, 20, 38, 0.9);
            --theme-card-border: rgba(43, 167, 216, 0.18);
            --theme-card-copy: rgba(203, 213, 225, 0.8);
            --theme-context-bg: #F4F8FC;
            --theme-context-border: #F4C45D;
            --theme-answer-bg: #ffffff;
            --theme-answer-border: #B7CCE6;
            --theme-answer-hover-bg: #E7F5F7;
            --theme-answer-hover-border: #16A3A3;
            --theme-pill-bg: #0F2F57;
            --theme-pill-text: #ffffff;
            --theme-primary: #0f172a;
            background: var(--theme-body-bg);
            transition: background 0.45s ease;
        }

        .answer-confirm-panel {
            border: 1px solid rgba(255, 195, 223, .34);
            background: linear-gradient(135deg, #0A2342 0%, #0F2F57 100%);
            color: #fff;
        }

        .answer-confirm-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 18px;
            background: linear-gradient(135deg, #F2A93B, #2BA7D8);
            color: #0A2342;
            font-size: 24px;
            font-weight: 900;
        }

        .answer-confirm-kicker {
            margin: 0;
            color: #9CB8D8;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .24em;
            text-transform: uppercase;
        }

        .answer-confirm-title {
            margin: 6px 0 0;
            color: #fff;
            font-size: 24px;
            font-weight: 900;
        }

        .answer-confirm-copy {
            margin: 8px 0 0;
            color: rgba(220, 231, 243, .78);
            line-height: 1.6;
        }

        .answer-confirm-cancel,
        .answer-confirm-submit {
            min-height: 46px;
            border-radius: 16px;
            padding: 0 18px;
            font-weight: 900;
            transition: .16s ease;
        }

        .answer-confirm-cancel {
            border: 1px solid rgba(255, 195, 223, .32);
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }

        .answer-confirm-submit {
            border: 0;
            background: linear-gradient(90deg, #1D5FD6, #2BA7D8);
            color: #fff;
        }

        .answer-confirm-cancel:hover,
        .answer-confirm-submit:hover {
            transform: translateY(-1px);
        }

        body.question-theme-root[data-theme="quiz-pop"] {
            --theme-body-bg:
                radial-gradient(circle at 10% 14%, rgba(255, 214, 10, 0.28), transparent 18%),
                radial-gradient(circle at 90% 8%, rgba(255, 74, 140, 0.28), transparent 18%),
                radial-gradient(circle at 82% 78%, rgba(22, 163, 163, 0.24), transparent 20%),
                linear-gradient(135deg, #0A2342 0%, #1D5FD6 42%, #2BA7D8 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(244, 248, 252, 0.99), rgba(239, 249, 255, 0.96));
            --theme-shell-border: rgba(255, 255, 255, 0.45);
            --theme-hero-bg: linear-gradient(135deg, #1D5FD6 0%, #1D5FD6 48%, #F2A93B 100%);
            --theme-kicker: rgba(255, 255, 255, 0.86);
            --theme-action-border: rgba(255, 255, 255, 0.32);
            --theme-action-hover: rgba(255, 255, 255, 0.18);
            --theme-context-bg: #F4F8FC;
            --theme-context-border: #F4C45D;
            --theme-answer-bg: #ffffff;
            --theme-answer-border: #B7CCE6;
            --theme-answer-hover-bg: #E7F5F7;
            --theme-answer-hover-border: #16A3A3;
            --theme-pill-bg: #0F2F57;
            --theme-pill-text: #ffffff;
        }

        body.question-theme-root[data-theme="terminal-mode"] {
            --theme-body-bg:
                radial-gradient(circle at top left, rgba(74, 222, 128, 0.14), transparent 26%),
                linear-gradient(140deg, #030805 0%, #07130a 46%, #0f2113 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(245, 255, 247, 0.98), rgba(230, 247, 233, 0.95));
            --theme-shell-border: rgba(74, 222, 128, 0.24);
            --theme-hero-bg: linear-gradient(135deg, rgba(5, 20, 9, 0.96), rgba(12, 47, 20, 0.94));
            --theme-kicker: rgba(187, 247, 208, 0.86);
            --theme-action-border: rgba(134, 239, 172, 0.22);
            --theme-action-hover: rgba(34, 197, 94, 0.18);
            --theme-card-border: rgba(74, 222, 128, 0.18);
            --theme-context-bg: #f7fff8;
            --theme-context-border: #d8f3dd;
            --theme-answer-bg: #f6fff7;
            --theme-answer-border: #d5edd9;
            --theme-answer-hover-bg: #eaf9ee;
            --theme-answer-hover-border: #22c55e;
        }

        body.question-theme-root[data-theme="data-stream"] {
            --theme-body-bg:
                radial-gradient(circle at left top, rgba(29, 95, 214, 0.16), transparent 26%),
                radial-gradient(circle at right, rgba(22, 163, 163, 0.14), transparent 24%),
                linear-gradient(140deg, #04111e 0%, #0a2c42 50%, #084c61 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(244, 253, 255, 0.98), rgba(232, 249, 251, 0.95));
            --theme-shell-border: rgba(22, 163, 163, 0.24);
            --theme-hero-bg: linear-gradient(135deg, rgba(4, 25, 46, 0.96), rgba(8, 78, 99, 0.94));
            --theme-kicker: rgba(204, 251, 241, 0.86);
            --theme-action-border: rgba(22, 163, 163, 0.22);
            --theme-action-hover: rgba(22, 163, 163, 0.2);
            --theme-card-border: rgba(22, 163, 163, 0.18);
            --theme-context-bg: #F1F7FA;
            --theme-context-border: #C4E0E8;
            --theme-answer-bg: #F1F7FA;
            --theme-answer-border: #B7D7E3;
            --theme-answer-hover-bg: #E7F5F7;
            --theme-answer-hover-border: #16A3A3;
        }

        body.question-theme-root[data-theme="cloud-ops"] {
            --theme-body-bg:
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.16), transparent 28%),
                linear-gradient(140deg, #0f172a 0%, #1e3a5f 48%, #2f5b7d 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(252, 254, 255, 0.99), rgba(237, 244, 251, 0.95));
            --theme-shell-border: rgba(43, 127, 216, 0.24);
            --theme-hero-bg: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(29, 95, 214, 0.88));
            --theme-kicker: rgba(220, 231, 243, 0.88);
            --theme-action-border: rgba(156, 184, 216, 0.24);
            --theme-action-hover: rgba(29, 95, 214, 0.18);
            --theme-card-border: rgba(43, 127, 216, 0.2);
            --theme-context-bg: #F4F8FC;
            --theme-context-border: #DCE7F3;
            --theme-answer-bg: #F4F8FC;
            --theme-answer-border: #B7CCE6;
            --theme-answer-hover-bg: #E8F0F8;
            --theme-answer-hover-border: #2B7FD8;
        }

        body.question-theme-root[data-theme="ai-core"] {
            --theme-body-bg:
                radial-gradient(circle at top left, rgba(242, 169, 59, 0.16), transparent 24%),
                radial-gradient(circle at right top, rgba(34, 211, 238, 0.18), transparent 26%),
                linear-gradient(140deg, #071426 0%, #2b1d52 50%, #0c4a6e 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(244, 248, 252, 0.98), rgba(240, 249, 255, 0.95));
            --theme-shell-border: rgba(242, 169, 59, 0.2);
            --theme-hero-bg: linear-gradient(135deg, rgba(29, 17, 57, 0.97), rgba(12, 74, 110, 0.9));
            --theme-kicker: rgba(254, 243, 199, 0.9);
            --theme-action-border: rgba(244, 196, 93, 0.2);
            --theme-action-hover: rgba(250, 204, 21, 0.16);
            --theme-card-border: rgba(242, 169, 59, 0.18);
            --theme-context-bg: #F4F8FC;
            --theme-context-border: #B7CCE6;
            --theme-answer-bg: #F4F8FC;
            --theme-answer-border: #DCE7F3;
            --theme-answer-hover-bg: #E8F0F8;
            --theme-answer-hover-border: #F2A93B;
        }

        body.question-theme-root[data-theme="night-shift"] {
            --theme-body-bg:
                radial-gradient(circle at top, rgba(99, 102, 241, 0.18), transparent 24%),
                linear-gradient(140deg, #050816 0%, #111c3b 48%, #1d2d6b 100%);
            --theme-shell-bg: linear-gradient(180deg, rgba(247, 249, 255, 0.98), rgba(232, 238, 255, 0.95));
            --theme-shell-border: rgba(129, 140, 248, 0.22);
            --theme-hero-bg: linear-gradient(135deg, rgba(8, 15, 40, 0.96), rgba(49, 46, 129, 0.9));
            --theme-kicker: rgba(224, 231, 255, 0.9);
            --theme-action-border: rgba(165, 180, 252, 0.22);
            --theme-action-hover: rgba(99, 102, 241, 0.18);
            --theme-card-border: rgba(129, 140, 248, 0.18);
            --theme-context-bg: #f6f8ff;
            --theme-context-border: #dfe6ff;
            --theme-answer-bg: #f7f8ff;
            --theme-answer-border: #d8ddfb;
            --theme-answer-hover-bg: #eef2ff;
            --theme-answer-hover-border: #6366f1;
        }

        .question-shell {
            background: var(--theme-shell-bg);
            border: 1px solid var(--theme-shell-border);
            box-shadow: 0 24px 60px rgba(3, 7, 18, 0.25);
        }

        .question-hero {
            background: var(--theme-hero-bg);
            color: #fff;
        }

        .question-kicker {
            color: var(--theme-kicker);
        }

        .question-action-btn {
            border: 1px solid var(--theme-action-border);
            background: var(--theme-action-bg);
            color: #fff;
        }

        .question-action-btn:hover {
            background: var(--theme-action-hover);
        }

        .question-zoom-image {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .question-zoom-image:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        }

        .question-image-modal-panel {
            background: rgba(248, 250, 252, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
        }

        .question-image-modal-close {
            background: rgba(15, 23, 42, 0.78);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .question-context-card {
            background: var(--theme-context-bg);
            border-color: var(--theme-context-border);
        }

        .question-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
            align-items: start;
            max-width: 1180px;
            margin: 0 auto;
        }

        .question-workspace--focused {
            grid-template-columns: minmax(0, 1fr);
            justify-content: center;
        }

        .question-workspace--focused .question-action-lane {
            width: 100%;
        }

        .question-workspace--focused .question-sticky-panel {
            position: static;
        }

        .question-sticky-panel {
            position: static;
        }

        .question-reading-lane,
        .question-action-lane {
            min-width: 0;
        }

        .question-progress-panel {
            background:
                radial-gradient(circle at 14% 20%, rgba(14, 165, 233, 0.08), transparent 28%),
                rgba(255, 255, 255, 0.72);
        }

        .question-stat-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 8px 12px;
            background: var(--theme-pill-bg);
            color: var(--theme-pill-text);
            font-weight: 700;
            white-space: nowrap;
        }

        .question-section-card {
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        }

        .question-section-head {
            display: grid;
            gap: 6px;
        }

        .question-section-kicker {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            font-weight: 800;
            color: #6A7C93;
        }

        .question-section-title {
            margin: 0;
            font-size: 24px;
            line-height: 1.28;
            font-weight: 800;
            color: #0f172a;
        }

        .question-question-text {
            font-size: 18px;
            line-height: 1.5;
            font-weight: 600;
            color: #1e293b;
        }

        .question-question-text p {
            line-height: 1.5 !important;
            color: #1e293b !important;
        }

        .question-text-card {
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(148, 163, 184, 0.18);
            padding: 14px;
        }

        .question-text-card .space-y-3 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0.6rem !important;
        }

        .question-text-card p {
            line-height: 1.6 !important;
        }

        .question-text-card .h-2 {
            height: 0.1rem !important;
        }

        .question-figure {
            display: grid;
            justify-items: center;
            gap: 10px;
        }

        .question-content-image {
            max-width: min(100%, 560px);
            max-height: 260px;
            object-fit: contain;
            background: #ffffff;
        }

        .question-image-preview-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid rgba(14, 165, 233, 0.22);
            background: #ffffff;
            color: #0369a1;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .question-answer-card {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 58px;
            border-radius: 22px;
            border: 1px solid var(--theme-answer-border);
            background: var(--theme-answer-bg);
            padding: 12px 14px;
            color: #0f172a;
            font-weight: 800;
            text-align: left;
            line-height: 1.45;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
        }

        .question-answer-letter {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 12px;
            background: #0f172a;
            color: #ffffff;
            font-size: 15px;
            font-weight: 900;
        }

        .question-answer-card.ring-2 {
            background: #D9EEF7 !important;
            border-color: #2BA7D8 !important;
        }

        .question-primary-btn,
        .question-next-btn,
        .question-help-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            border-radius: 20px;
            padding: 14px 18px;
            font-weight: 800;
            color: #ffffff;
            background: linear-gradient(135deg, #0284c7, #1D5FD6);
            box-shadow: 0 16px 34px rgba(29, 95, 214, 0.22);
        }

        .question-next-btn {
            background: linear-gradient(135deg, #0f172a, #263E5C);
        }

        .question-help-btn {
            background: linear-gradient(135deg, #d97706, #F2A93B);
        }

        .question-feedback-card {
            border-radius: 24px;
            padding: 18px;
            font-weight: 700;
            line-height: 1.7;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
        }

        @media (min-width: 1024px) {
            .question-workspace:not(.question-workspace--focused) {
                grid-template-columns: minmax(340px, 0.92fr) minmax(420px, 1.08fr);
                gap: 18px;
            }

            .question-sticky-panel {
                position: sticky;
                top: 18px;
            }

            .question-reading-lane .question-section-card,
            .question-sticky-panel .question-section-card {
                padding: 18px !important;
            }

            .question-reading-lane .space-y-5 > :not([hidden]) ~ :not([hidden]),
            .question-sticky-panel.space-y-5 > :not([hidden]) ~ :not([hidden]) {
                margin-top: 0.9rem !important;
            }

            .question-progress-panel {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .question-content-image {
                max-height: 220px;
            }
        }

        .answer-option {
            background: var(--theme-answer-bg) !important;
            border-color: var(--theme-answer-border) !important;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .answer-option:hover {
            background: var(--theme-answer-hover-bg) !important;
            border-color: var(--theme-answer-hover-border) !important;
            transform: translateY(-1px);
        }

        .theme-modal-panel {
            background: rgba(11, 19, 37, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 26px 70px rgba(2, 6, 23, 0.45);
        }

        .theme-modal-kicker {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.32em;
            text-transform: uppercase;
            color: rgba(186, 230, 253, 0.8);
        }

        .theme-modal-copy {
            color: rgba(226, 232, 240, 0.75);
            line-height: 1.7;
        }

        .theme-close-btn {
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        .theme-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 16px;
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(255, 255, 255, 0.05);
            text-align: left;
            transition: 0.2s ease;
        }

        .theme-card:hover {
            transform: translateY(-2px);
            border-color: rgba(43, 167, 216, 0.34);
            background: rgba(255, 255, 255, 0.08);
        }

        .theme-card.is-active {
            border-color: rgba(34, 211, 238, 0.58);
            box-shadow: 0 0 0 1px rgba(34, 211, 238, 0.4);
        }

        .theme-card-preview {
            display: block;
            width: 100%;
            height: 88px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .theme-card-name {
            color: #fff;
            font-size: 18px;
            font-weight: 700;
        }

        .theme-card-copy {
            color: rgba(226, 232, 240, 0.72);
            line-height: 1.6;
            font-size: 14px;
        }

        .theme-quiz-pop-preview {
            background:
                radial-gradient(circle at 18% 30%, #F4C45D 0 12%, transparent 13%),
                radial-gradient(circle at 78% 25%, #2BA7D8 0 13%, transparent 14%),
                radial-gradient(circle at 62% 76%, #22d3ee 0 14%, transparent 15%),
                linear-gradient(135deg, #1D5FD6 0%, #1D5FD6 52%, #F2A93B 100%);
        }

        .theme-terminal-mode-preview {
            background:
                radial-gradient(circle at 20% 28%, rgba(74, 222, 128, 0.35), transparent 14%),
                linear-gradient(135deg, #041007 0%, #113219 100%);
        }

        .theme-data-stream-preview {
            background:
                linear-gradient(120deg, rgba(29, 95, 214, 0.85), rgba(22, 163, 163, 0.85)),
                linear-gradient(135deg, #061b2d 0%, #0a4b64 100%);
        }

        .theme-cloud-ops-preview {
            background:
                radial-gradient(circle at 75% 25%, rgba(255, 255, 255, 0.22), transparent 20%),
                linear-gradient(135deg, #0f172a 0%, #1D5FD6 100%);
        }

        .theme-ai-core-preview {
            background:
                radial-gradient(circle at 24% 26%, rgba(242, 169, 59, 0.4), transparent 18%),
                radial-gradient(circle at 72% 68%, rgba(34, 211, 238, 0.36), transparent 20%),
                linear-gradient(135deg, #0A2342 0%, #0c4a6e 100%);
        }

        .theme-night-shift-preview {
            background:
                radial-gradient(circle at 70% 22%, rgba(165, 180, 252, 0.36), transparent 18%),
                linear-gradient(135deg, #070b1d 0%, #243b80 100%);
        }

        @media (max-width: 768px) {
            .question-workspace {
                grid-template-columns: 1fr;
            }

            .question-sticky-panel {
                position: static;
            }

            .question-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .question-hero>div:last-child {
                flex-wrap: wrap;
            }
        }
    </style>

    <script>
        let selectedMultiAnswers = [];
        let selectedSingleAnswer = null;
        let answerLocked = false;
        let helpOpened = false;
        let answerSoundsUnlocked = false;
        let backgroundMusicReady = false;
        let currentQuestionId = null;
        let currentChallengeId = null;
        let currentExitUrl = "{{ route('student.question.exit') }}";
        let nextQuestionUrl = null;
        let currentIsLastQuestion = false;
        const BGM_STORAGE_MUTED = "ctg_question_bgm_muted";
        const BGM_STORAGE_TIME = "ctg_question_bgm_time";
        const THEME_STORAGE_KEY = "ctg_question_theme";
        const BGM_DEFAULT_VOLUME = 0.2;
        const ANSWER_CORRECT_VOLUME = 0.16;
        const ANSWER_WRONG_VOLUME = 0.1;
        const DEFAULT_THEME = "cloud-ops";

        function lockAnswers() {
            answerLocked = true;
            $(".answer-option").prop("disabled", true).addClass("opacity-60 cursor-not-allowed");
            $("#essay-answer").prop("disabled", true);
            $("#submit-single-btn").prop("disabled", true).addClass("opacity-60 cursor-not-allowed");
            $("#submit-multi-btn").prop("disabled", true).addClass("opacity-60 cursor-not-allowed");
        }

        function unlockAnswers() {
            answerLocked = false;
            $(".answer-option").prop("disabled", false).removeClass("opacity-60 cursor-not-allowed");
            $("#essay-answer").prop("disabled", false);
            $("#submit-single-btn").prop("disabled", false).removeClass("opacity-60 cursor-not-allowed");
            $("#submit-multi-btn").prop("disabled", false).removeClass("opacity-60 cursor-not-allowed");
        }

        function primeAnswerSounds() {
            if (answerSoundsUnlocked) {
                return;
            }

            const audioElements = [
                document.getElementById("answer-correct-sound"),
                document.getElementById("answer-wrong-sound"),
            ].filter(Boolean);

            audioElements.forEach((audio) => {
                audio.muted = true;
                audio.currentTime = 0;

                const playPromise = audio.play();
                if (playPromise && typeof playPromise.then === "function") {
                    playPromise.then(() => {
                        audio.pause();
                        audio.currentTime = 0;
                        audio.muted = false;
                    }).catch(() => {
                        audio.muted = false;
                    });
                } else {
                    audio.pause();
                    audio.currentTime = 0;
                    audio.muted = false;
                }
            });

            answerSoundsUnlocked = true;
        }

        function playAnswerFeedbackSound(isCorrect) {
            const audio = document.getElementById(isCorrect ? "answer-correct-sound" : "answer-wrong-sound");
            if (!audio) {
                return;
            }

            audio.pause();
            audio.currentTime = 0;
            audio.volume = isCorrect ? ANSWER_CORRECT_VOLUME : ANSWER_WRONG_VOLUME;

            const playPromise = audio.play();
            if (playPromise && typeof playPromise.catch === "function") {
                playPromise.catch(() => {});
            }
        }

        function updateQuestionContextFromDom() {
            const questionSession = document.getElementById("question-session");
            if (!questionSession) {
                return;
            }

            currentQuestionId = questionSession.dataset.questionId;
            currentChallengeId = questionSession.dataset.challengeId;
            currentExitUrl = questionSession.dataset.exitUrl || "{{ route('student.question.exit') }}";
            nextQuestionUrl = questionSession.dataset.nextUrl;
            currentIsLastQuestion = questionSession.dataset.isLastQuestion === "1";
            selectedMultiAnswers = [];
            selectedSingleAnswer = null;
            answerLocked = false;
            helpOpened = false;
        }

        function syncThemeCards() {
            const activeTheme = document.body.dataset.theme || DEFAULT_THEME;
            document.querySelectorAll(".theme-card").forEach((card) => {
                card.classList.toggle("is-active", card.dataset.theme === activeTheme);
            });
        }

        function applyTheme(themeName) {
            document.body.dataset.theme = themeName;
            localStorage.setItem(THEME_STORAGE_KEY, themeName);
            syncThemeCards();
        }

        function initializeTheme() {
            const savedTheme = localStorage.getItem(THEME_STORAGE_KEY) || DEFAULT_THEME;
            applyTheme(savedTheme);
        }

        function openThemeModal() {
            $("#theme-modal").removeClass("hidden");
            syncThemeCards();
        }

        function closeThemeModal() {
            $("#theme-modal").addClass("hidden");
        }

        function getBackgroundMusic() {
            return document.getElementById("background-music");
        }

        function setBackgroundMusicButtonLabel() {
            const bgmToggle = document.getElementById("bgm-toggle");
            const backgroundMusic = getBackgroundMusic();

            if (!bgmToggle || !backgroundMusic) {
                return;
            }

            bgmToggle.textContent = `Musik: ${backgroundMusic.muted ? "Off" : "On"}`;
        }

        function saveBackgroundMusicTime() {
            const backgroundMusic = getBackgroundMusic();
            if (!backgroundMusic) {
                return;
            }

            sessionStorage.setItem(BGM_STORAGE_TIME, String(backgroundMusic.currentTime || 0));
        }

        function restoreBackgroundMusicTime(onRestored = null) {
            const backgroundMusic = getBackgroundMusic();
            if (!backgroundMusic) {
                if (typeof onRestored === "function") {
                    onRestored();
                }
                return;
            }

            const savedTime = Number(sessionStorage.getItem(BGM_STORAGE_TIME) || 0);
            if (Number.isNaN(savedTime) || savedTime <= 0) {
                if (typeof onRestored === "function") {
                    onRestored();
                }
                return;
            }

            const applySavedTime = () => {
                try {
                    const maxSeekTime = Number.isFinite(backgroundMusic.duration) && backgroundMusic.duration > 0 ?
                        Math.max(0, savedTime % backgroundMusic.duration) :
                        savedTime;
                    backgroundMusic.currentTime = maxSeekTime;
                } catch (error) {}

                if (typeof onRestored === "function") {
                    onRestored();
                }
            };

            if (backgroundMusic.readyState >= 1) {
                applySavedTime();
            } else {
                backgroundMusic.addEventListener("loadedmetadata", applySavedTime, {
                    once: true
                });
            }
        }

        function navigateWithMusicState(url) {
            saveBackgroundMusicTime();
            window.location.href = url;
        }

        function loadNextQuestionContent() {
            if (!nextQuestionUrl) {
                return;
            }

            $("#next-btn").prop("disabled", true).addClass("opacity-60 cursor-not-allowed");

            let focusData = {};
            try {
                const challengeId = currentChallengeId || '0';
                const attemptNumber = '{{ $attemptNumber ?? 1 }}';
                const key = `gaze_session_${challengeId}_attempt_${attemptNumber}`;
                const raw = localStorage.getItem(key);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    // Hitung durasi fokus = totalFrames / 30, lalu dibulatkan
                    const focusedFrames = parsed.focusedFrames || 0;
                    const focusedDuration = Math.round(focusedFrames / 30);
                    
                    focusData = {
                        focus_percentage: parsed.focusPercentage || 0,
                        unfocused_count: parsed.unfocusedCount || 0,
                        focused_duration: focusedDuration,
                        unfocused_duration: parsed.totalUnfocusDuration || 0
                    };
                }
            } catch (e) {
                console.warn('[GazeSync] Failed to read focus data', e);
            }

            $.get(nextQuestionUrl, {
                ajax: 1,
                ...focusData
            }, function(data) {
                if (data.redirect_url) {
                    navigateWithMusicState(data.redirect_url);
                    return;
                }

                if (data.html) {
                    $("#question-session-container").html(data.html);
                    updateQuestionContextFromDom();
                    window.scrollTo({
                        top: 0,
                        behavior: "smooth"
                    });
                }
            }).fail(function(xhr) {
                const redirectUrl = xhr.responseJSON && xhr.responseJSON.redirect_url;
                if (redirectUrl) {
                    navigateWithMusicState(redirectUrl);
                    return;
                }

                $("#result-box").removeClass("hidden bg-emerald-50 border-emerald-200 text-emerald-700 bg-red-50 border-red-200 text-red-700")
                    .addClass("bg-amber-50 border border-amber-200 text-amber-700")
                    .html("<strong>Belum berhasil lanjut.</strong> Coba tekan tombolnya sekali lagi.");
                $("#next-btn").prop("disabled", false).removeClass("opacity-60 cursor-not-allowed");
            });
        }

        function playBackgroundMusic() {
            const backgroundMusic = getBackgroundMusic();
            if (!backgroundMusic) {
                return;
            }

            backgroundMusic.volume = BGM_DEFAULT_VOLUME;

            if (backgroundMusic.muted) {
                setBackgroundMusicButtonLabel();
                return;
            }

            const playPromise = backgroundMusic.play();
            if (playPromise && typeof playPromise.catch === "function") {
                playPromise.catch(() => {});
            }

            setBackgroundMusicButtonLabel();
        }

        function initializeBackgroundMusic() {
            const backgroundMusic = getBackgroundMusic();
            if (!backgroundMusic) {
                return;
            }

            backgroundMusic.volume = BGM_DEFAULT_VOLUME;

            const savedMuted = localStorage.getItem(BGM_STORAGE_MUTED);
            backgroundMusic.muted = savedMuted === null ? false : savedMuted === "true";

            setBackgroundMusicButtonLabel();
            restoreBackgroundMusicTime(() => {
                playBackgroundMusic();
            });

            if (!backgroundMusicReady) {
                const unlockMusic = () => {
                    playBackgroundMusic();
                };

                document.addEventListener("click", unlockMusic, {
                    passive: true
                });
                document.addEventListener("keydown", unlockMusic);
                backgroundMusicReady = true;
            }

            backgroundMusic.addEventListener("timeupdate", saveBackgroundMusicTime);
        }

        function toggleBackgroundMusic() {
            const backgroundMusic = getBackgroundMusic();
            if (!backgroundMusic) {
                return;
            }

            backgroundMusic.muted = !backgroundMusic.muted;
            localStorage.setItem(BGM_STORAGE_MUTED, String(backgroundMusic.muted));

            if (backgroundMusic.muted) {
                backgroundMusic.pause();
            } else {
                playBackgroundMusic();
            }

            setBackgroundMusicButtonLabel();
        }

        function escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renderLearningFeedback(data) {
            const feedback = data.feedback || {};
            const steps = Array.isArray(feedback.thinking_steps) ? feedback.thinking_steps : [];
            const stepHtml = steps.length ? `
                <div class="mt-3 rounded-2xl bg-white/70 p-4">
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] opacity-75">Langkah berpikir</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        ${steps.map((step) => `<li>${escapeHtml(step)}</li>`).join("")}
                    </ul>
                </div>
            ` : "";

            return `
                <strong>${escapeHtml(feedback.title || (data.is_correct ? "Benar, bagus!" : "Belum tepat."))}</strong>
                <p class="mt-1">${escapeHtml(feedback.message || "")}</p>
                <div class="mt-3 inline-flex rounded-full bg-white/70 px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em]">
                    Konsep CT: ${escapeHtml(feedback.concept_name || "Computational Thinking")}
                </div>
                ${stepHtml}
                <p class="mt-3 text-sm font-semibold opacity-90">${escapeHtml(feedback.next_step || "")}</p>
            `;
        }

        function handleAnswerResponse(data) {
            const resultBox = $("#result-box");
            resultBox.removeClass(
                    "hidden bg-emerald-50 border-emerald-200 text-emerald-700 bg-red-50 border-red-200 text-red-700 bg-amber-50 border-amber-200 text-amber-700")
                .addClass(data.is_correct ? "bg-emerald-50 border border-emerald-200 text-emerald-700" :
                    "bg-red-50 border border-red-200 text-red-700");

            playAnswerFeedbackSound(data.is_correct);

            resultBox.html(renderLearningFeedback(data));

            if (!data.is_correct && data.has_help) {
                $("#help-actions").removeClass("hidden");
                $("#next-btn").addClass("hidden");
                unlockAnswers();
            } else {
                $("#help-actions").addClass("hidden");
                lockAnswers();
                $("#next-btn").removeClass("hidden");
            }

        }

        let pendingAnswerConfirmCallback = null;

        function confirmLockedAnswer(message = "Setelah dikirim, jawaban akan tercatat.", onConfirm = null) {
            pendingAnswerConfirmCallback = typeof onConfirm === 'function' ? onConfirm : null;
            $("#answer-confirm-message").text(message);
            $("#answer-confirm-modal").removeClass("hidden");
        }

        function closeAnswerConfirm(confirmed) {
            $("#answer-confirm-modal").addClass("hidden");

            if (confirmed && pendingAnswerConfirmCallback) {
                const callback = pendingAnswerConfirmCallback;
                pendingAnswerConfirmCallback = null;
                callback();
                return;
            }

            pendingAnswerConfirmCallback = null;
        }

        function selectSingleAnswer(button, answerId) {
            if (answerLocked) {
                return;
            }

            $(".answer-option").removeClass("ring-2 ring-sky-500");
            $(button).addClass("ring-2 ring-sky-500");
            selectedSingleAnswer = answerId;
            $("#submit-single-btn").removeClass("hidden");
        }

        function submitSelectedSingleAnswer() {
            if (answerLocked || selectedSingleAnswer === null) {
                return;
            }

            confirmLockedAnswer("Jawaban yang dipilih akan dikunci dan langsung diperiksa.", function() {
                submitSingleAnswer(selectedSingleAnswer);
            });
        }

        function submitSingleAnswer(answerId) {
            if (answerLocked) {
                return;
            }

            primeAnswerSounds();

            $.post("{{ route('student.question.check') }}", {
                _token: "{{ csrf_token() }}",
                question_id: currentQuestionId,
                selected_answer: answerId
            }, handleAnswerResponse);
        }

        function toggleMultiAnswer(button, answerId) {
            if (answerLocked) {
                return;
            }

            $(button).toggleClass("ring-2 ring-sky-500 bg-sky-50");

            const index = selectedMultiAnswers.indexOf(answerId);
            if (index > -1) {
                selectedMultiAnswers.splice(index, 1);
            } else {
                selectedMultiAnswers.push(answerId);
            }

            if (selectedMultiAnswers.length > 0) {
                $("#submit-multi-btn").removeClass("hidden");
            } else {
                $("#submit-multi-btn").addClass("hidden");
            }
        }

        function submitMultiAnswer() {
            if (answerLocked || selectedMultiAnswers.length === 0) {
                return;
            }

            confirmLockedAnswer("Pilihan yang sudah diceklis akan dikunci dan langsung diperiksa.", function() {
                primeAnswerSounds();

                $.post("{{ route('student.question.checkMultiple') }}", {
                    _token: "{{ csrf_token() }}",
                    question_id: currentQuestionId,
                    selected_answers: selectedMultiAnswers
                }, handleAnswerResponse);
            });
        }

        function submitEssayAnswer() {
            if (answerLocked) {
                return;
            }

            const answer = $("#essay-answer").val().trim();
            if (!answer) {
                $("#result-box").removeClass("hidden bg-emerald-50 border-emerald-200 text-emerald-700 bg-red-50 border-red-200 text-red-700")
                    .addClass("bg-amber-50 border border-amber-200 text-amber-700")
                    .html("<strong>Jawaban masih kosong.</strong> Tulis jawabanmu dulu, lalu kirim lagi.");
                return;
            }

            confirmLockedAnswer("Jawaban esai akan dikunci dan langsung diperiksa.", function() {
                primeAnswerSounds();

                $.post("{{ route('student.check.essay') }}", {
                    _token: "{{ csrf_token() }}",
                    question_id: currentQuestionId,
                    answer: answer
                }, handleAnswerResponse);
            });
        }

        function requestHelp() {
            $.post("{{ route('student.question.help') }}", {
                _token: "{{ csrf_token() }}",
                question_id: currentQuestionId
            }, function(data) {
                helpOpened = true;
                $("#help-text").html((data.help_text || "").replace(/\n/g, "<br>"));
                $("#help-box").removeClass("hidden");
                $("#help-actions").addClass("hidden");
                $("#result-box").removeClass("hidden")
                    .removeClass("bg-red-50 border-red-200 text-red-700")
                    .addClass("bg-amber-50 border border-amber-200 text-amber-700")
                    .html("<strong>Petunjuk terbuka.</strong> Pelan-pelan cek lagi aturan pada soal, lalu coba jawab ulang.");
                unlockAnswers();
            });
        }

        function nextQuestion() {
            loadNextQuestionContent();
        }

        function openExitModal() {
            $("#exit-modal").removeClass("hidden");
        }

        function closeExitModal() {
            $("#exit-modal").addClass("hidden");
        }

        function openQuestionImageModal(src, altText = "") {
            const modalImage = document.getElementById("question-image-modal-img");
            if (!modalImage) {
                return;
            }

            modalImage.src = src;
            modalImage.alt = altText || "Preview gambar soal";
            $("#question-image-modal").removeClass("hidden");
        }

        function closeQuestionImageModal() {
            $("#question-image-modal").addClass("hidden");
            $("#question-image-modal-img").attr("src", "");
        }

        function confirmExit() {
            saveBackgroundMusicTime();
            $.post(currentExitUrl, {
                _token: "{{ csrf_token() }}",
                challenge_id: currentChallengeId
            }, function(data) {
                window.location.href = data.redirect_url || "{{ route('student.mission.index') }}";
            });
        }

        $(document).ready(function() {
            updateQuestionContextFromDom();
            initializeTheme();
            initializeBackgroundMusic();
            $("#question-image-modal").on("click", function(event) {
                if (event.target === this) {
                    closeQuestionImageModal();
                }
            });
            window.addEventListener("beforeunload", saveBackgroundMusicTime);
            window.addEventListener("pagehide", saveBackgroundMusicTime);

            // --- Gaze Focus Tracker Boot ---
            (async function bootGazeTracker() {
                try {
                    const challengeId = currentChallengeId || '0';
                    const attemptNumber = '{{ $attemptNumber ?? 1 }}';

                    window.tracker = new GazeTracker({
                        challengeId: challengeId,
                        attemptNumber: attemptNumber,
                        videoId: 'videoInput',
                        canvasId: 'canvas',
                        statusBadgeId: 'focusStatusBadge',
                        unfocusCountId: 'unfocusCountVal',
                        focusPercentageId: 'focusPercentageVal',
                        unfocusThreshold: 10.0, // 10 detik tidak fokus = alert
                    });

                    const ok = await window.tracker.initialize();
                    if (ok) {
                        await window.tracker.start();
                        // Hide loading spinner once camera is active
                        const loadingEl = document.getElementById('camera-loading');
                        if (loadingEl) loadingEl.classList.add('hidden');
                        // Show video
                        const videoEl = document.getElementById('videoInput');
                        if (videoEl) videoEl.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error('[GazeBoot] Failed to start gaze tracker:', e);
                }
            })();
        });

        // Resume from unfocus modal
        function resumeFromUnfocus() {
            if (window.tracker) {
                window.tracker.resumeFromAlert();
            } else {
                document.getElementById('unfocus-modal')?.classList.add('hidden');
            }
        }
    </script>
</body>

</html>
