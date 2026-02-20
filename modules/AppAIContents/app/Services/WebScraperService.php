<?php

namespace Modules\AppAIContents\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebScraperService
{
    public function scrape(string $url): array
    {
        try {
            // Primary: direct HTTP scrape
            $result = $this->directScrape($url);

            // If direct scrape returned too little text, fall back to Jina Reader
            if (mb_strlen($result['text_content']) < 100) {
                Log::info('WebScraperService: direct scrape returned minimal content, trying Jina Reader', ['url' => $url]);
                $jinaResult = $this->jinaScrape($url);
                if ($jinaResult && mb_strlen($jinaResult['text_content']) > mb_strlen($result['text_content'])) {
                    return $jinaResult;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            // If direct scrape failed entirely, try Jina as last resort
            Log::warning('WebScraperService: direct scrape failed, trying Jina Reader', ['url' => $url, 'error' => $e->getMessage()]);
            $jinaResult = $this->jinaScrape($url);
            if ($jinaResult) {
                return $jinaResult;
            }

            Log::error('WebScraperService::scrape failed completely', ['url' => $url, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function directScrape(string $url): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Cache-Control' => 'no-cache',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->withOptions([
                'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                'verify' => false,
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
            'logo' => $this->extractLogo($html, $baseUrl),
            'colors' => $this->extractColors($html),
            'fonts' => $this->extractFonts($html),
            'text_content' => $this->extractTextContent($html),
            'language_code' => $this->extractLanguage($html),
        ];
    }

    /**
     * Fallback scraper using Jina Reader API for JS-rendered/bot-protected sites.
     */
    protected function jinaScrape(string $url): ?array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders(['Accept' => 'text/plain'])
                ->get('https://r.jina.ai/' . $url);

            if (!$response->successful()) {
                return null;
            }

            $markdown = $response->body();
            if (mb_strlen($markdown) < 50) {
                return null;
            }

            $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

            // Extract title from Jina's "Title: ..." header line
            $title = '';
            if (preg_match('/^Title:\s*(.+)$/m', $markdown, $m)) {
                $title = trim($m[1]);
            }

            // Extract images from markdown ![alt](url) patterns
            $images = [];
            preg_match_all('/!\[(?:Image \d+:\s*)?([^\]]*)\]\(([^)]+)\)/i', $markdown, $imgMatches, PREG_SET_ORDER);
            foreach ($imgMatches as $img) {
                $imgUrl = trim($img[2]);
                if ($imgUrl && $this->isValidImageUrl($imgUrl)) {
                    $images[] = [
                        'url' => $this->resolveUrl($imgUrl, $baseUrl),
                        'source' => 'jina',
                        'width' => 0,
                        'height' => 0,
                    ];
                }
            }

            // Extract text content (strip markdown syntax)
            $text = $markdown;
            // Remove the Jina header lines
            $text = preg_replace('/^(Title|URL Source|Markdown Content):.*$/m', '', $text);
            // Remove markdown links but keep text
            $text = preg_replace('/\[([^\]]*)\]\([^)]+\)/', '$1', $text);
            // Remove image references
            $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text);
            // Remove markdown heading markers
            $text = preg_replace('/^#{1,6}\s+/m', '', $text);
            // Remove emphasis markers
            $text = preg_replace('/[*_]{1,3}/', '', $text);
            // Collapse whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            $text = mb_substr(trim($text), 0, 5000);

            // Try to detect language from title/text
            $language = '';
            // Check for Hebrew characters
            if (preg_match('/[\x{0590}-\x{05FF}]/u', $title . $text)) {
                $language = 'he';
            } elseif (preg_match('/[\x{0600}-\x{06FF}]/u', $title . $text)) {
                $language = 'ar';
            } elseif (preg_match('/[\x{4E00}-\x{9FFF}]/u', $title . $text)) {
                $language = 'zh';
            } elseif (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $title . $text)) {
                $language = 'ja';
            }

            // Extract logo: first image with "logo" in its alt text, or first image overall
            $logo = null;
            foreach ($imgMatches as $img) {
                if (stripos($img[1], 'logo') !== false) {
                    $logo = $this->resolveUrl(trim($img[2]), $baseUrl);
                    break;
                }
            }

            return [
                'html' => $markdown,
                'url' => $url,
                'base_url' => $baseUrl,
                'title' => $title,
                'meta' => ['description' => mb_substr($text, 0, 300)],
                'images' => array_slice($images, 0, 30),
                'logo' => $logo,
                'colors' => [],
                'fonts' => [],
                'text_content' => $text,
                'language_code' => $language,
            ];
        } catch (\Throwable $e) {
            Log::warning('WebScraperService: Jina Reader fallback failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
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
        // Match both name="..." content="..." and property="..." content="..." (in either order)
        preg_match_all('/<meta\s+(?:name|property)=["\']([^"\']+)["\']\s+content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $meta[strtolower($match[1])] = $match[2];
        }
        // Also match content="..." before name/property (some sites use reverse order)
        preg_match_all('/<meta\s+content=["\']([^"\']*)["\'][\s]+(?:name|property)=["\']([^"\']+)["\'][^>]*>/i', $html, $matches2, PREG_SET_ORDER);
        foreach ($matches2 as $match) {
            $meta[strtolower($match[2])] = $match[1];
        }
        return $meta;
    }

    /**
     * Smart image extraction — collects from multiple sources and pre-filters junk.
     * Returns array of {url, source, width, height} objects.
     */
    protected function extractImages(string $html, string $baseUrl): array
    {
        $candidates = [];

        // 1. og:image (high priority — usually the hero/brand image)
        preg_match_all('/<meta\s+(?:property|name)=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $ogMatches);
        if (empty($ogMatches[1])) {
            preg_match_all('/<meta\s+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']og:image["\'][^>]*>/i', $html, $ogMatches);
        }
        foreach ($ogMatches[1] ?? [] as $src) {
            $candidates[] = ['url' => $this->resolveUrl($src, $baseUrl), 'source' => 'og:image', 'width' => 0, 'height' => 0];
        }

        // 2. twitter:image
        preg_match_all('/<meta\s+(?:property|name)=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $twMatches);
        if (empty($twMatches[1])) {
            preg_match_all('/<meta\s+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']twitter:image["\'][^>]*>/i', $html, $twMatches);
        }
        foreach ($twMatches[1] ?? [] as $src) {
            $candidates[] = ['url' => $this->resolveUrl($src, $baseUrl), 'source' => 'twitter:image', 'width' => 0, 'height' => 0];
        }

        // 3. <img> tags — extract src, data-src, data-lazy-src, srcset, and dimension attributes
        preg_match_all('/<img\s[^>]*>/i', $html, $imgTags);
        foreach ($imgTags[0] as $imgTag) {
            // Skip if class/id contains icon/flag/emoji/avatar keywords
            if ($this->hasJunkClass($imgTag)) {
                continue;
            }

            // Extract width/height attributes
            $w = 0;
            $h = 0;
            if (preg_match('/\bwidth=["\']?(\d+)/i', $imgTag, $wm)) $w = (int)$wm[1];
            if (preg_match('/\bheight=["\']?(\d+)/i', $imgTag, $hm)) $h = (int)$hm[1];

            // Skip tiny images (both dimensions set and both < 80px)
            if ($w > 0 && $h > 0 && $w < 80 && $h < 80) {
                continue;
            }

            // Try srcset first (pick largest)
            $srcFromSrcset = $this->extractLargestFromSrcset($imgTag, $baseUrl);

            // Try data-src / data-lazy-src (lazy-loaded images)
            $lazySrc = null;
            if (preg_match('/\bdata-(?:lazy-)?src=["\']([^"\']+)["\']/', $imgTag, $lm)) {
                $lazySrc = $this->resolveUrl($lm[1], $baseUrl);
            }

            // Regular src
            $regularSrc = null;
            if (preg_match('/\bsrc=["\']([^"\']+)["\']/', $imgTag, $sm)) {
                $regularSrc = $this->resolveUrl($sm[1], $baseUrl);
            }

            // Pick best source: srcset > data-src > src
            $bestSrc = $srcFromSrcset ?? $lazySrc ?? $regularSrc;
            if ($bestSrc && $this->isValidImageUrl($bestSrc)) {
                $candidates[] = ['url' => $bestSrc, 'source' => 'img', 'width' => $w, 'height' => $h];
            }
        }

        // 4. background-image from <style> blocks and inline styles
        preg_match_all('/background(?:-image)?\s*:\s*url\(["\']?([^)"\']+)["\']?\)/i', $html, $bgMatches);
        foreach ($bgMatches[1] ?? [] as $src) {
            $resolved = $this->resolveUrl($src, $baseUrl);
            if ($this->isValidImageUrl($resolved)) {
                $candidates[] = ['url' => $resolved, 'source' => 'background', 'width' => 0, 'height' => 0];
            }
        }

        // Deduplicate by URL
        $seen = [];
        $unique = [];
        foreach ($candidates as $c) {
            $key = rtrim($c['url'], '/');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $c;
            }
        }

        return array_slice($unique, 0, 30); // Return up to 30 candidates for server-side validation
    }

    /**
     * Extract the site's logo URL.
     * Priority: apple-touch-icon > img with logo class/id/alt > link rel=icon
     */
    public function extractLogo(string $html, string $baseUrl): ?string
    {
        // 1. apple-touch-icon (best — high-res square)
        if (preg_match('/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            return $this->resolveUrl($m[1], $baseUrl);
        }
        // Reverse attribute order
        if (preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']apple-touch-icon["\'][^>]*>/i', $html, $m)) {
            return $this->resolveUrl($m[1], $baseUrl);
        }

        // 2. <img> with class/id/alt containing "logo"
        if (preg_match('/<img[^>]+(?:class|id|alt)=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            return $this->resolveUrl($m[1], $baseUrl);
        }
        // Also try src before class/id/alt
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]+(?:class|id|alt)=["\'][^"\']*logo[^"\']*["\'][^>]*>/i', $html, $m)) {
            return $this->resolveUrl($m[1], $baseUrl);
        }

        // 3. <link rel="icon"> (fallback)
        if (preg_match('/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            return $this->resolveUrl($m[1], $baseUrl);
        }
        if (preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:shortcut )?icon["\'][^>]*>/i', $html, $m)) {
            return $this->resolveUrl($m[1], $baseUrl);
        }

        return null;
    }

    /**
     * Extract the largest image URL from a srcset attribute.
     */
    protected function extractLargestFromSrcset(string $imgTag, string $baseUrl): ?string
    {
        if (!preg_match('/\bsrcset=["\']([^"\']+)["\']/', $imgTag, $m)) {
            return null;
        }

        $entries = array_map('trim', explode(',', $m[1]));
        $best = null;
        $bestSize = 0;

        foreach ($entries as $entry) {
            $parts = preg_split('/\s+/', trim($entry));
            if (empty($parts[0])) continue;

            $src = $parts[0];
            $descriptor = $parts[1] ?? '1x';

            // Parse width descriptor (e.g., "800w") or density (e.g., "2x")
            $size = 0;
            if (preg_match('/(\d+)w/', $descriptor, $wm)) {
                $size = (int)$wm[1];
            } elseif (preg_match('/([\d.]+)x/', $descriptor, $xm)) {
                $size = (int)((float)$xm[1] * 100);
            }

            if ($size > $bestSize) {
                $bestSize = $size;
                $best = $src;
            }
        }

        return $best ? $this->resolveUrl($best, $baseUrl) : null;
    }

    /**
     * Check if an img tag's class/id suggests it's a junk image.
     */
    protected function hasJunkClass(string $imgTag): bool
    {
        $junkPatterns = ['icon', 'flag', 'emoji', 'avatar', 'sprite', 'pixel', 'tracking', 'badge', 'bullet', 'arrow'];

        // Check class attribute
        if (preg_match('/\bclass=["\']([^"\']+)["\']/', $imgTag, $m)) {
            $classes = strtolower($m[1]);
            foreach ($junkPatterns as $pattern) {
                if (str_contains($classes, $pattern)) return true;
            }
        }

        // Check id attribute
        if (preg_match('/\bid=["\']([^"\']+)["\']/', $imgTag, $m)) {
            $id = strtolower($m[1]);
            foreach ($junkPatterns as $pattern) {
                if (str_contains($id, $pattern)) return true;
            }
        }

        // Check alt text for flag/icon-like content
        if (preg_match('/\balt=["\']([^"\']+)["\']/', $imgTag, $m)) {
            $alt = strtolower($m[1]);
            // Skip if alt is just a country code or flag name
            if (preg_match('/^(flag|icon|arrow|bullet|pixel)\b/', $alt)) return true;
        }

        return false;
    }

    /**
     * Pre-filter: check if a URL is likely a valid brand image (not junk).
     */
    protected function isValidImageUrl(string $url): bool
    {
        // Skip data URIs
        if (str_starts_with($url, 'data:')) return false;

        $lower = strtolower($url);

        // Skip SVGs (usually icons, not brand photos)
        if (str_contains($lower, '.svg')) return false;

        // Skip known junk URL patterns
        $junkPatterns = [
            '/flags/', '/icons/', '/sprites/', '/emoji/',
            'flagcdn.com', 'flagsapi.com',
            '/pixel', '/tracking', '/beacon',
            'spacer.gif', 'blank.gif', 'transparent.gif',
            '1x1.', '/1x1',
        ];
        foreach ($junkPatterns as $pattern) {
            if (str_contains($lower, $pattern)) return false;
        }

        return true;
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
