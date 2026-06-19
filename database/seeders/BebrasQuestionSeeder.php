<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Challenge;
use App\Models\Question;
use App\Models\QuestionBlock;
use App\Models\Section;
use Illuminate\Database\Seeder;

class BebrasQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeLegacySampleSection();

        /** @var array<int, array<string, mixed>> $sections */
        $sections = require base_path('database/seeders/data/bebras_mission_bank.php');
        $desiredSectionNames = collect($sections)->pluck('name')->all();

        Section::whereNotIn('name', $desiredSectionNames)->get()->each(function (Section $section): void {
            $section->challenges()->each(function (Challenge $challenge): void {
                $challenge->questions()->delete();
                $challenge->delete();
            });

            $section->delete();
        });

        foreach ($sections as $sectionData) {
            $section = Section::firstOrCreate(
                ['name' => $sectionData['name']],
                ['order' => $sectionData['order']]
            );

            $section->update(['order' => $sectionData['order']]);

            $desiredTitles = collect($sectionData['missions'])->pluck('title')->all();

            Challenge::where('section_id', $section->id)
                ->whereNotIn('title', $desiredTitles)
                ->get()
                ->each(function (Challenge $challenge): void {
                    $challenge->questions()->delete();
                    $challenge->delete();
                });

            foreach ($sectionData['missions'] as $missionData) {
                $challenge = Challenge::updateOrCreate(
                    ['section_id' => $section->id, 'title' => $missionData['title']],
                    ['total_exp' => 0, 'total_score' => 0]
                );

                $questions = collect($missionData['questions'])
                    ->map(function (array $questionData): array {
                        if (! empty($questionData['source_images'])) {
                            $questionData['question_image'] = $this->prepareQuestionComposite(
                                (string) ($questionData['source_key'] ?? uniqid('source-', true)),
                                $questionData['source_images']
                            );
                        } elseif (! empty($questionData['question_image'])) {
                            $questionData['question_image'] = $this->prepareQuestionImage((string) $questionData['question_image']);
                        }

                        return $questionData;
                    })
                    ->all();

                $challenge->questions()->get()->each(function (Question $question): void {
                    $question->answers()->delete();
                    $question->delete();
                });

                foreach ($questions as $questionData) {
                    $blocks = $questionData['blocks'] ?? [];
                    unset($questionData['blocks'], $questionData['source_key'], $questionData['source_images']);

                    $question = Question::create(
                        collect($questionData)->except('answers')->put('challenge_id', $challenge->id)->toArray()
                    );

                    foreach ($blocks as $index => $blockData) {
                        $type = $blockData['type'] ?? null;
                        if (! in_array($type, ['text', 'image'], true)) {
                            continue;
                        }

                        $imagePath = $blockData['image_path'] ?? null;
                        if ($type === 'image' && $imagePath) {
                            $imagePath = $this->prepareQuestionImage((string) $imagePath);
                        }

                        QuestionBlock::create([
                            'question_id' => $question->id,
                            'type' => $type,
                            'content' => $type === 'text' ? ($blockData['content'] ?? null) : null,
                            'image_path' => $type === 'image' ? $imagePath : null,
                            'sort_order' => $blockData['sort_order'] ?? ($index + 1),
                        ]);
                    }

                    foreach ($questionData['answers'] as $answerData) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer' => $answerData['answer'],
                            'answer_image' => $answerData['answer_image'] ?? null,
                            'is_correct' => $answerData['is_correct'],
                        ]);
                    }
                }

                $challenge->recalculateTotals();
            }
        }
    }

    protected function ensureMissionVisual(int $sectionOrder, string $missionTitle, array $visual): ?string
    {
        $slug = $visual['slug'] ?? null;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $relativePath = 'questions/' . $slug . '.svg';
        $svg = $this->renderMissionVisual(
            $sectionOrder,
            $visual['headline'] ?? $missionTitle,
            $visual['subheadline'] ?? 'Latihan singkat CT',
            $visual['chips'] ?? []
        );

        foreach ([
            public_path('storage/' . $relativePath),
            storage_path('app/public/' . $relativePath),
        ] as $path) {
            $directory = dirname($path);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($path, $svg);
        }

        return $relativePath;
    }

    protected function prepareQuestionImage(string $imagePath): string
    {
        $normalizedPath = str_replace('\\', '/', trim($imagePath));

        if ($normalizedPath === '' || str_starts_with($normalizedPath, 'questions/')) {
            return $normalizedPath;
        }

        $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION) ?: 'png';
        $targetName = $this->buildQuestionImageName($normalizedPath, $extension);
        $relativePath = 'questions/' . $targetName;
        $sourcePath = base_path($normalizedPath);
        if (! file_exists($sourcePath)) {
            foreach ([
                public_path('storage/' . $relativePath),
                storage_path('app/public/' . $relativePath),
            ] as $targetPath) {
                if (file_exists($targetPath)) {
                    return $relativePath;
                }
            }

            return $normalizedPath;
        }

        foreach ([
            public_path('storage/' . $relativePath),
            storage_path('app/public/' . $relativePath),
        ] as $targetPath) {
            $directory = dirname($targetPath);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            copy($sourcePath, $targetPath);
        }

        return $relativePath;
    }

    protected function prepareQuestionComposite(string $sourceKey, array $sourceImages): ?string
    {
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($sourceKey)) ?: 'source-question';
        $relativePath = 'questions/source-' . trim($slug, '-') . '.png';
        $targetPaths = [
            public_path('storage/' . $relativePath),
            storage_path('app/public/' . $relativePath),
        ];

        $availableSources = collect($sourceImages)
            ->filter(fn ($imagePath) => is_string($imagePath) && trim($imagePath) !== '')
            ->map(fn ($imagePath) => base_path(str_replace('\\', '/', trim((string) $imagePath))))
            ->filter(fn ($imagePath) => file_exists($imagePath))
            ->values()
            ->all();

        if (empty($availableSources)) {
            foreach ($targetPaths as $targetPath) {
                if (file_exists($targetPath)) {
                    return $relativePath;
                }
            }

            return null;
        }

        $images = [];
        $maxWidth = 0;
        $totalHeight = 0;
        $gap = 18;
        $padding = 18;
        $maxCanvasImageWidth = 960;

        foreach ($availableSources as $sourcePath) {
            $resource = $this->loadImageResource($sourcePath);
            if (! $resource) {
                continue;
            }

            $width = imagesx($resource);
            $height = imagesy($resource);
            if ($width < 20 || $height < 20) {
                imagedestroy($resource);
                continue;
            }

            if (($width < 120 && $height < 120) || ($height < 70 && $this->isMostlyDark($resource, $width, $height))) {
                imagedestroy($resource);
                continue;
            }

            $targetWidth = min($width, $maxCanvasImageWidth);
            $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
            $images[] = [$resource, $width, $height, $targetWidth, $targetHeight];
            $maxWidth = max($maxWidth, $targetWidth);
            $totalHeight += $targetHeight;
        }

        if (empty($images)) {
            return null;
        }

        $canvasWidth = $maxWidth + ($padding * 2);
        $canvasHeight = $totalHeight + ($padding * 2) + ($gap * (count($images) - 1));
        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $y = $padding;
        foreach ($images as [$resource, $width, $height, $targetWidth, $targetHeight]) {
            $x = (int) floor(($canvasWidth - $targetWidth) / 2);
            imagecopyresampled($canvas, $resource, $x, $y, 0, 0, $targetWidth, $targetHeight, $width, $height);
            $y += $targetHeight + $gap;
            imagedestroy($resource);
        }

        foreach ($targetPaths as $targetPath) {
            $directory = dirname($targetPath);
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            imagepng($canvas, $targetPath);
        }

        imagedestroy($canvas);

        return $relativePath;
    }

    protected function loadImageResource(string $path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    protected function isMostlyDark($resource, int $width, int $height): bool
    {
        $samples = 0;
        $darkSamples = 0;
        $stepX = max(1, (int) floor($width / 24));
        $stepY = max(1, (int) floor($height / 24));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgb = imagecolorat($resource, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $samples++;

                if (($red + $green + $blue) < 105) {
                    $darkSamples++;
                }
            }
        }

        return $samples > 0 && ($darkSamples / $samples) > 0.48;
    }

    protected function buildQuestionImageName(string $normalizedPath, string $extension): string
    {
        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($normalizedPath, PATHINFO_FILENAME)) ?: 'question-image';
        $directory = preg_replace('/[^A-Za-z0-9_-]+/', '-', dirname($normalizedPath));
        $directory = trim((string) $directory, '-.');

        if ($directory === '' || $directory === '.') {
            return $filename . '.' . $extension;
        }

        return $directory . '-' . $filename . '.' . $extension;
    }

    protected function renderMissionVisual(
        int $sectionOrder,
        string $headline,
        string $subheadline,
        array $chips
    ): string {
        [$start, $end, $accent] = $this->paletteForSection($sectionOrder);
        $headlineLines = $this->wrapSvgText($headline, 28);
        $subheadlineLines = $this->wrapSvgText($subheadline, 42);
        $chipMarkup = '';
        $chipY = 208;

        foreach (array_slice($chips, 0, 4) as $index => $chip) {
            $x = 48 + ($index % 2) * 232;
            $y = $chipY + intdiv($index, 2) * 44;
            $chipMarkup .= sprintf(
                '<rect x="%d" y="%d" width="192" height="30" rx="15" fill="rgba(255,255,255,0.16)" />',
                $x,
                $y
            );
            $chipMarkup .= sprintf(
                '<text x="%d" y="%d" fill="#F8FAFC" font-size="14" font-family="Arial, sans-serif">%s</text>',
                $x + 18,
                $y + 20,
                $this->escapeSvg((string) $chip)
            );
        }

        $headlineMarkup = '';
        foreach ($headlineLines as $index => $line) {
            $headlineMarkup .= sprintf(
                '<text x="48" y="%d" fill="#FFFFFF" font-size="28" font-weight="700" font-family="Arial, sans-serif">%s</text>',
                88 + ($index * 34),
                $this->escapeSvg($line)
            );
        }

        $subheadlineMarkup = '';
        foreach ($subheadlineLines as $index => $line) {
            $subheadlineMarkup .= sprintf(
                '<text x="48" y="%d" fill="#E2E8F0" font-size="16" font-family="Arial, sans-serif">%s</text>',
                150 + ($index * 22),
                $this->escapeSvg($line)
            );
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360" role="img" aria-label="Visual mission">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$start}" />
      <stop offset="100%" stop-color="{$end}" />
    </linearGradient>
  </defs>
  <rect width="640" height="360" rx="28" fill="url(#bg)" />
  <circle cx="560" cy="76" r="52" fill="rgba(255,255,255,0.10)" />
  <circle cx="512" cy="126" r="22" fill="rgba(255,255,255,0.14)" />
  <rect x="36" y="34" width="568" height="292" rx="24" fill="rgba(15,23,42,0.18)" stroke="rgba(255,255,255,0.14)" />
  <rect x="48" y="48" width="132" height="28" rx="14" fill="{$accent}" />
  <text x="67" y="67" fill="#082F49" font-size="13" font-weight="700" font-family="Arial, sans-serif">Latihan CT</text>
  {$headlineMarkup}
  {$subheadlineMarkup}
  {$chipMarkup}
  <path d="M468 250 C520 220, 558 220, 604 262" fill="none" stroke="rgba(255,255,255,0.22)" stroke-width="8" stroke-linecap="round" />
  <circle cx="468" cy="250" r="12" fill="#FFFFFF" fill-opacity="0.92" />
  <circle cx="536" cy="230" r="12" fill="#FFFFFF" fill-opacity="0.82" />
  <circle cx="604" cy="262" r="12" fill="#FFFFFF" fill-opacity="0.72" />
  <text x="467" y="312" fill="#E2E8F0" font-size="13" font-family="Arial, sans-serif">naik level</text>
</svg>
SVG;
    }

    protected function paletteForSection(int $sectionOrder): array
    {
        $palettes = [
            1 => ['#0F172A', '#1D4ED8', '#FDE68A'],
            2 => ['#0B3B53', '#0891B2', '#A7F3D0'],
            3 => ['#102A43', '#7C3AED', '#C4B5FD'],
            4 => ['#1E293B', '#2563EB', '#93C5FD'],
            5 => ['#1F2937', '#0F766E', '#99F6E4'],
            6 => ['#3F1D2E', '#BE185D', '#FBCFE8'],
            7 => ['#3B0764', '#9333EA', '#F5D0FE'],
        ];

        return $palettes[$sectionOrder] ?? ['#0F172A', '#334155', '#BAE6FD'];
    }

    /**
     * @return array<int, string>
     */
    protected function wrapSvgText(string $text, int $lineLength): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($text === '') {
            return [];
        }

        return preg_split('/\n/', wordwrap($text, $lineLength, "\n", true)) ?: [$text];
    }

    protected function escapeSvg(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    protected function removeLegacySampleSection(): void
    {
        $legacySection = Section::where('name', 'manju')->first();

        if (! $legacySection) {
            return;
        }

        $legacySection->challenges()->each(function (Challenge $challenge): void {
            $challenge->questions()->delete();
            $challenge->delete();
        });

        $legacySection->delete();
    }
}
