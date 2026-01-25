<?php

namespace Modules\AppVideoWizard\Services;

/**
 * PromptTemplateLibrary
 *
 * Shot-type organized templates with word budgets for Hollywood-quality prompt generation.
 * Each shot type has specific emphasis areas, default lens choices, and priority ordering.
 *
 * Word budgets sum to 100% and are used by prompt builders to allocate token/word counts
 * appropriately for each shot type's visual requirements.
 */
class PromptTemplateLibrary
{
    /**
     * Shot templates organized by shot type.
     *
     * Each template contains:
     * - emphasis: What visual elements to prioritize
     * - default_lens: Recommended focal length for this shot type
     * - word_budget: Percentage allocation for prompt sections (must sum to 100)
     * - priority_order: Compression priority when tokens are limited
     */
    public const SHOT_TEMPLATES = [
        'close-up' => [
            'emphasis' => ['facial_detail', 'emotion', 'micro_expressions', 'eye_contact'],
            'default_lens' => '85mm',
            'word_budget' => [
                'subject' => 35,
                'action' => 20,
                'environment' => 10,
                'lighting' => 18,
                'style' => 17,
            ],
            'priority_order' => ['subject', 'action', 'lighting', 'environment', 'style'],
        ],
        'medium' => [
            'emphasis' => ['body_language', 'gesture', 'subject_context', 'interaction'],
            'default_lens' => '50mm',
            'word_budget' => [
                'subject' => 28,
                'action' => 20,
                'environment' => 20,
                'lighting' => 17,
                'style' => 15,
            ],
            'priority_order' => ['subject', 'action', 'environment', 'lighting', 'style'],
        ],
        'wide' => [
            'emphasis' => ['environment', 'spatial_context', 'scene_setting', 'atmosphere'],
            'default_lens' => '24mm',
            'word_budget' => [
                'subject' => 20,
                'action' => 15,
                'environment' => 35,
                'lighting' => 15,
                'style' => 15,
            ],
            'priority_order' => ['environment', 'subject', 'action', 'lighting', 'style'],
        ],
        'establishing' => [
            'emphasis' => ['location', 'time_of_day', 'atmosphere', 'scale'],
            'default_lens' => '24mm',
            'word_budget' => [
                'subject' => 10,
                'action' => 10,
                'environment' => 45,
                'lighting' => 20,
                'style' => 15,
            ],
            'priority_order' => ['environment', 'lighting', 'subject', 'action', 'style'],
        ],
        'extreme-close-up' => [
            'emphasis' => ['detail', 'texture', 'micro_expressions', 'emotional_intensity'],
            'default_lens' => '135mm',
            'word_budget' => [
                'subject' => 45,
                'action' => 15,
                'environment' => 5,
                'lighting' => 20,
                'style' => 15,
            ],
            'priority_order' => ['subject', 'lighting', 'action', 'style', 'environment'],
        ],
        'medium-close' => [
            'emphasis' => ['facial_expression', 'upper_body', 'intimate_gesture', 'personal_space'],
            'default_lens' => '85mm',
            'word_budget' => [
                'subject' => 32,
                'action' => 20,
                'environment' => 15,
                'lighting' => 18,
                'style' => 15,
            ],
            'priority_order' => ['subject', 'action', 'lighting', 'environment', 'style'],
        ],
        'medium-wide' => [
            'emphasis' => ['full_body', 'movement', 'immediate_environment', 'physical_action'],
            'default_lens' => '35mm',
            'word_budget' => [
                'subject' => 25,
                'action' => 22,
                'environment' => 25,
                'lighting' => 15,
                'style' => 13,
            ],
            'priority_order' => ['subject', 'environment', 'action', 'lighting', 'style'],
        ],
        'over-the-shoulder' => [
            'emphasis' => ['foreground_subject', 'background_subject', 'depth', 'relationship'],
            'default_lens' => '50mm',
            'word_budget' => [
                'subject' => 40,
                'action' => 18,
                'environment' => 15,
                'lighting' => 15,
                'style' => 12,
            ],
            'priority_order' => ['subject', 'action', 'environment', 'lighting', 'style'],
        ],
        'two-shot' => [
            'emphasis' => ['interaction', 'relationship', 'body_language', 'shared_space'],
            'default_lens' => '50mm',
            'word_budget' => [
                'subject' => 38,
                'action' => 22,
                'environment' => 18,
                'lighting' => 12,
                'style' => 10,
            ],
            'priority_order' => ['subject', 'action', 'environment', 'lighting', 'style'],
        ],
        'detail' => [
            'emphasis' => ['object', 'texture', 'significance', 'isolation'],
            'default_lens' => '135mm',
            'word_budget' => [
                'subject' => 50,
                'action' => 10,
                'environment' => 10,
                'lighting' => 18,
                'style' => 12,
            ],
            'priority_order' => ['subject', 'lighting', 'style', 'action', 'environment'],
        ],
    ];

    /**
     * CinematographyVocabulary instance for lens lookups.
     */
    protected CinematographyVocabulary $vocabulary;

    public function __construct(?CinematographyVocabulary $vocabulary = null)
    {
        $this->vocabulary = $vocabulary ?? new CinematographyVocabulary();
    }

    /**
     * Get the full template for a shot type.
     *
     * @param string $shotType The shot type to retrieve
     * @return array The template with emphasis, lens, word_budget, priority_order
     */
    public function getTemplateForShotType(string $shotType): array
    {
        $shotType = $this->normalizeShortType($shotType);

        $template = self::SHOT_TEMPLATES[$shotType] ?? self::SHOT_TEMPLATES['medium'];

        // Enrich with vocabulary data
        $lensData = $this->vocabulary->getLensForShotType($shotType);
        $template['lens_psychology'] = $lensData;

        return $template;
    }

    /**
     * Get word budget percentages for a shot type.
     *
     * @param string $shotType The shot type
     * @return array<string, int> Word budget percentages (sum to 100)
     */
    public function getWordBudget(string $shotType): array
    {
        $shotType = $this->normalizeShortType($shotType);

        return self::SHOT_TEMPLATES[$shotType]['word_budget']
            ?? self::SHOT_TEMPLATES['medium']['word_budget'];
    }

    /**
     * Get compression priority order for a shot type.
     *
     * When tokens are limited, compress sections in reverse priority order.
     *
     * @param string $shotType The shot type
     * @return array<string> Ordered list of sections by importance
     */
    public function getPriorityOrder(string $shotType): array
    {
        $shotType = $this->normalizeShortType($shotType);

        return self::SHOT_TEMPLATES[$shotType]['priority_order']
            ?? self::SHOT_TEMPLATES['medium']['priority_order'];
    }

    /**
     * Get the emphasis areas for a shot type.
     *
     * @param string $shotType The shot type
     * @return array<string> List of emphasis areas
     */
    public function getEmphasis(string $shotType): array
    {
        $shotType = $this->normalizeShortType($shotType);

        return self::SHOT_TEMPLATES[$shotType]['emphasis']
            ?? self::SHOT_TEMPLATES['medium']['emphasis'];
    }

    /**
     * Get the default lens for a shot type.
     *
     * @param string $shotType The shot type
     * @return string The default lens (e.g., '85mm')
     */
    public function getDefaultLens(string $shotType): string
    {
        $shotType = $this->normalizeShortType($shotType);

        return self::SHOT_TEMPLATES[$shotType]['default_lens']
            ?? self::SHOT_TEMPLATES['medium']['default_lens'];
    }

    /**
     * Infer shot type from scene context.
     *
     * Analyzes scene description to determine appropriate shot type.
     *
     * @param array $context Scene context with description, subject_count, focus, etc.
     * @return string Inferred shot type
     */
    public function getShotTypeFromContext(array $context): string
    {
        $description = strtolower($context['description'] ?? '');
        $subjectCount = $context['subject_count'] ?? 1;
        $focus = strtolower($context['focus'] ?? '');

        // Direct shot type mentions
        $shotTypeKeywords = [
            'extreme close' => 'extreme-close-up',
            'extreme closeup' => 'extreme-close-up',
            'ecu' => 'extreme-close-up',
            'close up' => 'close-up',
            'closeup' => 'close-up',
            'close-up' => 'close-up',
            'cu' => 'close-up',
            'medium close' => 'medium-close',
            'medium-close' => 'medium-close',
            'mcu' => 'medium-close',
            'medium wide' => 'medium-wide',
            'medium-wide' => 'medium-wide',
            'medium shot' => 'medium',
            'mid shot' => 'medium',
            'ms' => 'medium',
            'wide shot' => 'wide',
            'wide angle' => 'wide',
            'ws' => 'wide',
            'establishing shot' => 'establishing',
            'establishing' => 'establishing',
            'over the shoulder' => 'over-the-shoulder',
            'ots' => 'over-the-shoulder',
            'two shot' => 'two-shot',
            'two-shot' => 'two-shot',
            'detail shot' => 'detail',
            'insert' => 'detail',
        ];

        foreach ($shotTypeKeywords as $keyword => $shotType) {
            if (str_contains($description, $keyword)) {
                return $shotType;
            }
        }

        // Infer from focus area
        $focusMapping = [
            'eyes' => 'extreme-close-up',
            'eye' => 'extreme-close-up',
            'face' => 'close-up',
            'facial' => 'close-up',
            'expression' => 'close-up',
            'portrait' => 'close-up',
            'hands' => 'detail',
            'hand' => 'detail',
            'object' => 'detail',
            'body' => 'medium',
            'gesture' => 'medium',
            'interaction' => 'medium',
            'room' => 'wide',
            'location' => 'wide',
            'environment' => 'wide',
            'landscape' => 'establishing',
            'city' => 'establishing',
            'building' => 'establishing',
            'skyline' => 'establishing',
        ];

        foreach ($focusMapping as $focusKeyword => $shotType) {
            if (str_contains($focus, $focusKeyword) || str_contains($description, $focusKeyword)) {
                return $shotType;
            }
        }

        // Infer from subject count
        if ($subjectCount >= 2) {
            return 'two-shot';
        }

        // Default to medium shot (most versatile)
        return 'medium';
    }

    /**
     * Get all available shot types.
     *
     * @return array<string>
     */
    public function getAvailableShotTypes(): array
    {
        return array_keys(self::SHOT_TEMPLATES);
    }

    /**
     * Validate that a word budget sums to 100.
     *
     * @param array<string, int> $budget The word budget to validate
     * @return bool True if valid
     */
    public function validateWordBudget(array $budget): bool
    {
        return array_sum($budget) === 100;
    }

    /**
     * Calculate word counts from budget percentages.
     *
     * @param string $shotType The shot type
     * @param int $totalWords Total words available
     * @return array<string, int> Word counts per section
     */
    public function calculateWordCounts(string $shotType, int $totalWords): array
    {
        $budget = $this->getWordBudget($shotType);
        $counts = [];
        $allocated = 0;

        // Calculate proportional counts
        foreach ($budget as $section => $percentage) {
            $counts[$section] = (int) floor($totalWords * $percentage / 100);
            $allocated += $counts[$section];
        }

        // Distribute remaining words to highest priority section
        $remaining = $totalWords - $allocated;
        if ($remaining > 0) {
            $priority = $this->getPriorityOrder($shotType);
            $counts[$priority[0]] += $remaining;
        }

        return $counts;
    }

    /**
     * Normalize shot type string.
     *
     * @param string $shotType Raw shot type input
     * @return string Normalized shot type
     */
    protected function normalizeShortType(string $shotType): string
    {
        $shotType = strtolower(trim($shotType));

        // Handle common variations
        $normalizations = [
            'closeup' => 'close-up',
            'close up' => 'close-up',
            'extremecloseup' => 'extreme-close-up',
            'extreme close up' => 'extreme-close-up',
            'extreme closeup' => 'extreme-close-up',
            'mediumclose' => 'medium-close',
            'medium close' => 'medium-close',
            'mediumwide' => 'medium-wide',
            'medium wide' => 'medium-wide',
            'overtheshoulder' => 'over-the-shoulder',
            'over the shoulder' => 'over-the-shoulder',
            'ots' => 'over-the-shoulder',
            'twoshot' => 'two-shot',
            'two shot' => 'two-shot',
        ];

        return $normalizations[$shotType] ?? $shotType;
    }
}
