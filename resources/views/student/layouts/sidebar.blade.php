<div id="sidebar"
    class="w-24 md:w-64 bg-gradient-to-b from-[#0A2342] via-[#0A2342] to-[#071426] text-white h-screen fixed left-0 top-0 transition-all duration-500 flex flex-col shadow-2xl border-r border-white/10 glow-sidebar overflow-hidden">

    <div class="flex items-center justify-center md:justify-between px-3 md:px-6 py-5 border-b border-white/10 shadow-md gap-3">
        <h2 id="sidebar-logo"
            class="text-2xl font-bold transition-all duration-300 tracking-wider flex items-center min-w-0">
            <img src="{{ asset('favicon-ctg.png') }}" alt="Logo" class="w-10 h-10 shrink-0 md:mr-3">
            <span id="sidebar-title" class="sidebar-text">CTG</span>
        </h2>

        <button id="toggleSidebar" class="focus:outline-none hover:scale-110 transition-all hover-sfx"
            onclick="playShowDetailSound()">
            <span id="toggleSidebarIcon"
                class="text-white text-2xl cursor-pointer hover:text-gray-300 transition duration-300 transform hover:rotate-90">
                &#9776;
            </span>
        </button>
    </div>

    <nav class="mt-6 flex-1 space-y-2">
        <a href="{{ route('student.profile.index') }}"
            data-guide="profile"
            class="sidebar-link flex items-center justify-center md:justify-start px-3 md:px-6 py-3 bg-transparent hover:bg-white/8 rounded-md mx-3 md:mx-4 transition-all duration-300 shadow-lg hover:shadow-black/20 hover-sfx"
            onclick="playClick()">
            <span
                class="sidebar-badge mr-0 md:mr-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xs font-bold uppercase tracking-[0.18em] glow-icon shrink-0">DB</span>
            <span class="sidebar-text transition-all duration-300 font-semibold whitespace-nowrap">Dashboard</span>
        </a>
<a href="{{ route('student.mission.index') }}"
            data-guide="mission"
            class="sidebar-link flex items-center justify-center md:justify-start px-3 md:px-6 py-3 bg-transparent hover:bg-white/8 rounded-md mx-3 md:mx-4 transition-all duration-300 shadow-lg hover:shadow-black/20 hover-sfx"
            onclick="playClick()">
            <span
                class="sidebar-badge mr-0 md:mr-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xs font-bold uppercase tracking-[0.18em] glow-icon shrink-0">MS</span>
            <span class="sidebar-text transition-all duration-300 font-semibold whitespace-nowrap">Missions</span>
        </a>
<a href="{{ route('student.history.index') }}"
            data-guide="history"
            class="sidebar-link flex items-center justify-center md:justify-start px-3 md:px-6 py-3 bg-transparent hover:bg-white/8 rounded-md mx-3 md:mx-4 transition-all duration-300 shadow-lg hover:shadow-black/20 hover-sfx"
            onclick="playClick()">
            <span
                class="sidebar-badge mr-0 md:mr-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xs font-bold uppercase tracking-[0.18em] glow-icon shrink-0">RH</span>
            <span class="sidebar-text transition-all duration-300 font-semibold whitespace-nowrap">Riwayat</span>
        </a>

        <a href="{{ route('student.tutorial.index') }}"
            data-guide="tutorial"
            class="sidebar-link flex items-center justify-center md:justify-start px-3 md:px-6 py-3 bg-transparent hover:bg-white/8 rounded-md mx-3 md:mx-4 transition-all duration-300 shadow-lg hover:shadow-black/20 hover-sfx"
            onclick="playClick()">
            <span
                class="sidebar-badge mr-0 md:mr-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xs font-bold uppercase tracking-[0.18em] glow-icon shrink-0">TD</span>
            <span class="sidebar-text transition-all duration-300 font-semibold whitespace-nowrap">Tutorial</span>
        </a>
    </nav>

    <div class="px-3 md:px-6 py-4 border-t border-white/10">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button id="logout-button" type="submit"
                class="w-full flex items-center justify-center md:justify-start bg-[#1D5FD6] hover:bg-[#1D5FD6] px-3 md:px-4 py-2 rounded transition-all duration-300 shadow-md hover:shadow-black/25">
                <span
                    class="sidebar-badge mr-0 md:mr-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xs font-bold uppercase tracking-[0.18em] glow-icon shrink-0">EX</span>
                <span class="sidebar-text transition-all duration-300 font-semibold whitespace-nowrap">Logout</span>
            </button>
        </form>
    </div>

    <audio id="hover-sound" src="{{ asset('sfx/hover.mp3') }}"></audio>
    <audio id="click-sound" src="{{ asset('sfx/click.mp3') }}"></audio>
    <audio id="show-detail-sound" src="{{ asset('sfx/showdetailbox.mp3') }}"></audio>
</div>

<style>
    .glow-sidebar {
        box-shadow: 18px 0 36px rgba(8, 4, 8, 0.18);
    }

    .glow-icon {
        transition: all 0.3s ease-in-out;
    }

    .glow-icon:hover {
        text-shadow: 0px 0px 8px rgba(255, 255, 255, 0.45);
    }
</style>

<script>
    function playShowDetailSound() {
        const audio = document.getElementById("show-detail-sound");
        if (audio) {
            audio.currentTime = 0;
            audio.play();
        }
    }

    function playClick() {
        const audio = document.getElementById("click-sound");
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
        document.querySelectorAll(".hover-sfx").forEach((el) => {
            el.addEventListener("mouseenter", playHoverSound);
        });
    });

    function rebindHoverSound() {
        document.querySelectorAll(".hover-sfx").forEach((el) => {
            el.removeEventListener("mouseenter", playHoverSound);
            el.addEventListener("mouseenter", playHoverSound);
        });
    }
</script>
