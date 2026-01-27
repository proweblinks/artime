<?php

namespace Modules\AppVideoWizard\Services;

/**
 * ComplexityDetectorService
 *
 * Multi-dimensional complexity scoring for shot data to determine when
 * shots exceed template capability and require LLM expansion.
 *
 * Complexity Dimensions:
 * 1. multi_character - 2+ characters requires spatial dynamics vocabulary
 * 2. emotional_complexity - High subtext or layered emotions
 * 3. environment_novelty - Environment not covered by template keywords
 * 4. combination_novelty - Novel combination of shot elements
 * 5. token_budget_risk - Would exceed 80% of target budget with template approach
 *
 * Thresholds:
 * - Any single dimension >= 0.7 triggers complexity
 * - Total weighted score >= 0.6 triggers complexity
 * - 3+ characters ALWAYS triggers complexity
 */
class ComplexityDetectorService
{
    /**
     * Dimension weights for total score calculation.
     */
    protected const DIMENSION_WEIGHTS = [
        'multi_character' => 0.30,
        'emotional_complexity' => 0.25,
        'environment_novelty' => 0.20,
        'combination_novelty' => 0.15,
        'token_budget_risk' => 0.10,
    ];

    /**
     * Threshold for any single dimension to trigger complexity.
     */
    protected const SINGLE_DIMENSION_THRESHOLD = 0.7;

    /**
     * Threshold for total weighted score to trigger complexity.
     */
    protected const TOTAL_SCORE_THRESHOLD = 0.6;

    /**
     * Character count that ALWAYS triggers complexity.
     */
    protected const ALWAYS_COMPLEX_CHARACTER_COUNT = 3;

    /**
     * Known environments from template emphasis keywords.
     * Environments matching these keywords have template coverage.
     */
    protected const KNOWN_ENVIRONMENTS = [
        // Indoor environments
        'room', 'office', 'studio', 'apartment', 'house', 'home', 'bedroom',
        'living room', 'kitchen', 'bathroom', 'hallway', 'corridor', 'lobby',
        'restaurant', 'cafe', 'bar', 'hotel', 'hospital', 'clinic', 'school',
        'classroom', 'library', 'gym', 'garage', 'basement', 'attic',
        'warehouse', 'factory', 'store', 'shop', 'mall', 'church', 'temple',
        'museum', 'theater', 'cinema', 'courtroom', 'prison', 'cell',
        // Outdoor environments
        'street', 'road', 'sidewalk', 'park', 'garden', 'forest', 'woods',
        'beach', 'ocean', 'sea', 'lake', 'river', 'mountain', 'hill',
        'desert', 'field', 'meadow', 'farm', 'city', 'urban', 'suburban',
        'downtown', 'alley', 'parking lot', 'rooftop', 'balcony', 'patio',
        // Vehicle environments
        'car', 'vehicle', 'bus', 'train', 'airplane', 'plane', 'boat', 'ship',
        // Generic descriptors
        'interior', 'exterior', 'indoor', 'outdoor', 'dark', 'bright', 'dim',
    ];

    /**
     * Common shot type + emotion combinations that templates handle well.
     */
    protected const COMMON_COMBINATIONS = [
        'close-up:grief',
        'close-up:fear',
        'close-up:anxiety',
        'close-up:anger',
        'close-up:joy',
        'close-up:sadness',
        'medium:tension',
        'medium:neutral',
        'medium:contemplation',
        'wide:isolation',
        'wide:peace',
        'wide:danger',
        'establishing:atmosphere',
        'establishing:tension',
        'two-shot:tension',
        'two-shot:intimacy',
        'two-shot:conflict',
        'over-the-shoulder:conversation',
        'over-the-shoulder:tension',
    ];

    /**
     * Target token budget for standard prompts.
     */
    protected const TARGET_TOKEN_BUDGET = 77; // CLIP limit

    /**
     * Average words per template component.
     */
    protected const TEMPLATE_WORD_ESTIMATES = [
        'subject' => 15,
        'action' => 8,
        'environment' => 10,
        'lighting' => 8,
        'style' => 6,
        'emotion' => 5,
        'character' => 10, // per character
    ];

    protected PromptTemplateLibrary $templateLibrary;

    public function __construct(?PromptTemplateLibrary $templateLibrary = null)
    {
        $this->templateLibrary = $templateLibrary ?? new PromptTemplateLibrary();
    }

    /**
     * Calculate comprehensive complexity analysis for shot data.
     *
     * @param array $shotData Shot data with characters, shot_type, emotion, etc.
     * @return array{scores: array, total_score: float, is_complex: bool, complexity_reasons: array}
     */
    public function calculateComplexity(array $shotData): array
    {
        $scores = [
            'multi_character' => $this->scoreMultiCharacter($shotData),
            'emotional_complexity' => $this->scoreEmotionalComplexity($shotData),
            'environment_novelty' => $this->scoreEnvironmentNovelty($shotData),
            'combination_novelty' => $this->scoreCombinationNovelty($shotData),
            'token_budget_risk' => $this->scoreTokenBudgetRisk($shotData),
        ];

        $totalScore = $this->calculateTotalScore($scores);
        $isComplex = $this->determineIsComplex($scores, $totalScore, $shotData);
        $complexityReasons = $this->getComplexityReasons($scores);

        return [
            'scores' => $scores,
            'total_score' => round($totalScore, 3),
            'is_complex' => $isComplex,
            'complexity_reasons' => $complexityReasons,
        ];
    }

    /**
     * Convenience method to check if shot is complex.
     *
     * @param array $shotData Shot data
     * @return bool True if shot requires LLM expansion
     */
    public function isComplex(array $shotData): bool
    {
        return $this->calculateComplexity($shotData)['is_complex'];
    }

    /**
     * Get human-readable reasons for complexity.
     *
     * @param array $scores Dimension scores
     * @return array List of human-readable complexity reasons
     */
    public function getComplexityReasons(array $scores): array
    {
        $reasons = [];

        if ($scores['multi_character'] >= self::SINGLE_DIMENSION_THRESHOLD) {
            if ($scores['multi_character'] >= 1.0) {
                $reasons[] = 'Three or more characters require complex spatial dynamics';
            } else {
                $reasons[] = 'Two characters require spatial relationship vocabulary';
            }
        }

        if ($scores['emotional_complexity'] >= self::SINGLE_DIMENSION_THRESHOLD) {
            $reasons[] = 'High emotional complexity with subtext or layered emotions';
        } elseif ($scores['emotional_complexity'] >= 0.5) {
            $reasons[] = 'Moderate emotional complexity present';
        }

        if ($scores['environment_novelty'] >= self::SINGLE_DIMENSION_THRESHOLD) {
            $reasons[] = 'Novel environment not covered by standard templates';
        } elseif ($scores['environment_novelty'] >= 0.5) {
            $reasons[] = 'Environment may benefit from expanded description';
        }

        if ($scores['combination_novelty'] >= self::SINGLE_DIMENSION_THRESHOLD) {
            $reasons[] = 'Unusual combination of shot elements requires creative interpretation';
        }

        if ($scores['token_budget_risk'] >= self::SINGLE_DIMENSION_THRESHOLD) {
            $reasons[] = 'Token budget risk - template approach may exceed limits';
        }

        return $reasons;
    }

    /**
     * Score multi-character dimension.
     *
     * @param array $shot Shot data
     * @return float Score 0.0-1.0
     */
    protected function scoreMultiCharacter(array $shot): float
    {
        $characters = $shot['characters'] ?? [];
        $characterCount = is_array($characters) ? count($characters) : 0;

        // Also check for character_count if provided directly
        if ($characterCount === 0 && isset($shot['character_count'])) {
            $characterCount = (int) $shot['character_count'];
        }

        if ($characterCount <= 1) {
            return 0.0;
        }

        if ($characterCount === 2) {
            return 0.7;
        }

        // 3+ characters always max score
        return 1.0;
    }

    /**
     * Score emotional complexity dimension.
     *
     * @param array $shot Shot data
     * @return float Score 0.0-1.0
     */
    protected function scoreEmotionalComplexity(array $shot): float
    {
        $score = 0.0;

        // +0.5 if subtext field is non-empty
        $subtext = $shot['subtext'] ?? '';
        if (is_string($subtext) && trim($subtext) !== '') {
            $score += 0.5;
        }

        // +0.3 if multiple emotions specified
        $emotions = $shot['emotions'] ?? [];
        if (is_array($emotions) && count($emotions) >= 2) {
            $score += 0.3;
        }

        // +0.2 if tension_level >= 8
        $tensionLevel = $shot['tension_level'] ?? 0;
        if ((int) $tensionLevel >= 8) {
            $score += 0.2;
        }

        return min(1.0, $score);
    }

    /**
     * Score environment novelty dimension.
     *
     * @param array $shot Shot data
     * @return float Score 0.0-1.0 (lower = more common, higher = more novel)
     */
    protected function scoreEnvironmentNovelty(array $shot): float
    {
        $environment = strtolower(trim($shot['environment'] ?? ''));

        if ($environment === '') {
            // No environment specified - not a complexity factor
            return 0.0;
        }

        // Check for direct matches or partial matches with known environments
        $matchScore = 0.0;

        foreach (self::KNOWN_ENVIRONMENTS as $knownEnv) {
            if ($environment === $knownEnv) {
                // Exact match - very well covered
                return 0.0;
            }

            if (str_contains($environment, $knownEnv) || str_contains($knownEnv, $environment)) {
                // Partial match - somewhat covered
                $matchScore = max($matchScore, 0.3);
            }
        }

        // Also check shot type template emphasis keywords
        $shotType = $shot['shot_type'] ?? 'medium';
        $emphasis = $this->templateLibrary->getEmphasis($shotType);

        foreach ($emphasis as $emphasisKeyword) {
            $emphasisKeyword = strtolower($emphasisKeyword);
            if (str_contains($environment, $emphasisKeyword)) {
                $matchScore = max($matchScore, 0.2);
            }
        }

        if ($matchScore > 0) {
            // Some template coverage exists
            return 1.0 - $matchScore;
        }

        // No matches found - novel environment
        return 0.9;
    }

    /**
     * Score combination novelty dimension.
     *
     * @param array $shot Shot data
     * @return float Score 0.0-1.0
     */
    protected function scoreCombinationNovelty(array $shot): float
    {
        $shotType = strtolower($shot['shot_type'] ?? 'medium');
        $emotion = strtolower($shot['emotion'] ?? '');

        if ($emotion === '') {
            // No emotion specified - can't assess combination
            return 0.0;
        }

        // Create combination key
        $combination = "{$shotType}:{$emotion}";

        // Check if combination is common
        if (in_array($combination, self::COMMON_COMBINATIONS, true)) {
            return 0.0; // Common combination - templates handle it well
        }

        // Check for partial matches (e.g., emotion matches but shot type differs)
        $emotionMatches = 0;
        $shotTypeMatches = 0;

        foreach (self::COMMON_COMBINATIONS as $commonCombo) {
            [$commonShot, $commonEmotion] = explode(':', $commonCombo);
            if ($commonShot === $shotType) {
                $shotTypeMatches++;
            }
            if ($commonEmotion === $emotion) {
                $emotionMatches++;
            }
        }

        // If shot type is common but emotion isn't used with it
        if ($shotTypeMatches > 0 && $emotionMatches === 0) {
            return 0.6; // Novel emotion for this shot type
        }

        // If emotion is common but shot type isn't used with it
        if ($emotionMatches > 0 && $shotTypeMatches === 0) {
            return 0.5; // Novel shot type for this emotion
        }

        // Neither common - quite novel
        if ($shotTypeMatches === 0 && $emotionMatches === 0) {
            return 0.7;
        }

        // Both present but not combined - somewhat novel
        return 0.4;
    }

    /**
     * Score token budget risk dimension.
     *
     * @param array $shot Shot data
     * @return float Score 0.0-1.0
     */
    protected function scoreTokenBudgetRisk(array $shot): float
    {
        $estimatedWords = 0;

        // Base subject description
        $estimatedWords += self::TEMPLATE_WORD_ESTIMATES['subject'];

        // Per character
        $characters = $shot['characters'] ?? [];
        $characterCount = is_array($characters) ? count($characters) : 1;
        $estimatedWords += $characterCount * self::TEMPLATE_WORD_ESTIMATES['character'];

        // Action
        if (!empty($shot['action']) || !empty($shot['movement'])) {
            $estimatedWords += self::TEMPLATE_WORD_ESTIMATES['action'];
        }

        // Environment
        if (!empty($shot['environment'])) {
            $estimatedWords += self::TEMPLATE_WORD_ESTIMATES['environment'];
        }

        // Lighting
        $estimatedWords += self::TEMPLATE_WORD_ESTIMATES['lighting'];

        // Style
        $estimatedWords += self::TEMPLATE_WORD_ESTIMATES['style'];

        // Emotion/psychology
        if (!empty($shot['emotion']) || !empty($shot['emotions'])) {
            $estimatedWords += self::TEMPLATE_WORD_ESTIMATES['emotion'];
        }

        // Subtext adds complexity
        if (!empty($shot['subtext'])) {
            $estimatedWords += 10;
        }

        // Multiple emotions add complexity
        if (is_array($shot['emotions'] ?? null) && count($shot['emotions']) > 1) {
            $estimatedWords += 8 * (count($shot['emotions']) - 1);
        }

        // Estimate tokens (rough: 1.3 tokens per word for English)
        $estimatedTokens = $estimatedWords * 1.3;

        // Calculate proximity to budget limit
        $budgetUsage = $estimatedTokens / self::TARGET_TOKEN_BUDGET;

        // Score based on how close to 80% threshold
        if ($budgetUsage < 0.6) {
            return 0.0; // Well within budget
        }

        if ($budgetUsage < 0.8) {
            // Linear scaling from 0.6-0.8 usage to 0.0-0.5 score
            return ($budgetUsage - 0.6) / 0.2 * 0.5;
        }

        if ($budgetUsage < 1.0) {
            // Linear scaling from 0.8-1.0 usage to 0.5-0.8 score
            return 0.5 + (($budgetUsage - 0.8) / 0.2 * 0.3);
        }

        // Over budget - high risk
        return min(1.0, 0.8 + ($budgetUsage - 1.0) * 0.2);
    }

    /**
     * Calculate weighted total score from dimension scores.
     *
     * @param array $scores Dimension scores
     * @return float Weighted total score
     */
    protected function calculateTotalScore(array $scores): float
    {
        $total = 0.0;

        foreach (self::DIMENSION_WEIGHTS as $dimension => $weight) {
            $total += ($scores[$dimension] ?? 0.0) * $weight;
        }

        return $total;
    }

    /**
     * Determine if shot is complex based on scores and thresholds.
     *
     * @param array $scores Dimension scores
     * @param float $totalScore Weighted total score
     * @param array $shotData Original shot data
     * @return bool True if shot is complex
     */
    protected function determineIsComplex(array $scores, float $totalScore, array $shotData): bool
    {
        // Rule: 3+ characters ALWAYS triggers complexity
        $characters = $shotData['characters'] ?? [];
        $characterCount = is_array($characters) ? count($characters) : 0;
        if ($characterCount === 0 && isset($shotData['character_count'])) {
            $characterCount = (int) $shotData['character_count'];
        }

        if ($characterCount >= self::ALWAYS_COMPLEX_CHARACTER_COUNT) {
            return true;
        }

        // Rule: Any single dimension >= 0.7 triggers complexity
        foreach ($scores as $score) {
            if ($score >= self::SINGLE_DIMENSION_THRESHOLD) {
                return true;
            }
        }

        // Rule: Total weighted score >= 0.6 triggers complexity
        if ($totalScore >= self::TOTAL_SCORE_THRESHOLD) {
            return true;
        }

        return false;
    }
}
