<nav class="lecturer-navbar">
    <div class="lecturer-navbar-title">
        <div class="lecturer-navbar-logo-wrap">
            <img src="{{ asset('favicon-ctg.png') }}" alt="Logo" class="lecturer-navbar-logo">
        </div>
        <div>
            <p class="lecturer-navbar-kicker">Lecturer</p>
            <a href="{{ route('lecturer.dashboard') }}" class="lecturer-navbar-link">Dashboard</a>
        </div>
    </div>

    <div class="lecturer-user-menu">
        <button id="userMenuButton" class="lecturer-user-button">
            <img src="{{ Auth::user()->profile_photo ? asset('storage/' . Auth::user()->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}"
                alt="User Photo" class="lecturer-user-photo">

            <div class="lecturer-user-copy">
                <p class="lecturer-user-role">Pengajar</p>
                <p class="lecturer-user-name">{{ Auth::user()->name }}</p>
            </div>

            <span class="lecturer-user-caret">&#9662;</span>
        </button>

        <div id="userDropdown" class="lecturer-user-dropdown hidden">
            <div class="lecturer-user-dropdown-head">
                    <img src="{{ Auth::user()->profile_photo ? asset('storage/' . Auth::user()->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}"
                    alt="User Avatar" class="lecturer-user-dropdown-photo">
                <p class="lecturer-user-dropdown-kicker">Akun</p>
                <p class="lecturer-user-dropdown-email">{{ Auth::user()->email }}</p>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="lecturer-user-dropdown-action">
                    Logout
                </button>
            </form>
        </div>
    </div>
</nav>

<style>
    .lecturer-navbar {
        position: sticky;
        top: 0;
        z-index: 40;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        background: rgba(11, 47, 107, 0.84);
        border-bottom: 1px solid rgba(183, 204, 230, 0.12);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18);
        backdrop-filter: blur(12px);
    }

    .lecturer-navbar-title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .lecturer-navbar-logo-wrap {
        width: 46px;
        height: 46px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(183, 204, 230, 0.18);
    }

    .lecturer-navbar-logo {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .lecturer-navbar-kicker {
        margin: 0 0 4px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.32em;
        color: rgba(183, 204, 230, 0.72);
    }

    .lecturer-navbar-link {
        color: #fff;
        text-decoration: none;
        font-size: 30px;
        font-weight: 700;
    }

    .lecturer-user-menu {
        position: relative;
    }

    .lecturer-user-button {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 22px;
        border: 1px solid rgba(183, 204, 230, 0.18);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        cursor: pointer;
    }

    .lecturer-user-photo,
    .lecturer-user-dropdown-photo {
        object-fit: cover;
        border-radius: 999px;
        border: 2px solid rgba(183, 204, 230, 0.3);
        background: #fff;
    }

    .lecturer-user-photo {
        width: 42px;
        height: 42px;
    }

    .lecturer-user-dropdown-photo {
        width: 56px;
        height: 56px;
        margin: 0 auto 12px;
    }

    .lecturer-user-copy {
        text-align: left;
    }

    .lecturer-user-role {
        margin: 0;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.22em;
        color: rgba(183, 204, 230, 0.7);
    }

    .lecturer-user-name {
        margin: 4px 0 0;
        font-size: 16px;
        font-weight: 700;
    }

    .lecturer-user-caret {
        font-size: 16px;
    }

    .lecturer-user-dropdown {
        position: absolute;
        right: 0;
        margin-top: 12px;
        width: 240px;
        overflow: hidden;
        border-radius: 22px;
        border: 1px solid rgba(183, 204, 230, 0.16);
        background: #0A2342;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.32);
    }

    .lecturer-user-dropdown-head {
        padding: 18px 16px;
        text-align: center;
        border-bottom: 1px solid rgba(183, 204, 230, 0.1);
    }

    .lecturer-user-dropdown-kicker {
        margin: 0;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.24em;
        color: rgba(183, 204, 230, 0.68);
    }

    .lecturer-user-dropdown-email {
        margin: 8px 0 0;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        word-break: break-word;
    }

    .lecturer-user-dropdown-action {
        width: 100%;
        border: 0;
        background: transparent;
        color: #DCE7F3;
        text-align: left;
        padding: 14px 16px;
        font-weight: 600;
        cursor: pointer;
    }

    .lecturer-user-dropdown-action:hover {
        background: rgba(29, 95, 214, 0.28);
    }

    .hidden {
        display: none !important;
    }

    @media (max-width: 768px) {
        .lecturer-navbar {
            padding: 16px;
        }

        .lecturer-navbar-link {
            font-size: 24px;
        }

        .lecturer-user-copy {
            display: none;
        }
    }
</style>

<script>
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');

    userMenuButton?.addEventListener('click', function(event) {
        event.stopPropagation();
        userDropdown?.classList.toggle('hidden');
    });

    document.addEventListener('click', function(event) {
        if (userMenuButton && userDropdown && !userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>
