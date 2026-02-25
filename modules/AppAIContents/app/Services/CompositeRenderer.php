<?php

namespace Modules\AppAIContents\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppAIContents\Models\ContentCreative;
use Modules\AppAIContents\Models\CreativeLayoutTemplate;

class CompositeRenderer
{
    protected ColorResolver $colors;
    protected array $config;
    protected int $canvasW;
    protected int $canvasH;
    protected $canvas;

    public function render(ContentCreative $creative, CreativeLayoutTemplate $template, array $brandColors = []): ?array
    {
        if (!extension_loaded('gd')) {
            Log::warning('CompositeRenderer: GD extension not available');
            return null;
        }

        $this->config = $template->config;
        $this->colors = new ColorResolver($brandColors);

        $dimensions = $this->getCanvasDimensions($creative);
        $this->canvasW = $dimensions['width'];
        $this->canvasH = $dimensions['height'];

        try {
            // 1. Create canvas
            $this->canvas = imagecreatetruecolor($this->canvasW, $this->canvasH);
            imagealphablending($this->canvas, true);
            imagesavealpha($this->canvas, true);

            // 2. Fill background
            $this->fillBackground();

            // 3. Draw background blocks
            $this->drawBackgroundBlocks();

            // 4. Place photo into image region
            $this->placeImage($creative);

            // 5. Apply overlay (gradient)
            $this->applyOverlay();

            // 6. Draw decorations
            $this->drawDecorations();

            // 7. Render text regions
            $this->renderTextRegions($creative);

            // 8. Encode and save
            $result = $this->saveComposite($creative);

            imagedestroy($this->canvas);

            return $result;
        } catch (\Throwable $e) {
            Log::error('CompositeRenderer::render failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if (isset($this->canvas) && is_resource($this->canvas) || (isset($this->canvas) && $this->canvas instanceof \GdImage)) {
                imagedestroy($this->canvas);
            }
            return null;
        }
    }

    public function renderAndSave(ContentCreative $creative, CreativeLayoutTemplate $template, array $brandColors = []): bool
    {
        $creative->update(['composite_status' => 'rendering']);

        $result = $this->render($creative, $template, $brandColors);

        if ($result) {
            $creative->update([
                'composite_image_path' => $result['path'],
                'composite_image_url' => $result['url'],
                'composite_status' => 'ready',
            ]);
            return true;
        }

        $creative->update(['composite_status' => 'failed']);
        return false;
    }

    protected function getCanvasDimensions(ContentCreative $creative): array
    {
        $aspectRatio = $creative->campaign?->aspect_ratio ?? '9:16';
        return match ($aspectRatio) {
            '1:1' => ['width' => 1080, 'height' => 1080],
            '4:5' => ['width' => 1080, 'height' => 1350],
            default => ['width' => 1080, 'height' => 1920],
        };
    }

    // ─── Background ───

    protected function fillBackground(): void
    {
        $bgColor = $this->config['canvas']['background_color'] ?? '#1a1a2e';
        $rgb = $this->colors->hexToRgb($bgColor);
        $color = imagecolorallocate($this->canvas, $rgb['r'], $rgb['g'], $rgb['b']);
        imagefill($this->canvas, 0, 0, $color);
    }

    protected function drawBackgroundBlocks(): void
    {
        $blocks = $this->config['background_blocks'] ?? [];

        foreach ($blocks as $block) {
            $x = $this->pctToX($block['x_pct'] ?? 0);
            $y = $this->pctToY($block['y_pct'] ?? 0);
            $w = $this->pctToW($block['width_pct'] ?? 100);
            $h = $this->pctToH($block['height_pct'] ?? 100);
            $opacity = $block['opacity'] ?? 100;
            $colorMode = $block['color_mode'] ?? 'brand_color';

            $color = $this->colors->gdAllocate($this->canvas, $colorMode, $opacity);

            $radius = isset($block['border_radius_pct'])
                ? (int) round($block['border_radius_pct'] * $this->canvasW / 100)
                : 0;

            if ($radius > 0) {
                $this->drawRoundedRect($x, $y, $x + $w, $y + $h, $radius, $color);
            } else {
                imagefilledrectangle($this->canvas, $x, $y, $x + $w - 1, $y + $h - 1, $color);
            }
        }
    }

    // ─── Image Region ───

    protected function placeImage(ContentCreative $creative): void
    {
        $region = $this->config['image_region'] ?? null;
        if (!$region) return;

        $imagePath = $creative->image_path;
        if (!$imagePath) return;

        $fullPath = Storage::disk('public')->path($imagePath);
        if (!file_exists($fullPath)) {
            // Try image_url as fallback
            if ($creative->image_url) {
                try {
                    $contents = file_get_contents($creative->image_url);
                    $source = imagecreatefromstring($contents);
                } catch (\Throwable $e) {
                    Log::warning('CompositeRenderer: Could not load image from URL', ['url' => $creative->image_url]);
                    return;
                }
            } else {
                return;
            }
        } else {
            $info = @getimagesize($fullPath);
            if (!$info) return;

            $source = match ($info['mime']) {
                'image/jpeg' => @imagecreatefromjpeg($fullPath),
                'image/png' => @imagecreatefrompng($fullPath),
                'image/webp' => @imagecreatefromwebp($fullPath),
                default => null,
            };
        }

        if (!$source) return;

        $sourceW = imagesx($source);
        $sourceH = imagesy($source);

        $dstX = $this->pctToX($region['x_pct'] ?? 0);
        $dstY = $this->pctToY($region['y_pct'] ?? 0);
        $dstW = $this->pctToW($region['width_pct'] ?? 100);
        $dstH = $this->pctToH($region['height_pct'] ?? 100);

        $fit = $region['fit'] ?? 'cover';

        if ($fit === 'cover') {
            // Calculate crop region for cover fit
            $targetRatio = $dstW / $dstH;
            $sourceRatio = $sourceW / $sourceH;

            if ($sourceRatio > $targetRatio) {
                $cropH = $sourceH;
                $cropW = (int) round($sourceH * $targetRatio);
            } else {
                $cropW = $sourceW;
                $cropH = (int) round($sourceW / $targetRatio);
            }

            // Apply gravity
            $gravity = $region['gravity'] ?? 'center';
            $cropX = match ($gravity) {
                'left' => 0,
                'right' => max(0, $sourceW - $cropW),
                default => max(0, (int) round(($sourceW - $cropW) / 2)),
            };
            $cropY = match ($gravity) {
                'top' => 0,
                'bottom' => max(0, $sourceH - $cropH),
                default => max(0, (int) round(($sourceH - $cropH) / 2)),
            };

            imagecopyresampled($this->canvas, $source, $dstX, $dstY, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);
        } else {
            // Contain fit
            imagecopyresampled($this->canvas, $source, $dstX, $dstY, 0, 0, $dstW, $dstH, $sourceW, $sourceH);
        }

        imagedestroy($source);

        // Apply clip shape if specified
        $clipShape = $region['clip_shape'] ?? null;
        if ($clipShape) {
            $this->applyClipShape($clipShape, $region, $dstX, $dstY, $dstW, $dstH);
        }
    }

    protected function applyClipShape(string $shape, array $region, int $dstX, int $dstY, int $dstW, int $dstH): void
    {
        $params = $region['clip_params'] ?? [];

        if ($shape === 'wave_top') {
            // Erase area above wave line with background color
            $bgColor = $this->config['canvas']['background_color'] ?? '#1a1a2e';
            $rgb = $this->colors->hexToRgb($bgColor);
            $bgGd = imagecolorallocate($this->canvas, $rgb['r'], $rgb['g'], $rgb['b']);

            // Check for a background block color at the wave position
            $blocks = $this->config['background_blocks'] ?? [];
            foreach ($blocks as $block) {
                $blockY = $this->pctToY($block['y_pct'] ?? 0);
                $blockH = $this->pctToH($block['height_pct'] ?? 0);
                if ($dstY >= $blockY && $dstY <= $blockY + $blockH) {
                    $blockColor = $block['color_mode'] ?? 'brand_color';
                    $bgGd = $this->colors->gdAllocate($this->canvas, $blockColor, $block['opacity'] ?? 100);
                    break;
                }
            }

            $amplitude = $this->pctToH($params['amplitude_pct'] ?? 3);
            $frequency = $params['frequency'] ?? 2;

            $points = [];
            // Start from top-left of image region
            $points[] = $dstX;
            $points[] = $dstY;

            // Wave along top edge
            for ($x = $dstX; $x <= $dstX + $dstW; $x += 2) {
                $progress = ($x - $dstX) / max(1, $dstW);
                $waveY = $dstY + (int) round($amplitude * sin($progress * $frequency * 2 * M_PI));
                $points[] = $x;
                $points[] = $waveY;
            }

            // Close polygon back to start above
            $points[] = $dstX + $dstW;
            $points[] = $dstY - $amplitude - 2;
            $points[] = $dstX;
            $points[] = $dstY - $amplitude - 2;

            imagefilledpolygon($this->canvas, $points, $bgGd);
        }
    }

    // ─── Overlay ───

    protected function applyOverlay(): void
    {
        $overlay = $this->config['overlay'] ?? null;
        if (!$overlay || ($overlay['type'] ?? '') !== 'gradient') return;

        $stops = $overlay['gradient_stops'] ?? [];
        if (count($stops) < 2) return;

        $direction = $overlay['gradient_direction'] ?? 'to_bottom';

        if ($direction === 'to_bottom' || $direction === 'to_top') {
            $this->drawVerticalGradient($stops, $direction === 'to_top');
        } elseif ($direction === 'to_right' || $direction === 'to_left') {
            $this->drawHorizontalGradient($stops, $direction === 'to_left');
        }
    }

    protected function drawVerticalGradient(array $stops, bool $reverse = false): void
    {
        if ($reverse) {
            $stops = array_reverse($stops);
        }

        // Sort stops by position
        usort($stops, fn($a, $b) => ($a['position_pct'] ?? 0) <=> ($b['position_pct'] ?? 0));

        for ($y = 0; $y < $this->canvasH; $y++) {
            $pct = ($y / $this->canvasH) * 100;

            // Find surrounding stops
            $lower = $stops[0];
            $upper = end($stops);

            for ($i = 0; $i < count($stops) - 1; $i++) {
                if ($pct >= ($stops[$i]['position_pct'] ?? 0) && $pct <= ($stops[$i + 1]['position_pct'] ?? 100)) {
                    $lower = $stops[$i];
                    $upper = $stops[$i + 1];
                    break;
                }
            }

            $lowerPos = $lower['position_pct'] ?? 0;
            $upperPos = $upper['position_pct'] ?? 100;
            $range = max(1, $upperPos - $lowerPos);
            $t = ($pct - $lowerPos) / $range;
            $t = max(0, min(1, $t));

            $lowerRgb = $this->colors->hexToRgb($lower['color'] ?? '#000000');
            $upperRgb = $this->colors->hexToRgb($upper['color'] ?? '#000000');

            $r = (int) round($lowerRgb['r'] + ($upperRgb['r'] - $lowerRgb['r']) * $t);
            $g = (int) round($lowerRgb['g'] + ($upperRgb['g'] - $lowerRgb['g']) * $t);
            $b = (int) round($lowerRgb['b'] + ($upperRgb['b'] - $lowerRgb['b']) * $t);

            $lowerOpacity = $lower['opacity'] ?? 0;
            $upperOpacity = $upper['opacity'] ?? 100;
            $opacity = $lowerOpacity + ($upperOpacity - $lowerOpacity) * $t;

            if ($opacity < 1) continue;

            $gdAlpha = (int) round((100 - $opacity) * 127 / 100);
            $color = imagecolorallocatealpha($this->canvas, $r, $g, $b, $gdAlpha);
            imageline($this->canvas, 0, $y, $this->canvasW - 1, $y, $color);
        }
    }

    protected function drawHorizontalGradient(array $stops, bool $reverse = false): void
    {
        if ($reverse) {
            $stops = array_reverse($stops);
        }

        usort($stops, fn($a, $b) => ($a['position_pct'] ?? 0) <=> ($b['position_pct'] ?? 0));

        for ($x = 0; $x < $this->canvasW; $x++) {
            $pct = ($x / $this->canvasW) * 100;

            $lower = $stops[0];
            $upper = end($stops);

            for ($i = 0; $i < count($stops) - 1; $i++) {
                if ($pct >= ($stops[$i]['position_pct'] ?? 0) && $pct <= ($stops[$i + 1]['position_pct'] ?? 100)) {
                    $lower = $stops[$i];
                    $upper = $stops[$i + 1];
                    break;
                }
            }

            $range = max(1, ($upper['position_pct'] ?? 100) - ($lower['position_pct'] ?? 0));
            $t = max(0, min(1, ($pct - ($lower['position_pct'] ?? 0)) / $range));

            $lRgb = $this->colors->hexToRgb($lower['color'] ?? '#000000');
            $uRgb = $this->colors->hexToRgb($upper['color'] ?? '#000000');

            $r = (int) round($lRgb['r'] + ($uRgb['r'] - $lRgb['r']) * $t);
            $g = (int) round($lRgb['g'] + ($uRgb['g'] - $lRgb['g']) * $t);
            $b = (int) round($lRgb['b'] + ($uRgb['b'] - $lRgb['b']) * $t);

            $opacity = ($lower['opacity'] ?? 0) + (($upper['opacity'] ?? 100) - ($lower['opacity'] ?? 0)) * $t;
            if ($opacity < 1) continue;

            $gdAlpha = (int) round((100 - $opacity) * 127 / 100);
            $color = imagecolorallocatealpha($this->canvas, $r, $g, $b, $gdAlpha);
            imageline($this->canvas, $x, 0, $x, $this->canvasH - 1, $color);
        }
    }

    // ─── Decorations ───

    protected function drawDecorations(): void
    {
        $decorations = $this->config['decorations'] ?? [];

        foreach ($decorations as $dec) {
            $type = $dec['type'] ?? 'bar';

            match ($type) {
                'bar' => $this->drawBar($dec),
                'circle' => $this->drawCircle($dec),
                'wave_separator' => $this->drawWaveSeparator($dec),
                'diagonal' => $this->drawDiagonal($dec),
                'triangle' => $this->drawTriangle($dec),
                default => null,
            };
        }
    }

    protected function drawBar(array $dec): void
    {
        $x = $this->pctToX($dec['x_pct'] ?? 0);
        $y = $this->pctToY($dec['y_pct'] ?? 0);
        $w = $this->pctToW($dec['width_pct'] ?? 10);
        $h = $this->pctToH($dec['height_pct'] ?? 0.4);
        $color = $this->colors->gdAllocate($this->canvas, $dec['color_mode'] ?? 'accent', $dec['opacity'] ?? 100);

        imagefilledrectangle($this->canvas, $x, $y, $x + $w - 1, $y + $h - 1, $color);
    }

    protected function drawCircle(array $dec): void
    {
        $cx = $this->pctToX($dec['cx_pct'] ?? 50);
        $cy = $this->pctToY($dec['cy_pct'] ?? 50);
        $radius = (int) round(($dec['radius_pct'] ?? 5) * min($this->canvasW, $this->canvasH) / 100);
        $color = $this->colors->gdAllocate($this->canvas, $dec['color_mode'] ?? 'accent', $dec['opacity'] ?? 100);

        imagefilledellipse($this->canvas, $cx, $cy, $radius * 2, $radius * 2, $color);
    }

    protected function drawWaveSeparator(array $dec): void
    {
        $yBase = $this->pctToY($dec['y_pct'] ?? 50);
        $amplitude = $this->pctToH($dec['amplitude_pct'] ?? 3);
        $frequency = $dec['frequency'] ?? 2;
        $color = $this->colors->gdAllocate($this->canvas, $dec['color_mode'] ?? 'brand_color', $dec['opacity'] ?? 100);

        // Fill from wave line to bottom of wave with color
        $fillDown = $this->pctToH($dec['fill_height_pct'] ?? 2);

        $points = [];
        for ($x = 0; $x <= $this->canvasW; $x += 2) {
            $progress = $x / max(1, $this->canvasW);
            $waveY = $yBase + (int) round($amplitude * sin($progress * $frequency * 2 * M_PI));
            $points[] = $x;
            $points[] = $waveY;
        }

        // Close polygon downward
        $points[] = $this->canvasW;
        $points[] = $yBase + $fillDown;
        $points[] = 0;
        $points[] = $yBase + $fillDown;

        if (count($points) >= 6) {
            imagefilledpolygon($this->canvas, $points, $color);
        }
    }

    protected function drawDiagonal(array $dec): void
    {
        $color = $this->colors->gdAllocate($this->canvas, $dec['color_mode'] ?? 'brand_color', $dec['opacity'] ?? 100);
        $thickness = $this->pctToH($dec['thickness_pct'] ?? 0.3);

        imagesetthickness($this->canvas, max(1, $thickness));

        $x1 = $this->pctToX($dec['x1_pct'] ?? 0);
        $y1 = $this->pctToY($dec['y1_pct'] ?? 0);
        $x2 = $this->pctToX($dec['x2_pct'] ?? 100);
        $y2 = $this->pctToY($dec['y2_pct'] ?? 100);

        imageline($this->canvas, $x1, $y1, $x2, $y2, $color);
        imagesetthickness($this->canvas, 1);
    }

    protected function drawTriangle(array $dec): void
    {
        $color = $this->colors->gdAllocate($this->canvas, $dec['color_mode'] ?? 'brand_color', $dec['opacity'] ?? 100);

        $points = [
            $this->pctToX($dec['x1_pct'] ?? 0), $this->pctToY($dec['y1_pct'] ?? 0),
            $this->pctToX($dec['x2_pct'] ?? 10), $this->pctToY($dec['y2_pct'] ?? 0),
            $this->pctToX($dec['x3_pct'] ?? 0), $this->pctToY($dec['y3_pct'] ?? 10),
        ];

        imagefilledpolygon($this->canvas, $points, $color);
    }

    // ─── Text Rendering ───

    protected function renderTextRegions(ContentCreative $creative): void
    {
        $regions = $this->config['text_regions'] ?? [];
        $typography = $this->config['typography'] ?? [];
        $baseSize = $typography['base_size_1080'] ?? 48;

        // Scale base size for current canvas width
        $scale = $this->canvasW / 1080;

        foreach ($regions as $key => $region) {
            $text = match ($key) {
                'header' => $creative->header_text ?? '',
                'description' => $creative->description_text ?? '',
                'cta' => $creative->cta_text ?? '',
                default => '',
            };

            if (empty(trim($text))) continue;

            // Check visibility
            $visible = match ($key) {
                'header' => $creative->header_visible ?? true,
                'description' => $creative->desc_visible ?? true,
                'cta' => $creative->cta_visible ?? true,
                default => true,
            };
            if (!$visible) continue;

            $sizeScale = $region['size_scale'] ?? 1.0;
            $fontSize = (int) round($baseSize * $sizeScale * $scale);

            // RTL size adjustment
            if (FontManager::isRtl($text)) {
                $rtlAdjust = $typography['rtl_size_adjust'] ?? 1.05;
                $fontSize = (int) round($fontSize * $rtlAdjust);
            }

            $weight = $region['weight'] ?? 'regular';
            $fontFamily = match ($key) {
                'header' => $creative->header_font ?? 'Roboto',
                'description' => $creative->desc_font ?? 'Roboto',
                'cta' => $creative->cta_font ?? 'Roboto',
                default => 'Roboto',
            };

            $fontWeight = in_array($weight, ['bold', 'semibold']) ? 'bold' : 'regular';
            $fontPath = FontManager::getFontPath($fontFamily, $fontWeight, $text);

            $colorMode = $region['color_mode'] ?? 'light';
            $textColor = $this->colors->gdAllocate($this->canvas, $colorMode);

            $x = $this->pctToX($region['x_pct'] ?? 5);
            $y = $this->pctToY($region['y_pct'] ?? 50);
            $maxWidth = $this->pctToW($region['width_pct'] ?? 90);

            $alignment = $region['alignment'] ?? 'left';
            $lineHeightScale = $region['line_height_scale'] ?? 1.2;
            $transform = $region['transform'] ?? null;

            if ($transform === 'uppercase') {
                $text = mb_strtoupper($text);
            }

            // CTA pill style
            if (($region['style'] ?? null) === 'pill') {
                $this->renderCtaPill($text, $region, $fontSize, $fontPath, $x, $y, $maxWidth, $scale);
                continue;
            }

            // Draw text shadow
            $shadow = $region['shadow'] ?? null;
            if ($shadow) {
                $shadowColor = $this->colors->gdAllocate(
                    $this->canvas,
                    $shadow['color'] ?? '#000000',
                    $shadow['opacity'] ?? 50
                );
                $shadowX = (int) round(($shadow['x'] ?? 0) * $scale);
                $shadowY = (int) round(($shadow['y'] ?? 2) * $scale);

                $this->drawWrappedText(
                    $text, $fontSize, $fontPath, $shadowColor,
                    $x + $shadowX, $y + $shadowY, $maxWidth,
                    $alignment, $lineHeightScale
                );
            }

            // Draw main text
            $this->drawWrappedText(
                $text, $fontSize, $fontPath, $textColor,
                $x, $y, $maxWidth,
                $alignment, $lineHeightScale
            );
        }
    }

    protected function drawWrappedText(string $text, int $fontSize, string $fontPath, int $color, int $x, int $y, int $maxWidth, string $alignment, float $lineHeightScale): void
    {
        $isRtl = FontManager::isRtl($text);
        $lines = $this->wrapText($text, $fontSize, $fontPath, $maxWidth);
        $lineHeight = (int) round($fontSize * $lineHeightScale);

        foreach ($lines as $i => $line) {
            $lineY = $y + ($i + 1) * $lineHeight;
            $lineX = $x;

            // For RTL text, reverse for GD rendering (GD renders L→R only)
            $renderLine = $isRtl ? $this->reverseRtlText($line) : $line;

            $bbox = imagettfbbox($fontSize, 0, $fontPath, $renderLine);
            $lineW = abs($bbox[2] - $bbox[0]);

            if ($isRtl) {
                // RTL: always right-align
                $lineX = $x + $maxWidth - $lineW;
            } elseif ($alignment === 'center') {
                $lineX = $x + (int) round(($maxWidth - $lineW) / 2);
            } elseif ($alignment === 'right') {
                $lineX = $x + $maxWidth - $lineW;
            }

            imagettftext($this->canvas, $fontSize, 0, $lineX, $lineY, $color, $fontPath, $renderLine);
        }
    }

    /**
     * Reverse RTL text for GD rendering. GD's imagettftext() always renders
     * left-to-right, so we must reverse Hebrew/Arabic text to visual order.
     * Preserves embedded LTR runs (numbers, Latin) in correct order.
     */
    protected function reverseRtlText(string $text): string
    {
        // Split into grapheme clusters
        $chars = mb_str_split($text);

        // Simple approach: identify runs of RTL vs LTR characters
        $runs = [];
        $currentRun = '';
        $currentIsRtl = null;

        foreach ($chars as $char) {
            $charIsRtl = (bool) preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $char);
            $isSpace = $char === ' ';

            // Spaces inherit the direction of surrounding text
            if ($isSpace) {
                $currentRun .= $char;
                continue;
            }

            if ($currentIsRtl === null) {
                $currentIsRtl = $charIsRtl;
                $currentRun = $char;
            } elseif ($charIsRtl === $currentIsRtl) {
                $currentRun .= $char;
            } else {
                $runs[] = ['text' => $currentRun, 'rtl' => $currentIsRtl];
                $currentIsRtl = $charIsRtl;
                $currentRun = $char;
            }
        }

        if ($currentRun !== '') {
            $runs[] = ['text' => $currentRun, 'rtl' => $currentIsRtl ?? true];
        }

        // Reverse the order of runs (RTL paragraph direction)
        $runs = array_reverse($runs);

        // Within each RTL run, reverse the characters
        $result = '';
        foreach ($runs as $run) {
            if ($run['rtl']) {
                $runChars = mb_str_split($run['text']);
                $result .= implode('', array_reverse($runChars));
            } else {
                // LTR runs (numbers, Latin) keep their order
                $result .= $run['text'];
            }
        }

        return $result;
    }

    protected function wrapText(string $text, int $fontSize, string $fontPath, int $maxWidth): array
    {
        if (empty(trim($text))) return [];

        $words = preg_split('/\s+/', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $testLine);
            $testWidth = abs($bbox[2] - $bbox[0]);

            if ($testWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    protected function renderCtaPill(string $text, array $region, int $fontSize, string $fontPath, int $x, int $y, int $maxWidth, float $scale): void
    {
        $isRtl = FontManager::isRtl($text);
        $renderText = $isRtl ? $this->reverseRtlText($text) : $text;

        $bbox = imagettfbbox($fontSize, 0, $fontPath, $renderText);
        $textW = abs($bbox[2] - $bbox[0]);
        $textH = abs($bbox[7] - $bbox[1]);

        $paddingH = (int) round(20 * $scale);
        $paddingV = (int) round(12 * $scale);

        $pillW = $textW + $paddingH * 2;
        $pillH = $textH + $paddingV * 2;
        $pillRadius = isset($region['pill_border_radius_pct'])
            ? (int) round($pillH * ($region['pill_border_radius_pct'] / 100))
            : (int) round($pillH / 2);

        // For RTL, position the pill from the right side
        $pillX = $isRtl ? $x + $maxWidth - $pillW : $x;

        $bgColorMode = $region['pill_bg_color_mode'] ?? 'light';
        $bgColor = $this->colors->gdAllocate($this->canvas, $bgColorMode);

        $this->drawRoundedRect($pillX, $y, $pillX + $pillW, $y + $pillH, $pillRadius, $bgColor);

        $textColorMode = $region['pill_text_color_mode'] ?? 'brand_color';
        $textColor = $this->colors->gdAllocate($this->canvas, $textColorMode);

        $textX = $pillX + $paddingH;
        $textY = $y + $paddingV + $textH;

        imagettftext($this->canvas, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $renderText);
    }

    // ─── Helpers ───

    protected function drawRoundedRect(int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $radius = min($radius, (int) round(abs($x2 - $x1) / 2), (int) round(abs($y2 - $y1) / 2));

        if ($radius < 2) {
            imagefilledrectangle($this->canvas, $x1, $y1, $x2, $y2, $color);
            return;
        }

        // Fill main rectangles
        imagefilledrectangle($this->canvas, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($this->canvas, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        // Fill corners with arcs
        imagefilledellipse($this->canvas, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->canvas, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->canvas, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->canvas, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    protected function saveComposite(ContentCreative $creative): ?array
    {
        $teamId = $creative->team_id;
        $filename = "content-studio/{$teamId}/composite_" . uniqid() . '.jpg';
        $savePath = Storage::disk('public')->path($filename);

        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagejpeg($this->canvas, $savePath, 92);

        $url = url('/public/storage/' . $filename);

        return ['path' => $filename, 'url' => $url];
    }

    protected function pctToX(float $pct): int
    {
        return (int) round($pct * $this->canvasW / 100);
    }

    protected function pctToY(float $pct): int
    {
        return (int) round($pct * $this->canvasH / 100);
    }

    protected function pctToW(float $pct): int
    {
        return (int) round($pct * $this->canvasW / 100);
    }

    protected function pctToH(float $pct): int
    {
        return (int) round($pct * $this->canvasH / 100);
    }
}
