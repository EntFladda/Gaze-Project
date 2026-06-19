<div id="lecturerSidebar" class="lecturer-sidebar">
    <div>
        <div class="lecturer-sidebar-top">
            <a href="{{ route('lecturer.dashboard') }}" class="lecturer-brand">
                <img src="{{ asset('favicon-ctg.png') }}" alt="Logo" class="lecturer-brand-logo">
                <span class="lecturer-expand-only">CTG</span>
            </a>

            <button id="lecturerToggle" type="button" class="lecturer-toggle">
                &#9776;
            </button>
        </div>

        <nav class="lecturer-sidebar-nav">
            <a href="{{ route('lecturer.dashboard') }}"
                class="lecturer-nav-link {{ request()->routeIs('lecturer.dashboard') ? 'is-active' : '' }}">
                <span class="lecturer-nav-badge">DB</span>
                <span class="lecturer-expand-only">Dashboard</span>
            </a>

            <a href="{{ route('lecturer.sections.index') }}"
                class="lecturer-nav-link {{ request()->routeIs('lecturer.sections.*') ? 'is-active' : '' }}">
                <span class="lecturer-nav-badge">SC</span>
                <span class="lecturer-expand-only">Sections</span>
            </a>

            <a href="{{ route('lecturer.challenges.index') }}"
                class="lecturer-nav-link {{ request()->routeIs('lecturer.challenges.*') ? 'is-active' : '' }}">
                <span class="lecturer-nav-badge">MS</span>
                <span class="lecturer-expand-only">Challenges</span>
            </a>

            <a href="{{ route('lecturer.questions.index') }}"
                class="lecturer-nav-link {{ request()->routeIs('lecturer.questions.*') ? 'is-active' : '' }}">
                <span class="lecturer-nav-badge">QS</span>
                <span class="lecturer-expand-only">Questions</span>
            </a>

            <a href="{{ route('lecturer.students.index') }}"
                class="lecturer-nav-link {{ request()->routeIs('lecturer.students.*') ? 'is-active' : '' }}">
                <span class="lecturer-nav-badge">ST</span>
                <span class="lecturer-expand-only">Students</span>
            </a>
        </nav>
    </div>

    <div class="lecturer-sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="lecturer-nav-link lecturer-logout-link">
                <span class="lecturer-nav-badge">EX</span>
                <span class="lecturer-expand-only">Logout</span>
            </button>
        </form>
    </div>
</div>

<style>
    .lecturer-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: linear-gradient(180deg, #0A2342 0%, #123A68 55%, #071426 100%);
        border-right: 4px solid #1D5FD6;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        transition: width 0.3s ease;
        overflow-x: hidden;
        overflow-y: auto;
        z-index: 60;
        scrollbar-width: thin;
        scrollbar-color: rgba(183, 204, 230, 0.28) transparent;
    }

    .lecturer-sidebar.is-collapsed {
        width: 96px;
    }

    .lecturer-sidebar-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 20px 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .lecturer-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-decoration: none;
    }

    .lecturer-brand-logo {
        width: 40px;
        height: 40px;
        object-fit: contain;
        flex-shrink: 0;
    }

    .lecturer-toggle {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 28px;
        cursor: pointer;
    }

    .lecturer-sidebar-intro {
        margin: 20px 18px 0;
        padding: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.06);
    }

    .lecturer-sidebar-kicker {
        margin: 0;
        font-size: 11px;
        letter-spacing: 0.32em;
        text-transform: uppercase;
        color: rgba(183, 204, 230, 0.7);
    }

    .lecturer-sidebar-copy {
        margin: 10px 0 0;
        font-size: 14px;
        line-height: 1.6;
        color: rgba(220, 231, 243, 0.78);
    }

    .lecturer-sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 22px 14px;
    }

    .lecturer-nav-link {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.25s ease;
        box-sizing: border-box;
    }

    .lecturer-nav-link:hover,
    .lecturer-nav-link.is-active {
        background: rgba(29, 95, 214, 0.34);
        box-shadow: 0 14px 28px rgba(91, 16, 33, 0.28);
    }

    .lecturer-nav-badge {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.11);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.16em;
        flex-shrink: 0;
    }

    .lecturer-sidebar-footer {
        padding: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.12);
        margin-top: 8px;
    }

    .lecturer-sidebar::-webkit-scrollbar {
        width: 8px;
    }

    .lecturer-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .lecturer-sidebar::-webkit-scrollbar-thumb {
        background: rgba(183, 204, 230, 0.22);
        border-radius: 999px;
    }

    .lecturer-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(183, 204, 230, 0.35);
    }

    .lecturer-logout-link {
        border: 0;
        background: #1D5FD6;
        cursor: pointer;
    }

    .lecturer-sidebar.is-collapsed .lecturer-expand-only {
        display: none;
    }

    .lecturer-sidebar.is-collapsed .lecturer-sidebar-top,
    .lecturer-sidebar.is-collapsed .lecturer-nav-link,
    .lecturer-sidebar.is-collapsed .lecturer-logout-link {
        justify-content: center;
    }

    @media (max-width: 768px) {
        .lecturer-sidebar {
            width: 96px;
        }

        .lecturer-expand-only {
            display: none;
        }

        .lecturer-sidebar-top,
        .lecturer-nav-link,
        .lecturer-logout-link {
            justify-content: center;
        }
    }
</style>
