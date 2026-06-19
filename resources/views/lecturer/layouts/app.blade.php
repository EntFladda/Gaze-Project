<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Lecturer Dashboard') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-ctg.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body.lecturer-body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top, rgba(29, 95, 214, 0.22), transparent 34%),
                radial-gradient(circle at right top, rgba(43, 167, 216, 0.18), transparent 28%),
                linear-gradient(145deg, #071426 0%, #0A2342 46%, #0F2F57 100%);
            color: #fff;
            overflow-x: hidden;
        }

        .lecturer-layout {
            display: flex;
            min-height: 100vh;
        }

        .lecturer-main-shell {
            flex: 1;
            min-height: 100vh;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }

        .lecturer-main-content {
            padding: 24px;
            overflow-x: hidden;
        }

        .lecturer-main-content .bg-white {
            background: linear-gradient(180deg, rgba(244, 248, 252, 0.97), rgba(248, 251, 255, 0.95)) !important;
            border: 1px solid rgba(29, 95, 214, 0.14);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        }

        .lecturer-main-content input:not([type="checkbox"]):not([type="radio"]),
        .lecturer-main-content select,
        .lecturer-main-content textarea {
            background: rgba(248, 251, 255, 0.98) !important;
            border-color: rgba(29, 95, 214, 0.18) !important;
            color: #09254A !important;
        }

        .lecturer-main-content input::placeholder,
        .lecturer-main-content textarea::placeholder {
            color: #9ca3af !important;
            opacity: 1;
        }

        .lecturer-main-content select option {
            color: #09254A;
            background: #ffffff;
        }

        .lecturer-main-content input[type="checkbox"],
        .lecturer-main-content input[type="radio"] {
            appearance: auto !important;
            -webkit-appearance: auto !important;
            accent-color: #1D5FD6;
            background: initial !important;
            border-color: initial !important;
        }

        .lecturer-main-content table thead.bg-blue-600,
        .lecturer-main-content table thead.bg-green-500,
        .lecturer-main-content table thead.bg-indigo-600 {
            background: linear-gradient(90deg, #1D5FD6, #2B7FD8) !important;
        }

        .lecturer-main-content .bg-blue-600,
        .lecturer-main-content .hover\:bg-blue-500:hover,
        .lecturer-main-content .hover\:bg-blue-600:hover {
            background-color: #1D5FD6 !important;
        }

        .lecturer-main-content .bg-green-500,
        .lecturer-main-content .hover\:bg-green-400:hover {
            background-color: #1D5FD6 !important;
        }

        .lecturer-main-content .bg-indigo-500,
        .lecturer-main-content .hover\:bg-indigo-400:hover {
            background-color: #168C8C !important;
        }

        .lecturer-main-content .bg-yellow-400,
        .lecturer-main-content .hover\:bg-yellow-300:hover {
            background-color: #F2A93B !important;
        }

        .lecturer-main-shell.is-collapsed {
            margin-left: 96px;
        }

        @media (max-width: 768px) {
            .lecturer-main-shell {
                margin-left: 96px;
            }

            .lecturer-main-content {
                padding: 16px;
            }
        }
    </style>
</head>

<body class="lecturer-body">
    <div class="lecturer-layout">
        @include('lecturer.layouts.sidebar')

        <div id="lecturerMainShell" class="lecturer-main-shell">
            @include('lecturer.layouts.navbar')

            <main class="lecturer-main-content">
                @yield('content')
                @yield('scripts')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('lecturerSidebar');
            const mainShell = document.getElementById('lecturerMainShell');
            const toggle = document.getElementById('lecturerToggle');

            function setCollapsed(collapsed) {
                if (!sidebar || !mainShell) {
                    return;
                }

                sidebar.classList.toggle('is-collapsed', collapsed);
                mainShell.classList.toggle('is-collapsed', collapsed);
            }

            const defaultCollapsed = window.innerWidth <= 768;
            setCollapsed(defaultCollapsed);

            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    setCollapsed(true);
                }
            });

            toggle?.addEventListener('click', function() {
                const collapsed = sidebar.classList.contains('is-collapsed');
                setCollapsed(!collapsed);
            });

            document.addEventListener('error', function(event) {
                const image = event.target;
                if (image?.tagName === 'IMG' && image.src.includes('/storage/profile_photos/')) {
                    image.src = "{{ asset('storage/profile_photos/default-3d.svg') }}";
                }
            }, true);
        });
    </script>
</body>

</html>
