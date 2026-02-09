<?php

namespace Modules\AppAITools\Services;

class ThumbnailPromptBuilder
{
    /**
     * Build a multi-layer prompt for thumbnail generation.
     *
     * Layers:
     * 1. Base formula by category
     * 2. Reference type instructions (Phase 2 fills fully)
     * 3. Composition template (Phase 2 stub)
     * 4. Style modifier
     * 5. Expression modifier (Phase 2 stub)
     * 6. Background style (Phase 2 stub)
     * 7. YouTube context
     * 8. User custom prompt + universal rules
     */
    public function build(array $params): string
    {
        $layers = [];

        // Layer 1: Base formula by category
        $layers[] = $this->buildCategoryLayer($params['category'] ?? 'general');

        // Layer 2: Reference type instructions
        $layers[] = $this->buildReferenceTypeLayer($params);

        // Layer 3: Composition template (stub for Phase 2)
        $layers[] = $this->buildCompositionLayer($params);

        // Layer 4: Style modifier
        $layers[] = $this->buildStyleLayer($params['style'] ?? 'professional');

        // Layer 5: Expression modifier (stub for Phase 2)
        $layers[] = $this->buildExpressionLayer($params);

        // Layer 6: Background style (stub for Phase 2)
        $layers[] = $this->buildBackgroundLayer($params);

        // Layer 7: YouTube context
        $layers[] = $this->buildYouTubeLayer($params);

        // Layer 8: User custom prompt + universal rules
        $layers[] = $this->buildFinalLayer($params);

        return implode("\n\n", array_filter($layers));
    }

    protected function buildCategoryLayer(string $category): string
    {
        $formulas = [
            'general' => 'Create a YouTube thumbnail that uses a curiosity gap technique. '
                . 'Show a compelling visual that makes viewers desperate to know the answer or outcome. '
                . 'Use high contrast and a clear focal point.',

            'gaming' => 'Create a gaming YouTube thumbnail with an action-packed, high-energy visual. '
                . 'Include dynamic elements like explosions, dramatic character poses, or intense game moments. '
                . 'Use bold neon or saturated colors typical of gaming content.',

            'tutorial' => 'Create a tutorial/how-to YouTube thumbnail that clearly shows the end result or transformation. '
                . 'Use a clean, organized layout with before/after feel. '
                . 'Make the value proposition visually obvious - viewers should instantly know what they will learn.',

            'vlog' => 'Create a vlog-style YouTube thumbnail featuring a person with an expressive face as the main subject. '
                . 'Include contextual background elements that hint at the story or location. '
                . 'Make it feel authentic, personal, and relatable with warm, inviting tones.',

            'review' => 'Create a product review YouTube thumbnail prominently featuring the product or subject being reviewed. '
                . 'Show the product at an attractive angle with dramatic lighting. '
                . 'Convey the reviewer\'s verdict through visual cues (impressed expression, comparison elements).',

            'news' => 'Create a breaking news style YouTube thumbnail with urgency and importance. '
                . 'Use serious, impactful imagery with a news broadcast aesthetic. '
                . 'Convey the gravity or significance of the topic through visual composition.',

            'entertainment' => 'Create an entertainment YouTube thumbnail that is fun, vibrant, and irresistible to click. '
                . 'Use bright, eye-catching colors, humorous or surprising visual elements. '
                . 'Make it feel exciting and promise an entertaining experience.',
        ];

        return $formulas[$category] ?? $formulas['general'];
    }

    protected function buildReferenceTypeLayer(array $params): string
    {
        $mode = $params['mode'] ?? 'quick';

        if ($mode === 'upgrade') {
            return 'UPGRADE TRANSFORM: You are improving an existing YouTube thumbnail. '
                . 'Keep the core concept and subject matter but dramatically enhance: '
                . 'visual quality, color grading, composition, lighting, and overall professional polish. '
                . 'Make it look like a top creator\'s thumbnail while preserving the original idea.';
        }

        if ($mode === 'reference') {
            $refType = $params['referenceType'] ?? 'style';
            return $this->buildReferenceInstructions($refType, $params);
        }

        return '';
    }

    protected function buildReferenceInstructions(string $refType, array $params): string
    {
        $instructions = [
            'face' => 'FACE PRESERVE: Use the reference image to extract and preserve the person\'s facial identity exactly. '
                . 'Recreate the same person with the same facial features in a new thumbnail composition.',

            'product' => 'PRODUCT SHOWCASE: Use the reference image to identify the product/object. '
                . 'Feature this product prominently in the thumbnail with professional product photography aesthetics.',

            'style' => 'STYLE TRANSFER: Analyze the visual style of the reference image (colors, lighting, composition, mood). '
                . 'Apply this same visual style to generate a new thumbnail with the requested content.',

            'background' => 'BACKGROUND REFERENCE: Use the reference image as background or environment inspiration. '
                . 'Place the main subject in a similar setting or environment.',

            'auto' => 'REFERENCE ANALYSIS: Analyze the reference image and intelligently determine the best approach: '
                . 'preserve faces if present, maintain the visual style, and incorporate key elements into the new thumbnail.',
        ];

        return $instructions[$refType] ?? $instructions['auto'];
    }

    protected function buildCompositionLayer(array $params): string
    {
        // Phase 2: Will support face-right, face-center, split-screen, etc.
        $composition = $params['compositionTemplate'] ?? null;
        if (!$composition || $composition === 'auto') {
            return '';
        }

        $templates = [
            'face-right' => 'Place the main subject/face on the right third of the frame, leaving space on the left for visual context.',
            'face-center' => 'Center the main subject/face prominently with a shallow depth of field background.',
            'split-screen' => 'Split the thumbnail into two halves showing a comparison, before/after, or two contrasting elements.',
            'product-hero' => 'Feature the product centered and large with dramatic lighting and a clean, minimal background.',
            'action-shot' => 'Capture a dynamic, mid-action moment with motion blur or energy lines for movement.',
            'collage' => 'Arrange multiple visual elements in a clean collage layout with clear visual hierarchy.',
        ];

        return $templates[$composition] ?? '';
    }

    protected function buildStyleLayer(string $style): string
    {
        $modifiers = [
            'professional' => 'STYLE: Professional and polished. Clean composition, studio-quality lighting, '
                . 'muted but rich color palette, subtle shadows. Think corporate brand quality.',

            'dramatic' => 'STYLE: Dramatic and cinematic. High contrast, deep shadows with bright highlights, '
                . 'moody color grading (teal/orange or blue/gold), movie poster composition.',

            'minimal' => 'STYLE: Minimalist and clean. Simple composition with lots of negative space, '
                . 'limited color palette (2-3 colors max), elegant typography feel, modern and sleek.',

            'bold' => 'STYLE: Bold and attention-grabbing. Saturated colors, large visual elements, '
                . 'strong contrast, dynamic angles. Pop art and street art influences.',
        ];

        return $modifiers[$style] ?? $modifiers['professional'];
    }

    protected function buildExpressionLayer(array $params): string
    {
        // Phase 2: Will support excited, serious, surprised, curious, confident
        $expression = $params['expressionModifier'] ?? null;
        if (!$expression || $expression === 'keep') {
            return '';
        }

        $expressions = [
            'excited' => 'If a person is shown, depict them with an excited, wide-eyed, mouth-open expression.',
            'serious' => 'If a person is shown, depict them with a serious, determined, confident expression.',
            'surprised' => 'If a person is shown, depict them with a genuinely surprised, jaw-dropped expression.',
            'curious' => 'If a person is shown, depict them with a curious, intrigued, one-eyebrow-raised expression.',
            'confident' => 'If a person is shown, depict them with a confident, knowing smile or smirk.',
        ];

        return $expressions[$expression] ?? '';
    }

    protected function buildBackgroundLayer(array $params): string
    {
        // Phase 2: Will support studio, blur, gradient, contextual, dark, vibrant
        $bg = $params['backgroundStyle'] ?? null;
        if (!$bg || $bg === 'auto') {
            return '';
        }

        $backgrounds = [
            'studio' => 'Background: Clean studio backdrop with professional lighting, subtle gradient or solid color.',
            'blur' => 'Background: Heavily blurred background (bokeh effect) to isolate the main subject.',
            'gradient' => 'Background: Smooth color gradient that complements the main subject colors.',
            'contextual' => 'Background: Relevant context environment that tells the story of the content.',
            'dark' => 'Background: Dark, moody background (near-black) to make the subject pop dramatically.',
            'vibrant' => 'Background: Vibrant, colorful, energetic background with dynamic patterns or colors.',
        ];

        return $backgrounds[$bg] ?? '';
    }

    protected function buildYouTubeLayer(array $params): string
    {
        $youtubeData = $params['youtubeData'] ?? null;
        if (!$youtubeData) {
            return '';
        }

        $parts = ['YOUTUBE CONTEXT:'];

        if (!empty($youtubeData['title'])) {
            $parts[] = "Video title: \"{$youtubeData['title']}\"";
        }
        if (!empty($youtubeData['channel'])) {
            $parts[] = "Channel: {$youtubeData['channel']}";
        }
        if (!empty($youtubeData['tags']) && is_array($youtubeData['tags'])) {
            $tags = implode(', ', array_slice($youtubeData['tags'], 0, 10));
            $parts[] = "Tags: {$tags}";
        }

        return count($parts) > 1 ? implode("\n" , $parts) : '';
    }

    protected function buildFinalLayer(array $params): string
    {
        $parts = [];

        // User custom prompt
        $custom = trim($params['customPrompt'] ?? '');
        if ($custom) {
            $parts[] = "ADDITIONAL CREATIVE DIRECTION: {$custom}";
        }

        // Title context
        $title = $params['title'] ?? '';
        if ($title) {
            $parts[] = "The thumbnail is for content titled: \"{$title}\"";
        }

        // Universal rules
        $parts[] = "CRITICAL RULES:\n"
            . "- Do NOT include any text, letters, numbers, or words in the image\n"
            . "- Aspect ratio must be 16:9 (widescreen landscape)\n"
            . "- Must look good at small sizes (mobile-friendly)\n"
            . "- Use strong focal point that draws the eye immediately\n"
            . "- High resolution, sharp details, professional quality";

        return implode("\n\n", $parts);
    }
}
