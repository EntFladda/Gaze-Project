@extends('student.layouts.app')

@section('content')
    @php
        $achievementPreviewCount = 8;
        $hasMoreAchievements = $allAchievements->count() > $achievementPreviewCount;
        $missionProgress = $totalChallengesCount > 0 ? round(($completedChallengesCount / $totalChallengesCount) * 100) : 0;
        $profilePhoto = $user->profile_photo;
        $normalizedProfilePhoto = strtolower((string) $profilePhoto);
        $hasCustomProfilePhoto = filled($profilePhoto)
            && ! str_contains($normalizedProfilePhoto, 'default');
        $studentInitial = strtoupper(mb_substr($user->name ?: 'M', 0, 1));
    @endphp

    <style>
        .student-dashboard {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 18px 52px;
            color: #0A2342;
        }

        .sd-grid {
            display: grid;
            gap: 18px;
        }

        .sd-main-grid {
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, .85fr);
        }

        .sd-card {
            background: #F4F8FC;
            border: 1px solid rgba(29, 95, 214, .25);
            border-radius: 24px;
            box-shadow: 0 18px 42px rgba(34, 7, 20, .16);
        }

        .sd-card-dark {
            background: #0A2342;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .sd-section {
            padding: 24px;
        }

        .sd-eyebrow {
            margin: 0 0 8px;
            color: #1D5FD6;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .26em;
            text-transform: uppercase;
        }

        .sd-title {
            margin: 0;
            font-size: clamp(26px, 3vw, 42px);
            font-weight: 900;
            line-height: 1.05;
        }

        .sd-subtitle {
            margin: 8px 0 0;
            color: #6A7C93;
            font-size: 15px;
        }

        .sd-profile {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: 22px;
        }

        .sd-avatar {
            width: 112px;
            height: 112px;
            border-radius: 999px;
            border: 5px solid #B7CCE6;
            object-fit: cover;
            background: #fff;
            box-shadow: 0 12px 28px rgba(29, 95, 214, .24);
        }

        .sd-avatar-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 112px;
            color: #fff;
            font-size: 42px;
            font-weight: 900;
            background:
                radial-gradient(circle at 35% 22%, rgba(255, 230, 109, .95), transparent 28%),
                linear-gradient(135deg, #1D5FD6 0%, #123A68 55%, #071426 100%);
        }

        .sd-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1D5FD6, #2BA7D8);
            color: #fff;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 14px 30px rgba(29, 95, 214, .24);
        }

        .sd-profile-actions {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            align-items: center;
            padding-top: 18px;
            border-top: 1px solid #DCE7F3;
        }

        .sd-button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 16px;
            border: 1px solid #B7CCE6;
            background: #F4F8FC;
            color: #1D5FD6;
            font-weight: 850;
            text-decoration: none;
        }

        .sd-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .sd-info-box {
            min-width: 0;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid #B7CCE6;
            background: rgba(255, 255, 255, .7);
        }

        .sd-label {
            margin: 0;
            color: #1D5FD6;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .sd-value {
            margin: 6px 0 0;
            color: #0A2342;
            font-size: 18px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }

        .sd-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sd-stat {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid #B7CCE6;
            background: #F4F8FC;
        }

        .sd-stat strong {
            display: block;
            margin-top: 8px;
            font-size: 27px;
            line-height: 1;
        }

        .sd-progress-track {
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: #DCE7F3;
        }

        .sd-progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #1D5FD6, #F2A93B);
        }

        .sd-panel-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .sd-soft-box {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid #B7CCE6;
            background: #F4F8FC;
        }

        .sd-dark-muted {
            color: rgba(183, 204, 230, .78);
        }

        .sd-rankbar {
            margin: 18px 0;
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
        }

        .sd-rankbar span {
            display: block;
            height: 100%;
            width: var(--progress, 0%);
            border-radius: inherit;
            background: linear-gradient(90deg, #2BA7D8, #2BA7D8);
        }

        .sd-leaderboard {
            margin-top: 18px;
            display: grid;
            gap: 10px;
        }

        .sd-rank-row {
            display: grid;
            grid-template-columns: 34px 44px minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .1);
        }

        .sd-rank-row.is-current {
            color: #0A2342;
            border-color: rgba(255, 232, 91, .85);
            background: #DCE7F3;
        }

        .sd-rank-row img {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            object-fit: cover;
            background: #fff;
        }

        .sd-achievements {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .sd-achievement {
            border: 1px solid #B7CCE6;
            border-radius: 18px;
            background: #fff;
            padding: 14px 10px;
            text-align: center;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sd-achievement:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(29, 95, 214, .14);
        }

        .sd-achievement.is-locked {
            opacity: .48;
            filter: grayscale(1);
            background: #E8F0F8;
        }

        .sd-achievement img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            margin: 0 auto;
        }

        .sd-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(0, 0, 0, .55);
        }

        .sd-modal.is-open {
            display: flex;
        }

        @media (max-width: 980px) {
            .sd-main-grid,
            .sd-profile {
                grid-template-columns: 1fr;
            }

            .sd-stats,
            .sd-mini-grid,
            .sd-achievements {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .student-dashboard {
                padding-inline: 12px;
            }

            .sd-section {
                padding: 18px;
            }

            .sd-stats,
            .sd-mini-grid,
            .sd-panel-grid,
            .sd-achievements {
                grid-template-columns: 1fr;
            }

            .sd-avatar {
                width: 92px;
                height: 92px;
                flex-basis: 92px;
                font-size: 34px;
            }
        }
    </style>

    <main class="student-dashboard">
        @if (session('success'))
            <div class="sd-card sd-section" style="margin-bottom: 18px; border-color: #bbf7d0; background: #f0fdf4; color: #166534; box-shadow: none;">
                {{ session('success') }}
            </div>
        @endif

        <div class="sd-grid sd-main-grid">
            <section class="sd-card sd-section">
                <div class="sd-profile">
                    @if ($hasCustomProfilePhoto)
                        <img class="sd-avatar" src="{{ asset('storage/' . $profilePhoto) }}" alt="Foto profil {{ $user->name }}">
                    @else
                        <div class="sd-avatar sd-avatar-fallback" aria-label="Foto profil belum diatur">
                            {{ $studentInitial }}
                        </div>
                    @endif

                    <div>
                        <p class="sd-eyebrow">Dashboard Mahasiswa</p>
                        <h1 class="sd-title">{{ $user->name }}</h1>
                        <p class="sd-subtitle">{{ $user->email }}</p>

                        <div class="sd-mini-grid" style="margin-top: 18px;">
                            <div class="sd-info-box">
                                <p class="sd-label">NIM</p>
                                <p class="sd-value">{{ $student->nim ?: '-' }}</p>
                            </div>
                            <div class="sd-info-box">
                                <p class="sd-label">Kelas</p>
                                <p class="sd-value">{{ $student->class ?: '-' }}</p>
                            </div>
                            <div class="sd-info-box">
                                <p class="sd-label">Mingguan</p>
                                <p class="sd-value">{{ $weeklyRank ? '#' . $weeklyRank : 'Belum masuk' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="sd-profile-actions">
                        <a class="sd-button-secondary" href="{{ route('student.profile.detail') }}">Detail</a>
                        <a class="sd-button" href="{{ route('student.profile.edit') }}">Edit Profil</a>
                    </div>
                </div>
            </section>

            <aside class="sd-card sd-card-dark sd-section">
                <p class="sd-eyebrow" style="color: #B7CCE6;">Peringkat dan EXP</p>
                <h2 class="sd-title" style="font-size: 30px;">{{ $currentRank?->name ?? 'Belum Ada Rank' }}</h2>
                <p class="sd-dark-muted" style="margin-top: 8px;">
                    EXP {{ number_format($student->exp) }}@if ($currentRank) / {{ number_format($currentRank->max_exp) }}@endif
                </p>

                <div class="sd-rankbar"><span style="--progress: {{ $expProgress }}%"></span></div>

                <div class="sd-panel-grid">
                    <div class="sd-soft-box" style="background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.12);">
                        <p class="sd-label" style="color:#B7CCE6;">Total Poin</p>
                        <p class="sd-value" style="color:#fff; font-size:28px;">{{ number_format($student->total_score) }}</p>
                    </div>
                    <div class="sd-soft-box" style="background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.12);">
                        <p class="sd-label" style="color:#B7CCE6;">Hari Beruntun</p>
                        <p class="sd-value" style="color:#fff; font-size:28px;">{{ $student->streak }} hari</p>
                    </div>
                </div>
            </aside>
        </div>

        <section class="sd-card sd-section" style="margin-top: 18px;">
            <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                <div>
                    <p class="sd-eyebrow">Progres Belajar</p>
                    <h2 class="sd-title" style="font-size: 30px;">Ringkasan Mission</h2>
                </div>
                <strong style="font-size: 30px; color:#1D5FD6;">{{ $missionProgress }}%</strong>
            </div>

            <div class="sd-progress-track" style="margin-top: 18px;">
                <div class="sd-progress-fill" style="width: {{ $missionProgress }}%"></div>
            </div>

            <div class="sd-stats" style="margin-top: 18px;">
                <div class="sd-stat">
                    <p class="sd-label">Mission Selesai</p>
                    <strong>{{ $completedChallengesCount }} / {{ $totalChallengesCount }}</strong>
                </div>
                <div class="sd-stat">
                    <p class="sd-label">Section Terbuka</p>
                    <strong>{{ $unlockedSectionsCount }}</strong>
                </div>
                <div class="sd-stat">
                    <p class="sd-label">Section Tuntas</p>
                    <strong>{{ $completedSectionsCount }}</strong>
                </div>
                <div class="sd-stat">
                    <p class="sd-label">Mission Aktif</p>
                    <strong style="font-size: 20px; line-height: 1.2;">{{ $currentChallenge?->title ?? 'Belum ada' }}</strong>
                </div>
            </div>
        </section>

        <div class="sd-grid sd-main-grid" style="margin-top: 18px; align-items:start;">
            <div class="sd-grid">
                <section class="sd-card sd-section">
                    <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                        <div>
                            <p class="sd-eyebrow">Pencapaian</p>
                            <h2 class="sd-title" style="font-size: 30px;">Badge Belajar</h2>
                        </div>
                        <div class="sd-info-box">
                            <p class="sd-label">Terbuka</p>
                            <p class="sd-value">{{ count($unlockedAchievementIds) }} / {{ $allAchievements->count() }}</p>
                        </div>
                    </div>

                    <div class="sd-achievements" style="margin-top: 18px;">
                        @foreach ($allAchievements as $achievement)
                            @php
                                $pivotData = $student->achievements->firstWhere('id', $achievement->id)?->pivot;
                                $isUnlocked = ! is_null($pivotData);
                                $unlockedAt = $pivotData?->unlocked_at
                                    ? \Carbon\Carbon::parse($pivotData->unlocked_at)->translatedFormat('d F Y, H:i')
                                    : null;
                            @endphp

                            <button type="button"
                                class="sd-achievement {{ $isUnlocked ? '' : 'is-locked' }} {{ $loop->index >= $achievementPreviewCount ? 'hidden extra-achievement' : '' }}"
                                onclick='showAchievement(@json(asset("storage/" . $achievement->icon)), @json($achievement->name), @json($achievement->description), {{ $isUnlocked ? "true" : "false" }}, @json($unlockedAt ?? ""))'>
                                <img src="{{ asset('storage/' . $achievement->icon) }}" alt="{{ $achievement->name }}">
                                <p style="margin: 10px 0 0; font-weight: 800; color:#0A2342;">{{ $achievement->name }}</p>
                            </button>
                        @endforeach
                    </div>

                    @if ($hasMoreAchievements)
                        <div style="margin-top: 18px; text-align:center;">
                            <button type="button" id="achievementToggleBtn" class="sd-button" style="border:0; cursor:pointer; box-shadow:none;" onclick="toggleAchievements()">
                                Lihat Semua Pencapaian
                            </button>
                        </div>
                    @endif
                </section>
            </div>

            <aside class="sd-card sd-card-dark sd-section">
                <p class="sd-eyebrow" style="color: #B7CCE6;">Papan Skor</p>
                <h2 class="sd-title" style="font-size: 30px;">Leaderboard Mingguan</h2>
                <p class="sd-dark-muted" style="margin-top: 8px;">Peringkat tampil setelah mahasiswa memperoleh poin mingguan dari mission.</p>

                <div class="sd-leaderboard">
                    @forelse ($leaderboard as $index => $entry)
                        @php
                            $isCurrentUser = (int) $entry->user_id === (int) $user->id;
                            $entryPhoto = $entry->profile_photo ?: 'profile_photos/default-3d.svg';
                        @endphp
                        <div class="sd-rank-row {{ $isCurrentUser ? 'is-current' : '' }}">
                            <strong>{{ $index + 1 }}</strong>
                            <img src="{{ asset('storage/' . $entryPhoto) }}" alt="Foto {{ $entry->name }}">
                            <div style="min-width:0;">
                                <p style="margin:0; font-weight:850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $entry->name }}</p>
                                <small style="opacity:.76;">{{ $isCurrentUser ? 'Anda' : 'Mahasiswa' }}</small>
                            </div>
                            <strong>{{ number_format($entry->weekly_score) }} poin</strong>
                        </div>
                    @empty
                        <div class="sd-soft-box" style="background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.12); color:#DCE7F3; text-align:center;">
                            Belum ada skor mingguan.
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </main>

    <div id="achievementModal" class="sd-modal" onclick="closeAchievement(event)">
        <div class="sd-card sd-section" style="max-width: 420px; width: 100%; position: relative;" onclick="event.stopPropagation()">
            <button type="button" onclick="closeAchievement()"
                style="position:absolute; right:16px; top:16px; width:38px; height:38px; border:0; border-radius:999px; background:#DCE7F3; color:#1D5FD6; font-size:24px; cursor:pointer;">&times;</button>
            <div style="text-align:center; padding-top: 10px;">
                <img id="modalIcon" src="" alt="Ikon pencapaian" style="width:86px; height:86px; object-fit:contain; margin:0 auto;">
                <h3 id="modalName" style="margin:16px 0 8px; font-size:26px; font-weight:900;"></h3>
                <p id="modalDescription" style="margin:0; color:#6A7C93; line-height:1.6;"></p>
                <div id="modalStatus" style="display:inline-flex; margin-top:18px; padding:10px 14px; border-radius:999px; background:#DCE7F3; color:#1D5FD6; font-weight:800;"></div>
            </div>
        </div>
    </div>

    <script>
        let achievementsExpanded = false;

        function showAchievement(icon, name, description, isUnlocked, unlockedAt) {
            document.getElementById('modalIcon').src = icon;
            document.getElementById('modalName').innerText = name;
            document.getElementById('modalDescription').innerText = description;
            document.getElementById('modalStatus').innerText = isUnlocked ? `Dibuka pada ${unlockedAt}` : 'Belum terbuka';
            document.getElementById('achievementModal').classList.add('is-open');
        }

        function closeAchievement(event) {
            if (event && event.target.id !== 'achievementModal') return;
            document.getElementById('achievementModal').classList.remove('is-open');
        }

        function toggleAchievements() {
            achievementsExpanded = !achievementsExpanded;
            document.querySelectorAll('.extra-achievement').forEach((item) => {
                item.classList.toggle('hidden', !achievementsExpanded);
            });

            const button = document.getElementById('achievementToggleBtn');
            if (button) {
                button.innerText = achievementsExpanded ? 'Sembunyikan Pencapaian' : 'Lihat Semua Pencapaian';
            }
        }
    </script>
@endsection
