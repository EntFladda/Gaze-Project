@extends('student.layouts.app')

@section('content')
    @php
        $guideMissionTagged = false;
    @endphp

    <div class="animate-fadeIn">
        <div class="max-w-6xl mx-auto px-2 sm:px-4 py-8 md:py-10 relative overflow-x-hidden">
            <audio id="hover-sound" src="{{ asset('sfx/hover.mp3') }}"></audio>
            <audio id="click-star-sound" src="{{ asset('sfx/click.mp3') }}"></audio>
            <audio id="show-detail-sound" src="{{ asset('sfx/showdetailbox.mp3') }}"></audio>

            <div class="flex flex-col xl:flex-row justify-between items-start gap-6">
                <div data-guide="mission-page-intro">
                    <h2 id="welcome-text"
                        class="text-3xl md:text-4xl font-extrabold text-yellow-500 drop-shadow-lg tracking-wide uppercase animate-fadeIn relative inline-block transition-transform duration-500 typing-effect">
                        <span class="text-shadow-glow">
                            Haloo <span id="username">{{ explode(' ', $student->user->name)[0] }} &#128075;</span>
                        </span>
                    </h2>
                    <p class="mt-3 text-sky-50/85 max-w-2xl">
                        Pilih mission yang sedang terbuka. Mission yang sudah selesai berwarna hijau, mission aktif lebih terang,
                        dan mission yang terkunci akan redup.
                    </p>
                </div>

                <div class="flex flex-wrap gap-4 w-full xl:w-auto">
                    <div onclick="openRankModal()"
                        class="cursor-pointer bg-gradient-to-r from-[#1D5FD6] to-[#2B7FD8] text-white p-4 rounded-xl shadow-3xl flex items-center w-full sm:w-64 border-2 border-sky-200/60 transition transform hover:scale-105 hover:shadow-sky-300/40 duration-300 group relative overflow-hidden">
                        <span class="text-3xl mr-4 drop-shadow-md animate-pulse">&#127942;</span>

                        <div>
                            <p class="text-md font-bold group-hover:text-yellow-300 transition">
                                {{ $student->current_rank?->name }}</p>

                            @php
                                $minExp = $student->current_rank?->min_exp ?? 0;
                                $maxExp = $student->current_rank?->max_exp ?? 100;
                                $currentExp = $student->exp;
                                $expInCurrentRank = $currentExp - $minExp;
                                $range = $maxExp - $minExp;
                                $progressPercent = $range > 0 ? ($expInCurrentRank / $range) * 100 : 0;
                            @endphp
                            <div class="w-40 bg-gray-300 rounded-full h-2 mt-1 relative overflow-hidden">
                                <div class="bg-yellow-400 h-2 rounded-full absolute transition-all duration-700 animate-glow"
                                    style="width: {{ $progressPercent }}%">
                                </div>
                            </div>
                            <p class="text-xs mt-1 text-gray-200">{{ $student->exp }} /
                                {{ $student->current_rank?->max_exp }} XP</p>
                        </div>
                    </div>

                    <div
                        class="p-4 rounded-lg shadow-2xl flex items-center w-full sm:w-32 justify-center border-2 transition-all {{ $student->streak >= 1 ? 'bg-orange-500 border-yellow-300 animate-fire' : 'bg-[#0F2F57] border-[#2B7FD8]' }}">
                        <span
                            class="text-2xl mr-2 {{ $student->streak >= 1 ? 'text-yellow-300 glow-fire' : 'text-gray-400' }}">
                            &#128293;
                        </span>
                        <div>
                            <p
                                class="text-sm font-semibold text-center {{ $student->streak >= 1 ? 'text-white' : 'text-gray-400' }}">
                                Hari Beruntun</p>
                            <p
                                class="text-lg font-bold text-center {{ $student->streak >= 1 ? 'text-yellow-200' : 'text-gray-500' }}">
                                {{ $student->streak }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="p-4 rounded-lg shadow-2xl flex items-center w-full sm:w-56 bg-gradient-to-r from-[#1D5FD6] to-[#2BA7D8] border-2 border-sky-200/50 transition transform hover:scale-105 hover:shadow-sky-400/30 hover-sfx mt-6">
                <span class="text-3xl mr-3 drop-shadow-md text-white">&#127919;</span>
                <div>
                    <p class="text-md font-bold text-white">Total Poin</p>
                    <p class="text-xl font-extrabold text-yellow-200">{{ number_format($student->total_score ?? 0) }} poin
                    </p>
                </div>
            </div>

            @foreach ($sections as $section)
                <div class="mt-12">
                    <div
                        @if ($loop->first) data-guide="mission-section" @endif
                        class="bg-[#0A2342] text-white p-4 rounded-lg shadow-lg border-2 {{ $section->is_unlocked ? 'border-sky-200/15' : 'border-yellow-700 opacity-80' }}">
                        <p class="text-base md:text-lg font-bold uppercase text-white text-center w-full leading-relaxed">
                            Section {{ $section->order }} -
                            <span class="text-yellow-300 font-semibold normal-case">{{ $section->name }}</span>
                            @unless ($section->is_unlocked)
                                <span class="block md:inline md:ml-3 text-sm text-yellow-200 normal-case">Terkunci sampai semua mission section sebelumnya selesai</span>
                            @endunless
                        </p>
                    </div>

                    <div class="mt-8">
                        @if ($section->is_unlocked)
                            <div class="relative max-w-4xl mx-auto min-h-[260px] sm:min-h-[260px]">
                                @php
                                    $challengeCount = $section->challenges->count();
                                @endphp

                                @if ($challengeCount > 1)
                                    <div class="hidden sm:block absolute left-1/2 top-10 bottom-10 w-1 -translate-x-1/2 bg-amber-300/40 rounded-full"></div>
                                @endif

                                @foreach ($section->challenges as $index => $challenge)
                                    @php
                                        $left = $index % 2 === 0;
                                        $top = 20 + $index * 95;
                                        $canOpen = $challenge->is_unlocked && $challenge->has_questions;
                                        $shouldGuideMission = ! $guideMissionTagged && $canOpen;
                                        if ($shouldGuideMission) {
                                            $guideMissionTagged = true;
                                        }
                                        $cardClasses = $challenge->is_completed
                                            ? 'from-[#2B7FD8] to-[#2BA7D8] border-sky-200 shadow-sky-400/35'
                                            : ($canOpen
                                                ? 'from-[#1D5FD6] to-[#2B7FD8] border-sky-100 hover:border-yellow-300 shadow-sky-500/25'
                                                : 'from-[#243b53] to-[#0A2342] border-[#2B7FD8] opacity-60 cursor-not-allowed');
                                        $label = ! $challenge->has_questions
                                            ? 'Belum ada soal'
                                            : ($challenge->is_completed ? 'Selesai' : ($challenge->is_unlocked ? 'Siap Dikerjakan' : 'Terkunci'));
                                        $icon = $challenge->is_completed ? '&#10003;' : ($canOpen ? '&#9733;' : '&#9632;');
                                    @endphp

                                    <div class="relative flex justify-center sm:{{ $left ? 'justify-start' : 'justify-end' }} mb-10">
                                        <div class="w-full sm:w-[46%]">
                                            <button
                                                @if ($canOpen) onclick="playClickStarSound(); showChallenge('{{ route('student.mission.showChallenge', $challenge->id) }}')" @endif
                                                class="w-full rounded-3xl border-4 bg-gradient-to-r {{ $cardClasses }} text-white p-5 shadow-3xl transition duration-300 {{ $canOpen ? 'hover:scale-105' : 'cursor-not-allowed opacity-70' }}">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="text-left">
                                                        <p class="text-xs uppercase tracking-[0.25em] text-white/80">Mission {{ $index + 1 }}</p>
                                                        <h3 class="text-xl font-bold mt-2">{{ $challenge->title }}</h3>
                                                        <p class="text-sm mt-3 text-white/85">{{ $challenge->questions_count }} soal</p>
                                                    </div>
                                                    <span class="text-3xl">{!! $icon !!}</span>
                                                </div>
                                                <div class="mt-4 text-xs font-semibold uppercase tracking-[0.25em] text-white/90">
                                                    <span
                                                        @if ($shouldGuideMission)
                                                            data-guide="mission-card"
                                                            data-guide-open-url="{{ route('student.mission.showChallenge', $challenge->id) }}"
                                                        @endif
                                                        class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1">
                                                        {{ $label }}
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="w-full flex justify-center mt-8">
                                    <div
                                        class="w-52 h-52 bg-[#0F2F57] text-sky-50 rounded-full border-4 border-[#2B7FD8] flex flex-col items-center justify-center shadow-3xl text-center px-4">
                                    <span class="text-5xl">&#128274;</span>
                                    <p class="text-sm mt-3">Selesaikan semua mission pada section sebelumnya untuk membuka section ini</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div id="missionBox"
                data-guide="mission-detail"
                class="fixed top-6 left-4 right-4 md:left-auto md:right-8 w-auto md:w-[340px] bg-gradient-to-br from-[#0A2342] to-[#071426] p-5 rounded-2xl shadow-[0_0_20px_rgba(59,130,246,0.28)] hidden opacity-0 transition-all duration-500 transform scale-95 border border-sky-200/45 flex flex-col items-center text-white animate-3dFlip backdrop-blur-md z-40 overflow-y-auto">

                <button onclick="playClickStarSound(); closeMissionBox()"
                    class="hover-sfx absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:scale-110 transition transform hover:rotate-12 shadow-md border border-red-700">
                    &times;
                </button>

                <h3 class="text-xl font-extrabold text-center tracking-wide uppercase text-yellow-300 leading-tight px-8" id="missionTitle">
                    Mission</h3>

                <div class="w-16 h-1 bg-yellow-400 rounded-full my-2"></div>

                <p class="text-sm text-gray-300 mt-1">Attempt: <span id="attemptNumber"
                        class="font-bold text-yellow-200"></span></p>

                <p class="text-sm text-gray-300 mt-1">Target: <span id="questionCount"
                        class="font-bold text-yellow-200"></span> soal</p>

                <div
                    class="hover-sfx flex items-center mt-3 bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg shadow-inner border border-yellow-300 hover:shadow-yellow-300 transition">
                    <span class="text-lg font-extrabold mr-2">&#127942;</span>
                    <p class="text-lg font-bold">+<span id="challengeExp"></span> XP</p>
                </div>

                <div
                    class="hover-sfx flex items-center mt-2 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-inner border border-sky-200/60 hover:shadow-sky-400/40 transition">
                    <span class="text-lg font-extrabold mr-2">&#128290;</span>
                    <p class="text-lg font-bold"><span id="challengeScore"></span> Poin</p>
                </div>

                <button id="missionStartBtn" onclick="playClickStarSound(); startChallenge()"
                    data-guide="mission-start"
                    class="hover-sfx bg-gradient-to-r from-yellow-500 to-orange-500 text-black px-7 py-3 mt-5 rounded-lg shadow-lg hover:shadow-yellow-300 transition transform hover:scale-105 border border-yellow-400 font-bold text-base">
                    START
                </button>
                <button id="missionReviewBtn"
                    class="hidden hover-sfx bg-gradient-to-r from-[#1D5FD6] to-[#2BA7D8] text-white px-7 py-3 mt-3 rounded-lg shadow-lg hover:shadow-sky-400/35 transition transform hover:scale-105 border border-sky-200/60 font-bold text-base">
                    REVIEW
                </button>
            </div>

        <div id="rankModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-[#0A2342] border border-sky-200/25 p-6 rounded-lg shadow-xl w-full max-w-lg">
                <h2 class="text-2xl font-bold text-yellow-400 mb-4 text-center">Daftar Rank & EXP</h2>
                <ul class="space-y-3 max-h-72 overflow-y-auto">
                    @foreach ($allRanks as $rank)
                        <li class="border-b border-gray-700 pb-2">
                            <p class="text-white font-semibold">{{ $rank->name }}</p>
                            <p class="text-sm text-sky-200/70">EXP: {{ $rank->min_exp }} - {{ $rank->max_exp }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="text-center mt-6">
                    <button onclick="closeRankModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-shadow-glow {
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8), 0 0 20px rgba(255, 165, 0, 0.6);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes typing {
            from {
                width: 0;
            }

            to {
                width: 35%;
            }
        }

        .typing-effect {
            overflow: hidden;
            white-space: nowrap;
            border-right: 2px solid white;
            display: inline-block;
            animation: typing 2s steps(16, end);
        }

        @media (max-width: 767px) {
            .typing-effect {
                white-space: normal;
                border-right: none;
                animation: none;
            }
        }

        @keyframes fireGlow {
            0% {
                text-shadow: 0 0 10px rgba(255, 165, 0, 0.7);
            }

            50% {
                text-shadow: 0 0 20px rgba(255, 140, 0, 1);
            }

            100% {
                text-shadow: 0 0 10px rgba(255, 165, 0, 0.7);
            }
        }

        .glow-fire {
            animation: fireGlow 1s infinite alternate;
        }

        @keyframes fireMove {
            0% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-3px) scale(1.05);
            }

            100% {
                transform: translateY(0) scale(1);
            }
        }

        .animate-fire {
            animation: fireMove 0.8s infinite alternate ease-in-out;
        }

        @keyframes flip {
            0% {
                transform: rotateY(90deg) scale(0.8);
                opacity: 0;
            }

            100% {
                transform: rotateY(0deg) scale(1);
                opacity: 1;
            }
        }

        .animate-3dFlip {
            animation: flip 0.5s ease-in-out;
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 5px rgba(255, 255, 0, 0.5);
            }

            50% {
                box-shadow: 0 0 20px rgba(255, 255, 0, 1);
            }

            100% {
                box-shadow: 0 0 5px rgba(255, 255, 0, 0.5);
            }
        }

        .animate-glow {
            animation: glow 1.5s infinite alternate;
        }

        .shadow-3xl {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .glow {
            transition: all 0.3s ease-in-out;
        }

        .glow:hover {
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.7);
        }

        #missionBox {
            max-height: min(640px, calc(100vh - 3rem));
        }

        #missionBox::-webkit-scrollbar {
            width: 8px;
        }

        #missionBox::-webkit-scrollbar-thumb {
            background: rgba(43, 167, 216, 0.32);
            border-radius: 999px;
        }
    </style>

    <script>
        function openRankModal() {
            document.getElementById('rankModal').classList.remove('hidden');
        }

        function closeRankModal() {
            document.getElementById('rankModal').classList.add('hidden');
        }

        function playShowDetailSound() {
            const audio = document.getElementById("show-detail-sound");
            if (audio) {
                audio.currentTime = 0;
                audio.play();
            }
        }

        function playClickStarSound() {
            const audio = document.getElementById("click-star-sound");
            if (audio) {
                audio.currentTime = 0;
                audio.play();
            }
        }

        function playHoverSound() {
            const audio = document.getElementById("hover-sound");
            if (audio) {
                audio.currentTime = 0;
                audio.play();
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const hoverElements = document.querySelectorAll(".hover-sfx");

            hoverElements.forEach(el => {
                el.addEventListener("mouseenter", playHoverSound);
            });
        });

        function rebindHoverSound() {
            const hoverElements = document.querySelectorAll(".hover-sfx");

            hoverElements.forEach(el => {
                el.removeEventListener("mouseenter", playHoverSound);
                el.addEventListener("mouseenter", playHoverSound);
            });
        }

        function showChallenge(url) {
            fetch(url)
                .then(async response => {
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || "Challenge tidak bisa dibuka.");
                    }

                    return data;
                })
                .then(data => {
                    document.getElementById('missionTitle').innerText = data.title;
                    document.getElementById('questionCount').innerText = data.question_count;
                    document.getElementById('challengeExp').innerText = data.exp;
                    document.getElementById('challengeScore').innerText = data.score;
                    document.getElementById('attemptNumber').innerText = data.attempt_number || 0;
                    document.getElementById('missionBox').setAttribute("data-challenge-id", data.id);

                    const btn = document.getElementById('missionStartBtn');
                    const reviewBtn = document.getElementById('missionReviewBtn');

                    if (data.is_perfect) {
                        btn.classList.add('hidden');
                        reviewBtn.classList.remove('hidden');
                        reviewBtn.onclick = function() {
                            window.location.href = `/student/review/${data.id}/${data.attempt_number}`;
                        };
                    } else {
                        btn.classList.remove('hidden');
                        reviewBtn.classList.add('hidden');
                    }

                    let box = document.getElementById('missionBox');
                    box.classList.remove('hidden');
                    setTimeout(() => {
                        box.classList.remove('opacity-0', 'scale-95');
                        box.classList.add('opacity-100', 'scale-100');
                        playShowDetailSound();
                        rebindHoverSound();
                    }, 50);
                })
                .catch(error => {
                    alert(error.message);
                });
        }

        function closeMissionBox() {
            let box = document.getElementById('missionBox');
            box.classList.add('opacity-0', 'scale-95');

            setTimeout(() => {
                box.classList.add('hidden');
            }, 300);
        }

        function startChallenge() {
            let challengeId = document.getElementById("missionBox").getAttribute("data-challenge-id");

            $.ajax({
                url: "{{ route('student.start.challenge') }}",
                type: "POST",
                data: {
                    challenge_id: challengeId,
                    _token: "{{ csrf_token() }}"
                },
                success: function() {
                    window.location.href = `/student/question/${challengeId}`;
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || "Gagal memulai mission.";
                    showMissionNotice(message);
                }
            });
        }
    </script>
@endsection
