<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CT-Game') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('favicon-ctg.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --guest-bg: #071426;
            --guest-bg-soft: #0A2342;
            --guest-card: #F4F8FC;
            --guest-ink: #09254A;
            --guest-muted: #6A7C93;
            --guest-line: #B7CCE6;
            --guest-accent: #1D5FD6;
            --guest-accent-2: #2BA7D8;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Figtree', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--guest-bg), var(--guest-bg-soft));
            color: var(--guest-ink);
        }

        .guest-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 22px 16px;
        }

        .guest-card {
            width: 100%;
            max-width: 460px;
            padding: 26px;
            border-radius: 24px;
            background: var(--guest-card);
            border: 1px solid rgba(255, 255, 255, .6);
            box-shadow: 0 18px 42px rgba(0, 0, 0, .22);
        }

        .guest-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .guest-brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .guest-brand strong {
            display: block;
            color: var(--guest-ink);
            font-size: 22px;
            line-height: 1;
            letter-spacing: .08em;
        }

        .guest-brand span {
            display: block;
            margin-top: 4px;
            color: var(--guest-muted);
            font-size: 13px;
        }

        .guest-card a { color: var(--guest-accent); }

        @media (max-width: 520px) {
            .guest-card { padding: 22px 18px; border-radius: 22px; }
        }
    </style>
</head>

<body>
    <main class="guest-shell">
        <section class="guest-card">
            <div class="guest-brand">
                <img src="{{ asset('favicon-ctg.png') }}" alt="CTG">
                <div>
                    <strong>CTG</strong>
                    <span>Computational Thinking Game</span>
                </div>
            </div>

            {{ $slot }}
        </section>
    </main>
</body>

</html>
