<?php

namespace Modules\AppAIContents\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebScraperService
{
    public function scrape(string $url): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; ARTimeBot/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch URL: HTTP {$response->status()}");
            }

            $html = $response->body();
            $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

            return [
                'html' => $html,
                'url' => $url,
                'base_url' => $baseUrl,
                'title' => $this->extractTitle($html),
                'meta' => $this->extractMeta($html),
                'images' => $this->extractImages($html, $baseUrl),
                'colors' => $this->extractColors($html),
                'fonts' => $this->extractFonts($html),
                'text_content' => $this->extractTextContent($html),
                'language_code' => $this->extractLanguage($html),
            ];
        } catch (\Throwable $e) {
            Log::error('WebScraperService::scrape failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function extractTitle(string $html): string
    {
        preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches);
        return trim($matches[1] ?? '');
    }

    protected function extractMeta(string $html): array
    {
        $meta = [];
        preg_match_all('/<meta\s+(?:name|property)=["\']([^"\']+)["\']\s+content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $meta[strtolower($match[1])] = $match[2];
        }
        return $meta;
    }

    protected function extractImages(string $html, string $baseUrl): array
    {
        $images = [];

        // <img> tags
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        foreach ($matches[1] as $src) {
            $images[] = $this->resolveUrl($src, $baseUrl);
        }

        // og:image
        preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\'][^>]*>/i', $html, $ogMatch);
        if (!empty($ogMatch[1])) {
            array_unshift($images, $this->resolveUrl($ogMatch[1], $baseUrl));
        }

        return array_unique(array_slice($images, 0, 20));
    }

    protected function extractColors(string $html): array
    {
        $colors = [];
        // Find hex colors in inline styles and style blocks
        preg_match_all('/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/', $html, $matches);
        foreach ($matches[0] as $color) {
            $hex = strtolower($color);
            if (!in_array($hex, ['#000000', '#ffffff', '#fff', '#000'])) {
                $colors[$hex] = ($colors[$hex] ?? 0) + 1;
            }
        }
        arsort($colors);
        return array_slice(array_keys($colors), 0, 6);
    }

    protected function extractFonts(string $html): array
    {
        $fonts = [];

        // Google Fonts links
        preg_match_all('/fonts\.googleapis\.com\/css2?\?family=([^&"\']+)/i', $html, $matches);
        foreach ($matches[1] as $family) {
            $name = urldecode(explode(':', $family)[0]);
            $name = str_replace('+', ' ', $name);
            $fonts[] = ['name' => $name, 'category' => 'web'];
        }

        // font-family declarations
        preg_match_all('/font-family:\s*["\']?([^;"\']+)/i', $html, $ffMatches);
        foreach ($ffMatches[1] as $ff) {
            $name = trim(explode(',', $ff)[0], " \t\n\r\0\x0B\"'");
            if ($name && !in_array(strtolower($name), ['inherit', 'initial', 'sans-serif', 'serif', 'monospace'])) {
                $fonts[] = ['name' => $name, 'category' => 'css'];
            }
        }

        return array_values(array_unique($fonts, SORT_REGULAR));
    }

    protected function extractTextContent(string $html): string
    {
        $text = strip_tags(preg_replace('/<(script|style|nav|footer|header)[^>]*>.*?<\/\1>/si', '', $html));
        $text = preg_replace('/\s+/', ' ', $text);
        return mb_substr(trim($text), 0, 5000);
    }

    protected function extractLanguage(string $html): string
    {
        // Try <html lang="...">
        if (preg_match('/<html[^>]+lang=["\']([^"\']+)["\'][^>]*>/i', $html, $match)) {
            return strtolower(explode('-', trim($match[1]))[0]); // "en-US" → "en"
        }

        // Try <meta http-equiv="content-language">
        if (preg_match('/<meta\s+http-equiv=["\']content-language["\']\s+content=["\']([^"\']+)["\'][^>]*>/i', $html, $match)) {
            return strtolower(explode('-', trim($match[1]))[0]);
        }

        return ''; // Unknown — AI will detect from content
    }

    protected function resolveUrl(string $src, string $baseUrl): string
    {
        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            return $src;
        }
        if (str_starts_with($src, '//')) {
            return 'https:' . $src;
        }
        if (str_starts_with($src, '/')) {
            return rtrim($baseUrl, '/') . $src;
        }
        return rtrim($baseUrl, '/') . '/' . $src;
    }
}
