<?php

namespace Modules\AppAIContents\Services;

class ColorResolver
{
    protected array $brandColors = [];

    public function __construct(array $brandColors = [])
    {
        $this->brandColors = $brandColors;
    }

    public function resolve(string $colorMode, int $opacity = 100): array
    {
        $hex = $this->resolveHex($colorMode);
        $rgb = $this->hexToRgb($hex);

        return [
            'hex' => $hex,
            'r' => $rgb['r'],
            'g' => $rgb['g'],
            'b' => $rgb['b'],
            'a' => (int) round($opacity * 1.27), // 0-127 for GD (127=fully transparent)
            'opacity' => $opacity,
        ];
    }

    public function resolveHex(string $colorMode): string
    {
        // Direct hex color passthrough
        if (str_starts_with($colorMode, '#')) {
            return $colorMode;
        }

        return match ($colorMode) {
            'brand_color' => $this->brandColors[0] ?? '#DA291C',
            'brand_secondary' => $this->brandColors[1] ?? ($this->brandColors[0] ?? '#1a1a2e'),
            'brand_light' => $this->lighten($this->brandColors[0] ?? '#DA291C', 40),
            'accent' => $this->lighten($this->brandColors[0] ?? '#DA291C', 20),
            'light' => '#ffffff',
            'dark' => '#111111',
            'white' => '#ffffff',
            'black' => '#000000',
            'muted' => '#888888',
            'transparent' => '#000000',
            default => $colorMode,
        };
    }

    public function resolveRgba(string $colorMode, int $opacity = 100): string
    {
        $color = $this->resolve($colorMode, $opacity);
        $a = $opacity / 100;
        return "rgba({$color['r']},{$color['g']},{$color['b']},{$a})";
    }

    public function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    public function lighten(string $hex, int $percent): string
    {
        $rgb = $this->hexToRgb($hex);
        $r = min(255, $rgb['r'] + (int) round((255 - $rgb['r']) * $percent / 100));
        $g = min(255, $rgb['g'] + (int) round((255 - $rgb['g']) * $percent / 100));
        $b = min(255, $rgb['b'] + (int) round((255 - $rgb['b']) * $percent / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public function darken(string $hex, int $percent): string
    {
        $rgb = $this->hexToRgb($hex);
        $r = max(0, $rgb['r'] - (int) round($rgb['r'] * $percent / 100));
        $g = max(0, $rgb['g'] - (int) round($rgb['g'] * $percent / 100));
        $b = max(0, $rgb['b'] - (int) round($rgb['b'] * $percent / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public function gdAllocate($image, string $colorMode, int $opacity = 100): int
    {
        $color = $this->resolve($colorMode, $opacity);

        if ($opacity < 100) {
            // GD alpha: 0 = opaque, 127 = transparent
            $gdAlpha = (int) round((100 - $opacity) * 127 / 100);
            return imagecolorallocatealpha($image, $color['r'], $color['g'], $color['b'], $gdAlpha);
        }

        return imagecolorallocate($image, $color['r'], $color['g'], $color['b']);
    }
}
