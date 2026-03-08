<?php

namespace Modules\AppVideoWizard\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

class ScreenplayImportService
{
    /**
     * Words-per-minute for dialogue duration estimation.
     */
    private const WPM = 140;

    /**
     * Target segment duration in seconds.
     */
    private const TARGET_SEGMENT_SECONDS = 8.0;

    /**
     * Max words per dialogue segment (~10s at 140 WPM).
     */
    private const MAX_SEGMENT_WORDS = 23;

    /**
     * OpenAI voice pool for auto-assignment.
     */
    private const VOICE_POOL = ['echo', 'nova', 'onyx', 'shimmer', 'fable', 'alloy'];

    /**
     * Main entry point — parse file, split scenes, build template, generate transcript.
     */
    public function import(string $content, string $fileType, string $aspectRatio = '16:9'): array
    {
        $parsed = ($fileType === 'html')
            ? $this->parseHtml($content)
            : $this->parsePlainText($content);

        $allSegments = [];
        foreach ($parsed['scenes'] as $scene) {
            $segments = $this->splitSceneIntoSegments($scene);
            $allSegments = array_merge($allSegments, $segments);
        }

        $template = $this->buildSyntheticTemplate($parsed, $aspectRatio);
        $transcript = $this->toTranscript($allSegments);

        return [
            'title' => $parsed['title'] ?? 'Imported Screenplay',
            'transcript' => $transcript,
            'segments' => $allSegments,
            'template' => $template,
            'scene_count' => count($allSegments),
            'estimated_duration' => array_sum(array_column($allSegments, 'estimated_duration')),
        ];
    }

    // ──────────────────────────────────────────────────
    // 1A. HTML Parsing
    // ──────────────────────────────────────────────────

    /**
     * Parse an HTML screenplay file into structured data.
     */
    public function parseHtml(string $html): array
    {
        $dom = new DOMDocument();
        // Prepend XML encoding declaration to preserve UTF-8 characters (em-dashes, etc.)
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);

        // Extract title from <title> tag or .main-title element
        $title = 'Untitled';
        $titleNode = $xpath->query('//title')->item(0);
        if ($titleNode) {
            $raw = trim($this->utf8($titleNode->textContent));
            // Strip suffixes like " — A Screenplay"
            $title = preg_replace('/\s*[—–-]\s*A\s+Screenplay.*/i', '', $raw) ?: $raw;
        }

        // Find all scene containers
        $sceneNodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' scene ')]");

        $scenes = [];
        $allCharacters = [];
        $sceneNumber = 0;

        foreach ($sceneNodes as $sceneNode) {
            $sceneNumber++;
            $blocks = [];
            $slug = '';
            $locationType = 'interior';
            $locationName = '';

            foreach ($sceneNode->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $classes = $child->getAttribute('class') ?? '';
                $text = trim($this->utf8($child->textContent));

                if (empty($text)) {
                    continue;
                }

                if ($this->hasClass($classes, 'slug')) {
                    $slug = $text;
                    if (preg_match('/^(INT|EXT)\.?\s*/i', $text, $m)) {
                        $locationType = strtolower($m[1]) === 'ext' ? 'exterior' : 'interior';
                    }
                    // Extract location name: remove INT./EXT. prefix and time suffix
                    $locationName = preg_replace('/^(INT|EXT)\.?\s*/i', '', $text);
                    $locationName = preg_replace('/\s*[—–-]\s*(CONTINUOUS|NIGHT|DAY|DAWN|DUSK|LATER|MOMENTS LATER|SAME TIME).*$/i', '', $locationName);
                    $locationName = trim($locationName);
                    continue;
                }

                if ($this->hasClass($classes, 'scene-number')) {
                    continue; // Skip scene number labels
                }

                if ($this->hasClass($classes, 'char-name')) {
                    // Character name — look ahead for parenthetical and dialogue
                    $charName = strtoupper(trim($text));
                    if (!in_array($charName, $allCharacters)) {
                        $allCharacters[] = $charName;
                    }

                    // Peek at next sibling(s) for parenthetical and dialogue
                    $parenthetical = null;
                    $dialogue = '';
                    $next = $child->nextSibling;
                    while ($next && $next->nodeType !== XML_ELEMENT_NODE) {
                        $next = $next->nextSibling;
                    }
                    if ($next) {
                        $nextClasses = $next->getAttribute('class') ?? '';
                        if ($this->hasClass($nextClasses, 'parenthetical')) {
                            $parenthetical = trim($next->textContent);
                            // Move to dialogue
                            $next = $next->nextSibling;
                            while ($next && $next->nodeType !== XML_ELEMENT_NODE) {
                                $next = $next->nextSibling;
                            }
                        }
                        // The dialogue block will be consumed when we encounter it
                    }

                    $blocks[] = [
                        'type' => 'character',
                        'character' => $charName,
                        'parenthetical' => $parenthetical,
                    ];
                    continue;
                }

                if ($this->hasClass($classes, 'parenthetical')) {
                    // Already consumed by char-name lookahead, skip standalone
                    continue;
                }

                if ($this->hasClass($classes, 'dialogue')) {
                    // Attach to previous character block
                    $lastIdx = count($blocks) - 1;
                    if ($lastIdx >= 0 && $blocks[$lastIdx]['type'] === 'character') {
                        $blocks[$lastIdx] = [
                            'type' => 'dialogue',
                            'character' => $blocks[$lastIdx]['character'],
                            'parenthetical' => $blocks[$lastIdx]['parenthetical'] ?? null,
                            'text' => $text,
                        ];
                    } else {
                        // Orphan dialogue — treat as action
                        $blocks[] = ['type' => 'action', 'text' => $text];
                    }
                    continue;
                }

                if ($this->hasClass($classes, 'fight-block') || $this->hasClass($classes, 'fight-impact')) {
                    // Extract all text from fight block (may contain nested .action divs)
                    $fightText = $this->extractAllText($child);
                    if (!empty($fightText)) {
                        $blocks[] = ['type' => 'fight', 'text' => $fightText];
                    }
                    continue;
                }

                if ($this->hasClass($classes, 'transition')) {
                    $blocks[] = ['type' => 'transition', 'text' => $text];
                    continue;
                }

                if ($this->hasClass($classes, 'action')) {
                    $blocks[] = ['type' => 'action', 'text' => $text];
                    continue;
                }

                // Unknown class — treat as action if it has text
                $blocks[] = ['type' => 'action', 'text' => $text];
            }

            if (!empty($blocks) || !empty($slug)) {
                $scenes[] = [
                    'number' => $sceneNumber,
                    'slug' => $slug,
                    'location_type' => $locationType,
                    'location_name' => $locationName,
                    'blocks' => $blocks,
                ];
            }
        }

        return [
            'title' => $title,
            'scenes' => $scenes,
            'characters' => $allCharacters,
        ];
    }

    // ──────────────────────────────────────────────────
    // 1B. Plain Text Parsing
    // ──────────────────────────────────────────────────

    /**
     * Parse a plain text screenplay using standard format conventions.
     */
    public function parsePlainText(string $text): array
    {
        $lines = preg_split('/\r?\n/', $text);
        $scenes = [];
        $currentScene = null;
        $allCharacters = [];
        $sceneNumber = 0;
        $title = 'Untitled';

        // Try to detect title from first few lines
        foreach (array_slice($lines, 0, 10) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            // Title is often the first centered, all-caps, non-slug line
            if (preg_match('/^[A-Z][A-Z\s\-:]+$/', $line) && !preg_match('/^(INT|EXT|FADE|CUT|SMASH)/i', $line)) {
                $title = ucwords(strtolower($line));
                break;
            }
        }

        $i = 0;
        while ($i < count($lines)) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // Empty line
            if (empty($trimmed)) {
                $i++;
                continue;
            }

            // Scene heading (INT./EXT.)
            if (preg_match('/^(INT|EXT)\.?\s+/i', $trimmed)) {
                // Save previous scene
                if ($currentScene !== null) {
                    $scenes[] = $currentScene;
                }

                $sceneNumber++;
                $locationType = preg_match('/^EXT/i', $trimmed) ? 'exterior' : 'interior';
                $locationName = preg_replace('/^(INT|EXT)\.?\s*/i', '', $trimmed);
                $locationName = preg_replace('/\s*[—–-]\s*(CONTINUOUS|NIGHT|DAY|DAWN|DUSK|LATER|MOMENTS LATER|SAME TIME).*$/i', '', $locationName);

                $currentScene = [
                    'number' => $sceneNumber,
                    'slug' => $trimmed,
                    'location_type' => $locationType,
                    'location_name' => trim($locationName),
                    'blocks' => [],
                ];
                $i++;
                continue;
            }

            // Transition lines (CUT TO:, FADE TO:, SMASH CUT TO:, etc.)
            if (preg_match('/^(CUT TO|FADE TO|SMASH CUT TO|DISSOLVE TO|MATCH CUT TO|FADE OUT|FADE IN)\s*:?\s*$/i', $trimmed)) {
                if ($currentScene !== null) {
                    $currentScene['blocks'][] = ['type' => 'transition', 'text' => $trimmed];
                }
                $i++;
                continue;
            }

            // Character name: ALL CAPS line followed by dialogue
            // Standard format: character names are indented ~20 chars and in ALL CAPS
            if (preg_match('/^([A-Z][A-Z\s\.]+)$/', $trimmed) && !preg_match('/^(INT|EXT|FADE|CUT|SMASH|THE END)/i', $trimmed)) {
                $charName = trim($trimmed);

                // Check for parenthetical on next line
                $parenthetical = null;
                $dialogue = '';
                $j = $i + 1;

                // Skip empty lines
                while ($j < count($lines) && trim($lines[$j]) === '') $j++;

                if ($j < count($lines)) {
                    $nextLine = trim($lines[$j]);
                    if (preg_match('/^\(.*\)$/', $nextLine)) {
                        $parenthetical = $nextLine;
                        $j++;
                        while ($j < count($lines) && trim($lines[$j]) === '') $j++;
                    }

                    // Collect dialogue lines (until empty line or next character/heading)
                    while ($j < count($lines)) {
                        $dLine = trim($lines[$j]);
                        if (empty($dLine)) break;
                        if (preg_match('/^(INT|EXT)\.?\s+/i', $dLine)) break;
                        if (preg_match('/^[A-Z][A-Z\s\.]+$/', $dLine) && strlen($dLine) < 40) break;
                        $dialogue .= ($dialogue ? ' ' : '') . $dLine;
                        $j++;
                    }
                }

                if (!empty($dialogue)) {
                    if (!in_array($charName, $allCharacters)) {
                        $allCharacters[] = $charName;
                    }

                    if ($currentScene === null) {
                        $currentScene = [
                            'number' => ++$sceneNumber,
                            'slug' => 'SCENE ' . $sceneNumber,
                            'location_type' => 'interior',
                            'location_name' => '',
                            'blocks' => [],
                        ];
                    }

                    $currentScene['blocks'][] = [
                        'type' => 'dialogue',
                        'character' => $charName,
                        'parenthetical' => $parenthetical,
                        'text' => $dialogue,
                    ];
                    $i = $j;
                    continue;
                }
            }

            // Action/description line
            if ($currentScene !== null) {
                $currentScene['blocks'][] = ['type' => 'action', 'text' => $trimmed];
            } else {
                // Before first scene — might be title page content, skip
            }

            $i++;
        }

        // Save last scene
        if ($currentScene !== null) {
            $scenes[] = $currentScene;
        }

        return [
            'title' => $title,
            'scenes' => $scenes,
            'characters' => $allCharacters,
        ];
    }

    // ──────────────────────────────────────────────────
    // 1C. Scene Splitting
    // ──────────────────────────────────────────────────

    /**
     * Split a long scene into 5-10 second sub-segments for Seedance video clips.
     */
    public function splitSceneIntoSegments(array $scene): array
    {
        $segments = [];
        $currentDialogueLines = [];
        $currentActionContext = '';
        $currentWordCount = 0;
        $slug = $scene['slug'] ?? '';
        $isFirstSegment = true;

        foreach ($scene['blocks'] as $idx => $block) {
            $type = $block['type'] ?? 'action';

            if ($type === 'transition') {
                // Flush any accumulated dialogue before transition
                if (!empty($currentDialogueLines)) {
                    $segments[] = $this->buildDialogueSegment(
                        $currentDialogueLines,
                        $this->buildDirection($slug, $currentActionContext, $isFirstSegment),
                        $currentWordCount
                    );
                    $currentDialogueLines = [];
                    $currentActionContext = '';
                    $currentWordCount = 0;
                    $isFirstSegment = false;
                }
                continue;
            }

            if ($type === 'fight') {
                // Flush dialogue first
                if (!empty($currentDialogueLines)) {
                    $segments[] = $this->buildDialogueSegment(
                        $currentDialogueLines,
                        $this->buildDirection($slug, $currentActionContext, $isFirstSegment),
                        $currentWordCount
                    );
                    $currentDialogueLines = [];
                    $currentActionContext = '';
                    $currentWordCount = 0;
                    $isFirstSegment = false;
                }

                // Each fight block = its own 10s visual-only segment
                $fightText = $block['text'] ?? '';
                $direction = $this->summarizeAction($fightText, $slug);
                $segments[] = [
                    'text' => '',
                    'direction' => $direction,
                    'estimated_duration' => 10.0,
                    'is_visual_only' => true,
                ];
                continue;
            }

            if ($type === 'action') {
                $actionText = $block['text'] ?? '';

                // If we have accumulated dialogue, this action provides visual context
                if (!empty($currentDialogueLines)) {
                    // Context for the current dialogue segment
                    $currentActionContext .= ($currentActionContext ? ' ' : '') . $actionText;
                    continue;
                }

                // Pure action block without dialogue — check if it's substantial enough
                $sentences = preg_split('/(?<=[.!?])\s+/', $actionText, -1, PREG_SPLIT_NO_EMPTY);
                if (count($sentences) >= 2) {
                    // Substantial action block → visual-only segment
                    $direction = $this->summarizeAction($actionText, $slug);
                    $segments[] = [
                        'text' => '',
                        'direction' => $direction,
                        'estimated_duration' => 10.0,
                        'is_visual_only' => true,
                    ];
                    $isFirstSegment = false;
                } else {
                    // Short action — accumulate as context for next dialogue
                    $currentActionContext .= ($currentActionContext ? ' ' : '') . $actionText;
                }
                continue;
            }

            if ($type === 'dialogue') {
                $words = str_word_count($block['text'] ?? '');
                $line = strtoupper($block['character']) . ': ' . ($block['text'] ?? '');

                // Would adding this line exceed our target?
                if ($currentWordCount > 0 && ($currentWordCount + $words) > self::MAX_SEGMENT_WORDS) {
                    // Close current segment, start new one
                    $segments[] = $this->buildDialogueSegment(
                        $currentDialogueLines,
                        $this->buildDirection($slug, $currentActionContext, $isFirstSegment),
                        $currentWordCount
                    );
                    $currentDialogueLines = [];
                    $currentActionContext = '';
                    $currentWordCount = 0;
                    $isFirstSegment = false;
                }

                $currentDialogueLines[] = $line;
                $currentWordCount += $words;
            }
        }

        // Flush remaining
        if (!empty($currentDialogueLines)) {
            $segments[] = $this->buildDialogueSegment(
                $currentDialogueLines,
                $this->buildDirection($slug, $currentActionContext, $isFirstSegment),
                $currentWordCount
            );
        } elseif (!empty($currentActionContext) && empty($segments)) {
            // Scene is all action (no dialogue) — create a visual-only segment
            $segments[] = [
                'text' => '',
                'direction' => $this->summarizeAction($currentActionContext, $slug),
                'estimated_duration' => 10.0,
                'is_visual_only' => true,
            ];
        }

        return $segments;
    }

    // ──────────────────────────────────────────────────
    // 1D. Character Auto-Detection
    // ──────────────────────────────────────────────────

    /**
     * Extract characters from parsed scenes and assign roles/voices.
     */
    public function detectCharacters(array $parsed): array
    {
        $names = $parsed['characters'] ?? [];
        $characters = [];

        $roles = ['protagonist', 'deuteragonist', 'antagonist', 'supporting'];

        foreach ($names as $idx => $name) {
            $role = $roles[$idx] ?? 'supporting';
            $voiceId = self::VOICE_POOL[$idx % count(self::VOICE_POOL)];

            $characters[] = [
                'id' => strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name)),
                'name' => ucwords(strtolower($name)),
                'role' => $role,
                'gender' => 'unknown',
                'description' => $this->findCharacterDescription($name, $parsed['scenes']),
                'voice' => ['id' => $voiceId, 'provider' => 'openai'],
            ];
        }

        return $characters;
    }

    // ──────────────────────────────────────────────────
    // 1E. Synthetic Film Template
    // ──────────────────────────────────────────────────

    /**
     * Build a filmTemplateConfig matching FilmTemplateService::TEMPLATES structure.
     */
    public function buildSyntheticTemplate(array $parsed, string $aspectRatio = '16:9'): array
    {
        $characters = $this->detectCharacters($parsed);
        $atmosphere = $this->extractAtmosphere($parsed['scenes']);
        $totalSegments = 0;
        $totalDuration = 0;

        foreach ($parsed['scenes'] as $scene) {
            $segs = $this->splitSceneIntoSegments($scene);
            $totalSegments += count($segs);
            $totalDuration += array_sum(array_column($segs, 'estimated_duration'));
        }

        return [
            'name' => $parsed['title'] ?? 'Imported Screenplay',
            'slug' => 'imported_screenplay',
            'icon' => 'fa-light fa-file-import',
            'color' => '#f59e0b',
            'description' => 'Imported from external screenplay file',
            'visual_style' => 'cinematic',
            'visual_overrides' => [
                'imagePrefix' => 'Cinematic lighting, film grain, shallow depth of field',
                'imageSuffix' => 'Professional cinematography',
                'videoAnchor' => 'Cinematic film look, natural lighting',
                'videoLighting' => 'dramatic natural lighting with practical sources',
                'videoColor' => 'cinematic color grading with natural tones',
            ],
            'characters' => $characters,
            'camera_rules' => [
                'establishing' => ['slow zoom out', 'rise and reveal', 'pan right slow'],
                'dialogue' => ['slow zoom in', 'push to subject', 'breathe'],
                'action' => ['zoom in pan right', 'dramatic zoom in', 'diagonal drift'],
                'tension' => ['slow zoom in', 'settle in'],
                'closing' => ['slow zoom out', 'rise and reveal'],
            ],
            'transitions' => ['default' => 'none', 'action' => 'none', 'dialogue' => 'none'],
            'scene_count_target' => $totalSegments,
            'duration_default' => (int) $totalDuration,
            'aspect_ratio' => $aspectRatio,
            'script_format' => 'screenplay',
            'no_narrator' => true,
            'atmosphere' => $atmosphere,
            'music_style' => 'cinematic orchestral',
        ];
    }

    // ──────────────────────────────────────────────────
    // 1F. Transcript Conversion
    // ──────────────────────────────────────────────────

    /**
     * Convert segments into [Scene: direction]\nCHARACTER: dialogue transcript format.
     */
    public function toTranscript(array $segments): string
    {
        $lines = [];

        foreach ($segments as $segment) {
            $direction = $segment['direction'] ?? '';
            $text = $segment['text'] ?? '';

            $lines[] = '[Scene: ' . $direction . ']';

            if (!empty($text)) {
                // Text already contains "CHARACTER: dialogue" lines
                $lines[] = $text;
            }

            $lines[] = ''; // blank line between segments
        }

        return trim(implode("\n", $lines));
    }

    // ──────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────

    /**
     * Ensure a string is valid UTF-8 (strip invalid bytes).
     */
    private function utf8(string $text): string
    {
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    /**
     * Check if an element has a specific CSS class.
     */
    private function hasClass(string $classList, string $className): bool
    {
        return in_array($className, preg_split('/\s+/', $classList));
    }

    /**
     * Extract all text from a DOM node and its children.
     */
    private function extractAllText($node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $this->utf8($child->textContent);
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $classes = $child->getAttribute('class') ?? '';
                // Skip fight-impact text (e.g. "MOTION — SUDDEN — TOTAL")
                if ($this->hasClass($classes, 'fight-impact')) {
                    continue;
                }
                $text .= ' ' . $this->extractAllText($child);
            }
        }
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Build a direction string for a dialogue segment.
     */
    private function buildDirection(string $slug, string $actionContext, bool $isFirst): string
    {
        if ($isFirst && !empty($slug)) {
            // First segment uses the scene's slug as direction
            return $this->slugToDirection($slug);
        }

        if (!empty($actionContext)) {
            return $this->summarizeAction($actionContext, $slug);
        }

        // Fallback: use location from slug
        return $this->slugToDirection($slug);
    }

    /**
     * Convert a scene slug (e.g. "INT. LENA'S ALL-NIGHT DINER — CONTINUOUS")
     * into a visual direction.
     */
    private function slugToDirection(string $slug): string
    {
        // Remove INT./EXT. prefix
        $dir = preg_replace('/^(INT|EXT)\.?\s*/i', '', $slug);
        // Remove time/continuity suffixes
        $dir = preg_replace('/\s*[—–-]\s*(CONTINUOUS|NIGHT|DAY|DAWN|DUSK|LATER|MOMENTS LATER|SAME TIME).*$/i', '', $dir);
        $dir = trim($dir);

        // Add location type context
        $isExterior = preg_match('/^EXT/i', $slug);
        $prefix = $isExterior ? 'Wide exterior shot,' : 'Interior setting,';

        return $prefix . ' ' . $dir;
    }

    /**
     * Summarize action text into a concise visual direction (max ~15 words).
     */
    private function summarizeAction(string $actionText, string $slug = ''): string
    {
        // Take first sentence or first 20 words as direction
        $sentences = preg_split('/(?<=[.!?])\s+/', $actionText, 2, PREG_SPLIT_NO_EMPTY);
        $firstSentence = $sentences[0] ?? $actionText;

        $words = explode(' ', $firstSentence);
        if (count($words) > 20) {
            $firstSentence = implode(' ', array_slice($words, 0, 20));
        }

        // If too short, add location context
        if (str_word_count($firstSentence) < 5 && !empty($slug)) {
            $location = $this->slugToDirection($slug);
            return $location . '. ' . $firstSentence;
        }

        return $firstSentence;
    }

    /**
     * Build a dialogue segment array.
     */
    private function buildDialogueSegment(array $dialogueLines, string $direction, int $wordCount): array
    {
        // Duration: words / WPM * 60, snapped to 5 or 10
        $rawDuration = ($wordCount / self::WPM) * 60;
        $duration = $rawDuration <= 7 ? 5.0 : 10.0;

        return [
            'text' => implode("\n", $dialogueLines),
            'direction' => $direction,
            'estimated_duration' => $duration,
            'is_visual_only' => false,
        ];
    }

    /**
     * Find first description of a character from action blocks.
     */
    private function findCharacterDescription(string $name, array $scenes): string
    {
        foreach ($scenes as $scene) {
            foreach ($scene['blocks'] as $block) {
                if ($block['type'] === 'action') {
                    $text = $block['text'] ?? '';
                    // Look for the character name in action text (often introduces appearance)
                    if (stripos($text, $name) !== false) {
                        // Take up to 2 sentences containing the name
                        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
                        $relevant = [];
                        foreach ($sentences as $sentence) {
                            if (stripos($sentence, $name) !== false) {
                                $relevant[] = $sentence;
                                if (count($relevant) >= 2) break;
                            }
                        }
                        if (!empty($relevant)) {
                            return implode(' ', $relevant);
                        }
                    }
                }
            }
        }

        return ucwords(strtolower($name)) . ', a character in the story';
    }

    /**
     * Extract atmospheric description from the first scene's action blocks.
     */
    private function extractAtmosphere(array $scenes): string
    {
        if (empty($scenes)) {
            return 'Cinematic atmosphere';
        }

        $firstScene = $scenes[0];
        $actionTexts = [];
        foreach ($firstScene['blocks'] as $block) {
            if ($block['type'] === 'action' && !empty($block['text'])) {
                $actionTexts[] = $block['text'];
                if (count($actionTexts) >= 2) break;
            }
        }

        if (empty($actionTexts)) {
            return 'Cinematic atmosphere';
        }

        // Take first sentence from each action block
        $atmosphere = '';
        foreach ($actionTexts as $text) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $text, 2);
            $atmosphere .= ($atmosphere ? ' ' : '') . ($sentences[0] ?? $text);
        }

        // Cap at ~30 words
        $words = explode(' ', $atmosphere);
        if (count($words) > 30) {
            $atmosphere = implode(' ', array_slice($words, 0, 30));
        }

        return $atmosphere;
    }
}
