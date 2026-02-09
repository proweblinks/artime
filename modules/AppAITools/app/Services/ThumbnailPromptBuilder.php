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
        $strength = $params['styleStrength'] ?? 0.7;
        $strengthLang = $this->strengthLanguage($strength);

        if ($mode === 'upgrade') {
            return "UPGRADE TRANSFORM: You are improving an existing YouTube thumbnail.\n"
                . "Keep the core concept and subject matter but dramatically enhance:\n"
                . "- Visual quality: 8K photorealistic, cinematic film still quality\n"
                . "- Color grading: Professional color science, rich tonal range\n"
                . "- Composition: Rule of thirds, strong visual hierarchy\n"
                . "- Lighting: Professional cinematography lighting with depth\n"
                . "- Overall polish: Make it look like a top creator's thumbnail\n"
                . "{$strengthLang} the original concept while maximizing visual quality.";
        }

        if ($mode === 'reference') {
            $refType = $params['referenceType'] ?? 'auto';
            return $this->buildReferenceInstructions($refType, $params);
        }

        return '';
    }

    protected function buildReferenceInstructions(string $refType, array $params): string
    {
        $faceStrength = $params['faceStrength'] ?? 0.8;
        $styleStrength = $params['styleStrength'] ?? 0.7;
        $faceLang = $this->strengthLanguage($faceStrength);
        $styleLang = $this->strengthLanguage($styleStrength);

        $instructions = [
            'face' => $this->buildFacePreservePrompt($faceLang),

            'product' => "PRODUCT SHOWCASE: Use the reference image to identify the product/object.\n"
                . "Feature this product prominently in the thumbnail with professional product photography aesthetics.\n"
                . "- Maintain exact product shape, proportions, branding, and color\n"
                . "- Use dramatic lighting to highlight product features\n"
                . "- {$styleLang} the product's visual identity",

            'style' => "STYLE TRANSFER: Analyze the visual style of the reference image.\n"
                . "Extract and apply these style elements to the new thumbnail:\n"
                . "1. COLOR PALETTE: Identical color scheme, saturation, and tonal range\n"
                . "2. LIGHTING STYLE: Same direction, quality (hard/soft), and color temperature\n"
                . "3. COMPOSITION: Similar framing, depth of field, and visual weight distribution\n"
                . "4. MOOD/ATMOSPHERE: Same emotional tone and visual energy\n"
                . "5. TEXTURE/GRAIN: Match post-processing aesthetics (film grain, sharpness)\n"
                . "6. CONTRAST RATIO: Same shadow depth and highlight brightness\n"
                . "{$styleLang} these style elements in the new thumbnail.",

            'background' => "BACKGROUND REFERENCE: Use the reference image as environment/setting reference.\n"
                . "LOCATION PRESERVATION:\n"
                . "1. ARCHITECTURE: Maintain identical structural elements, spatial layout\n"
                . "2. MATERIALS & TEXTURES: Same surfaces, finishes, and materials\n"
                . "3. COLOR PALETTE: Identical environmental color scheme\n"
                . "4. LIGHTING DIRECTION: Same light source positions and quality\n"
                . "5. ATMOSPHERE: Same visual mood, depth, and ambiance\n"
                . "{$styleLang} these environmental elements.",

            'auto' => "REFERENCE ANALYSIS: Analyze the reference image and intelligently determine the best approach.\n"
                . "If a PERSON is prominently featured:\n" . $this->buildFacePreservePrompt($faceLang) . "\n"
                . "If NO person is featured, apply STYLE TRANSFER:\n"
                . "- Extract color palette, lighting, composition, and mood\n"
                . "- {$styleLang} these visual elements in the new thumbnail.",
        ];

        return $instructions[$refType] ?? $instructions['auto'];
    }

    /**
     * Build face preservation prompt using Character DNA technique from VideoWizard.
     */
    protected function buildFacePreservePrompt(string $strengthLang): string
    {
        return <<<EOT
FACE IDENTITY PRESERVATION (CRITICAL):
Generate an image of THIS EXACT PERSON from the reference image.

IDENTITY DNA - {$strengthLang} these features:
- FACE: Same exact facial structure, jawline, cheekbones, forehead shape
- EYES: Same eye shape, color, spacing, brow shape and thickness
- NOSE: Same nose shape, bridge width, tip shape
- MOUTH: Same lip shape, mouth width, smile characteristics
- SKIN: Same skin tone, complexion, texture (natural, no airbrushing)
- HAIR: Same hair color, style, length, texture, parting
- BODY: Same build, proportions, posture characteristics

QUALITY REQUIREMENTS:
- 8K photorealistic, cinematic film still quality
- Natural skin texture with visible pores
- Professional cinematography lighting
- Sharp focus on face, cinematic depth of field

OUTPUT: Generate showing THIS EXACT SAME PERSON (not a similar person, THE SAME person) with their EXACT appearance in the described scene.
EOT;
    }

    /**
     * Convert strength float (0.3-1.0) to natural language instruction.
     */
    protected function strengthLanguage(float $strength): string
    {
        if ($strength >= 0.9) {
            return 'STRICTLY preserve exactly';
        } elseif ($strength >= 0.7) {
            return 'Closely match and preserve';
        } elseif ($strength >= 0.5) {
            return 'Loosely inspired by, maintain the general feel of';
        }
        return 'Take creative liberty while referencing';
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
