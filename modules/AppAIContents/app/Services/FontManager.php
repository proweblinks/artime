<?php

namespace Modules\AppAIContents\Services;

class FontManager
{
    protected static array $fontMap = [
        // Available TTF fonts in resources/fonts/
        'NotoSans-Regular' => 'NotoSans-Regular.ttf',
        'NotoSans-Bold' => 'NotoSans-Bold.ttf',
    ];

    // System fonts for RTL languages (Hebrew, Arabic)
    // DejaVuSans is preferred: it has Hebrew + Latin + numbers + punctuation
    // DroidSansHebrew only has Hebrew script (numbers/punctuation render as □)
    protected static array $systemRtlFonts = [
        'regular' => [
            '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans.ttf',
            '/usr/share/fonts/google-droid-sans-fonts/DroidSansHebrew-Regular.ttf',
        ],
        'bold' => [
            '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/google-droid-sans-fonts/DroidSansHebrew-Bold.ttf',
        ],
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

    /**
     * Get font path, with RTL-aware fallback to system Hebrew fonts.
     */
    public static function getFontPath(string $fontName, string $weight = 'regular', ?string $text = null): string
    {
        // If text is RTL, use system Hebrew/Arabic font
        if ($text && self::isRtl($text)) {
            return self::getRtlFontPath($weight);
        }

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

    /**
     * Get a system font that supports RTL scripts (Hebrew, Arabic).
     */
    public static function getRtlFontPath(string $weight = 'regular'): string
    {
        $candidates = self::$systemRtlFonts[$weight] ?? self::$systemRtlFonts['regular'];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Final fallback to bundled NotoSans (may render tofu)
        $fontsDir = resource_path('fonts');
        return $fontsDir . '/' . ($weight === 'bold' ? 'NotoSans-Bold.ttf' : 'NotoSans-Regular.ttf');
    }

    public static function resolveFromPreference(string $preference, ?string $text = null): string
    {
        $weight = self::$preferenceMap[$preference] ?? 'regular';

        if ($text && self::isRtl($text)) {
            return self::getRtlFontPath($weight);
        }

        return self::getFontPath('NotoSans', $weight);
    }

    public static function getHeaderFont(array $config, string $fontFamily = 'Roboto', ?string $text = null): string
    {
        $preference = $config['typography']['header_font_preference'] ?? 'bold_sans';
        $weight = self::$preferenceMap[$preference] ?? 'bold';

        if ($text && self::isRtl($text)) {
            return self::getRtlFontPath($weight);
        }

        return self::getFontPath($fontFamily, $weight);
    }

    public static function getBodyFont(array $config, string $fontFamily = 'Roboto', ?string $text = null): string
    {
        $preference = $config['typography']['body_font_preference'] ?? 'regular_sans';
        $weight = self::$preferenceMap[$preference] ?? 'regular';

        if ($text && self::isRtl($text)) {
            return self::getRtlFontPath($weight);
        }

        return self::getFontPath($fontFamily, $weight);
    }

    public static function isRtl(string $text): bool
    {
        // Detect RTL by checking for Hebrew, Arabic, Persian characters
        return (bool) preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }
}
