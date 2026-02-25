<?php

namespace Modules\AppAIContents\Services;

class FontManager
{
    protected static array $fontMap = [
        // Available TTF fonts
        'NotoSans-Regular' => 'NotoSans-Regular.ttf',
        'NotoSans-Bold' => 'NotoSans-Bold.ttf',
    ];

    // Map Google Font names and weight preferences to local TTF files
    protected static array $familyMap = [
        'Roboto' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
        'Inter' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
        'Playfair Display' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
        'Montserrat' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
        'Open Sans' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
        'Lato' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
        'Poppins' => ['regular' => 'NotoSans-Regular', 'bold' => 'NotoSans-Bold'],
    ];

    // Map preference tokens from template config to weight
    protected static array $preferenceMap = [
        'bold_sans' => 'bold',
        'regular_sans' => 'regular',
        'bold_serif' => 'bold',
        'regular_serif' => 'regular',
        'semibold_sans' => 'bold',
        'light_sans' => 'regular',
    ];

    public static function getFontPath(string $fontName, string $weight = 'regular'): string
    {
        $fontsDir = resource_path('fonts');

        // Direct font file name
        if (isset(self::$fontMap[$fontName])) {
            return $fontsDir . '/' . self::$fontMap[$fontName];
        }

        // Font family name (e.g., 'Roboto')
        $family = self::$familyMap[$fontName] ?? null;
        if ($family) {
            $key = $family[$weight] ?? $family['regular'];
            $file = self::$fontMap[$key] ?? 'NotoSans-Regular.ttf';
            return $fontsDir . '/' . $file;
        }

        // Fallback: NotoSans based on weight
        $fallback = $weight === 'bold' ? 'NotoSans-Bold.ttf' : 'NotoSans-Regular.ttf';
        return $fontsDir . '/' . $fallback;
    }

    public static function resolveFromPreference(string $preference): string
    {
        $weight = self::$preferenceMap[$preference] ?? 'regular';
        return self::getFontPath('NotoSans', $weight);
    }

    public static function getHeaderFont(array $config, string $fontFamily = 'Roboto'): string
    {
        $preference = $config['typography']['header_font_preference'] ?? 'bold_sans';
        $weight = self::$preferenceMap[$preference] ?? 'bold';
        return self::getFontPath($fontFamily, $weight);
    }

    public static function getBodyFont(array $config, string $fontFamily = 'Roboto'): string
    {
        $preference = $config['typography']['body_font_preference'] ?? 'regular_sans';
        $weight = self::$preferenceMap[$preference] ?? 'regular';
        return self::getFontPath($fontFamily, $weight);
    }

    public static function isRtl(string $text): bool
    {
        // Detect RTL by checking for Hebrew, Arabic, Persian characters
        return (bool) preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }
}
