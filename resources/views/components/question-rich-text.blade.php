@props([
    'text' => '',
    'tone' => 'default',
])

@php
    $normalized = trim((string) $text);
    $lines = preg_split("/\r\n|\r|\n/", $normalized) ?: [];

    $headingClasses = match ($tone) {
        'warning' => 'text-amber-700 font-bold',
        'info' => 'text-sky-700 font-bold',
        default => 'text-slate-800 font-bold',
    };

    $bulletPattern = '/^(?:[-*]|\x{2022}|\x{25CF}|\x{25AA}|\x{25E6}|\x{25A0}|\x{25A1}|\x{2610}|\x{F0A7}|\x{F0B7}|\x{F0FC}|[^\p{L}\p{N}\s])\s*(.+)$/u';
@endphp

@if ($normalized !== '')
    <div class="space-y-3">
        @foreach ($lines as $line)
            @php
                $trimmed = trim($line);
            @endphp

            @if ($trimmed === '')
                <div class="h-2"></div>
            @elseif (preg_match('/^(jawaban|penjelasan|solusi\s*\d+)\s*:?\s*$/iu', $trimmed))
                <p class="{{ $headingClasses }}">{{ $trimmed }}</p>
            @elseif (preg_match($bulletPattern, $trimmed, $matches))
                <div class="flex items-start gap-3">
                    <span class="mt-1 text-slate-500" aria-hidden="true">&bull;</span>
                    <p class="leading-8 text-slate-700">{{ $matches[1] }}</p>
                </div>
            @else
                <p class="leading-8 text-slate-700">{{ $trimmed }}</p>
            @endif
        @endforeach
    </div>
@endif
