<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rank Up!</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background:
                radial-gradient(circle at top, rgba(29, 95, 214, 0.22), transparent 32%),
                linear-gradient(135deg, #071426, #0A2342, #123A68);
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        .glow-box {
            border: 3px solid #F2A93B;
            box-shadow: 0px 0px 15px rgba(242, 169, 59, 0.7);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 10px rgba(242, 169, 59, 0.55);
            }

            50% {
                box-shadow: 0 0 25px rgba(242, 169, 59, 0.9);
            }

            100% {
                box-shadow: 0 0 10px rgba(242, 169, 59, 0.55);
            }
        }

        .pop-in {
            animation: popIn 0.8s ease-out;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .loader {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #F2A93B;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <script>
        setTimeout(function() {
            window.location.href =
                "{{ route('student.challenge.summary', ['challenge_id' => $challenge_id, 'attempt_number' => $attempt_number]) }}";
        }, 6000);
    </script>
</head>

<body class="min-h-screen flex items-center justify-center px-4">

    <audio id="rankup-audio" autoplay>
        <source src="{{ asset('audio/rankup.mp3') }}" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <div class="bg-[#0A2342] border border-sky-200/25 p-10 rounded-2xl glow-box pop-in max-w-md w-full text-center">
        <h1 class="text-4xl font-extrabold text-green-400 mb-2 animate-bounce">🔓 Rank Unlocked!</h1>
        <p class="text-sm text-gray-400 mb-1">You’ve proven your skills. Your new rank is:</p>

        <div class="text-3xl font-extrabold text-yellow-300 my-5 tracking-wider uppercase">
            {{ $student->ranks->sortByDesc('min_exp')->first()?->name }}
        </div>

        <div class="mt-6">
            <p class="text-sm text-gray-400 mb-2">Redirecting to challenge result...</p>
            <div class="loader"></div>
        </div>
    </div>

</body>

</html>
