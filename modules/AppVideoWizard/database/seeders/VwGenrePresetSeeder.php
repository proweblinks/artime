<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\VwGenrePreset;

class VwGenrePresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Professional cinematography genre presets based on industry standards.
     */
    public function run(): void
    {
        $presets = [
            // Documentary genres
            [
                'slug' => 'documentary-narrative',
                'name' => 'Documentary Narrative',
                'category' => 'documentary',
                'description' => 'Classic documentary storytelling with authentic visuals',
                'camera_language' => 'smooth tracking, wide establishing shots, intimate close-ups, observational framing',
                'color_grade' => 'natural tones, slight desaturation, documentary realism, authentic colors',
                'lighting' => 'natural light, available light, practical sources, soft ambient',
                'atmosphere' => 'authentic environments, real textures, genuine moments, unfiltered reality',
                'style' => 'documentary realism, authentic, observational, truthful storytelling',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 24mm lens',
                    'medium' => 'standard 35mm lens',
                    'close-up' => 'portrait 50mm lens',
                    'detail' => '85mm telephoto',
                ]),
                'sort_order' => 1,
            ],
            [
                'slug' => 'documentary-interview',
                'name' => 'Documentary Interview',
                'category' => 'documentary',
                'description' => 'Professional interview-style documentary',
                'camera_language' => 'static interviews, dramatic zooms, handheld urgency, eye-level framing',
                'color_grade' => 'neutral, clean whites, professional grade, natural skin tones',
                'lighting' => '3-point interview lighting, soft key light, subtle fill, separation rim',
                'atmosphere' => 'professional setting, clean backgrounds, focus on subject, minimal distractions',
                'style' => 'interview documentary, professional, clear, authoritative',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 35mm lens',
                    'medium' => 'standard 50mm lens',
                    'close-up' => 'portrait 85mm lens, shallow depth of field',
                ]),
                'sort_order' => 2,
            ],

            // Cinematic genres
            [
                'slug' => 'cinematic-thriller',
                'name' => 'Cinematic Thriller',
                'category' => 'cinematic',
                'description' => 'High-tension thriller with noir aesthetics',
                'camera_language' => 'slow dolly, low angles, stabilized gimbal, anamorphic lens feel, Dutch angles',
                'color_grade' => 'desaturated teal shadows, amber highlights, crushed blacks, high contrast',
                'lighting' => 'harsh single-source, dramatic rim lights, deep shadows, chiaroscuro',
                'atmosphere' => 'smoke, rain reflections, wet surfaces, urban grit, tension',
                'style' => 'ultra-cinematic photoreal, noir thriller, high contrast, Fincher-esque',
                'lens_preferences' => json_encode([
                    'establishing' => 'anamorphic 40mm lens',
                    'medium' => 'anamorphic 50mm lens',
                    'close-up' => 'anamorphic 75mm lens',
                    'detail' => 'macro lens, extreme detail',
                ]),
                'is_default' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'cinematic-action',
                'name' => 'Cinematic Action',
                'category' => 'cinematic',
                'description' => 'Blockbuster action with dynamic visuals',
                'camera_language' => 'fast dolly, Dutch angles, tracking shots, crash zooms, whip pans',
                'color_grade' => 'high contrast, orange and teal, saturated, punchy blacks',
                'lighting' => 'dramatic backlighting, lens flares, explosions, practical fire',
                'atmosphere' => 'dust, debris, fire, motion blur, chaos, energy',
                'style' => 'blockbuster action, high energy, dynamic composition, Michael Bay aesthetic',
                'lens_preferences' => json_encode([
                    'establishing' => 'ultra-wide 16mm lens',
                    'medium' => 'wide 24mm lens',
                    'close-up' => 'standard 50mm lens',
                ]),
                'sort_order' => 11,
            ],
            [
                'slug' => 'cinematic-drama',
                'name' => 'Cinematic Drama',
                'category' => 'cinematic',
                'description' => 'Prestige drama with emotional depth',
                'camera_language' => 'elegant slow movements, meaningful compositions, long takes, deliberate framing',
                'color_grade' => 'rich but restrained, natural skin tones, dramatic contrast, warm shadows',
                'lighting' => 'motivated lighting, golden hour, intimate practicals, soft natural light',
                'atmosphere' => 'subtle, realistic environments, emotional resonance, intimate spaces',
                'style' => 'prestige drama, Oscar-worthy cinematography, emotional depth, Deakins-inspired',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 32mm lens',
                    'medium' => 'standard 50mm lens',
                    'close-up' => 'portrait 85mm lens, f/1.4',
                ]),
                'sort_order' => 12,
            ],
            [
                'slug' => 'cinematic-epic',
                'name' => 'Cinematic Epic',
                'category' => 'cinematic',
                'description' => 'Grand scale epic cinematography',
                'camera_language' => 'sweeping crane shots, helicopter aerials, massive scale, grand compositions',
                'color_grade' => 'rich saturated colors, golden highlights, deep shadows, majestic tones',
                'lighting' => 'natural dramatic light, god rays, sunset/sunrise, epic scale lighting',
                'atmosphere' => 'vast landscapes, armies, mountains, clouds, weather elements',
                'style' => 'epic grandeur, Lord of the Rings aesthetic, awe-inspiring, monumental',
                'lens_preferences' => json_encode([
                    'establishing' => 'ultra-wide 14mm lens',
                    'wide' => 'wide-angle 24mm lens',
                    'medium' => 'standard 50mm lens',
                ]),
                'sort_order' => 13,
            ],

            // Horror genres
            [
                'slug' => 'horror-psychological',
                'name' => 'Psychological Horror',
                'category' => 'horror',
                'description' => 'Unsettling psychological terror',
                'camera_language' => 'Dutch angles, slow creeping push-ins, unstable handheld, voyeuristic framing',
                'color_grade' => 'desaturated, sickly greens, deep blacks, red accents, unnatural tones',
                'lighting' => 'stark contrasts, flickering lights, deep shadows, underexposed areas',
                'atmosphere' => 'claustrophobic spaces, decay, isolation, dread, wrongness',
                'style' => 'psychological horror, Ari Aster style, unsettling, creeping dread',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 24mm with distortion',
                    'medium' => 'standard 35mm lens',
                    'close-up' => 'telephoto 100mm lens, isolating',
                ]),
                'sort_order' => 20,
            ],
            [
                'slug' => 'horror-supernatural',
                'name' => 'Supernatural Horror',
                'category' => 'horror',
                'description' => 'Gothic supernatural horror aesthetics',
                'camera_language' => 'slow reveals, sudden movements, static then jarring, tracking through spaces',
                'color_grade' => 'cold blues, desaturated, crushed blacks, sickly highlights',
                'lighting' => 'practical candles, moonlight, shadows that move, minimal light sources',
                'atmosphere' => 'fog, mist, old buildings, supernatural elements, otherworldly',
                'style' => 'supernatural horror, James Wan aesthetic, gothic, terrifying',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 21mm lens',
                    'medium' => 'standard 40mm lens',
                    'close-up' => 'portrait 85mm lens',
                ]),
                'sort_order' => 21,
            ],

            // Comedy genres
            [
                'slug' => 'comedy-bright',
                'name' => 'Bright Comedy',
                'category' => 'comedy',
                'description' => 'Light, colorful comedy aesthetics',
                'camera_language' => 'clean compositions, steady movement, reaction shots, comedic timing cuts',
                'color_grade' => 'bright, saturated, warm tones, cheerful palette, clean whites',
                'lighting' => 'soft even lighting, bright and airy, flattering, high key',
                'atmosphere' => 'colorful environments, clean spaces, inviting, fun',
                'style' => 'romantic comedy aesthetic, Wes Anderson lite, bright and cheerful',
                'lens_preferences' => json_encode([
                    'establishing' => 'standard 35mm lens',
                    'medium' => 'standard 50mm lens',
                    'close-up' => 'portrait 85mm lens',
                ]),
                'sort_order' => 30,
            ],
            [
                'slug' => 'comedy-dark',
                'name' => 'Dark Comedy',
                'category' => 'comedy',
                'description' => 'Sardonic dark comedy visuals',
                'camera_language' => 'deadpan framing, ironic compositions, uncomfortable holds, wide awkward shots',
                'color_grade' => 'muted colors, slight desaturation, ironic contrast, deliberate drabness',
                'lighting' => 'flat lighting with dramatic moments, practical sources, mundane',
                'atmosphere' => 'awkward spaces, mundane environments, ironic juxtaposition',
                'style' => 'dark comedy, Coen Brothers aesthetic, sardonic, uncomfortable humor',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 28mm lens',
                    'medium' => 'standard 40mm lens',
                    'close-up' => 'standard 50mm lens',
                ]),
                'sort_order' => 31,
            ],

            // Social/Commercial genres
            [
                'slug' => 'social-viral',
                'name' => 'Social Viral',
                'category' => 'social',
                'description' => 'TikTok/Reels optimized viral content',
                'camera_language' => 'dynamic handheld, quick cuts, POV shots, direct to camera, energetic movement',
                'color_grade' => 'vibrant, high contrast, punchy colors, social media optimized',
                'lighting' => 'ring light, natural bright light, clean and flattering',
                'atmosphere' => 'trendy, energetic, immediate, attention-grabbing',
                'style' => 'social media native, viral potential, scroll-stopping, engaging',
                'lens_preferences' => json_encode([
                    'establishing' => 'smartphone ultra-wide',
                    'medium' => 'standard 24mm lens',
                    'close-up' => 'standard 35mm lens',
                ]),
                'sort_order' => 40,
            ],
            [
                'slug' => 'commercial-premium',
                'name' => 'Premium Commercial',
                'category' => 'commercial',
                'description' => 'High-end advertising cinematography',
                'camera_language' => 'smooth slider movements, turntable product shots, hero angles, precision framing',
                'color_grade' => 'clean, polished, brand-appropriate, premium feel, perfect exposure',
                'lighting' => 'professional studio lighting, product highlighting, controlled environment',
                'atmosphere' => 'aspirational, premium, desirable, perfect',
                'style' => 'Apple commercial aesthetic, premium advertising, polished perfection',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 35mm lens',
                    'medium' => 'standard 50mm lens',
                    'close-up' => 'macro lens for product detail',
                    'detail' => '100mm macro lens',
                ]),
                'sort_order' => 50,
            ],

            // Educational
            [
                'slug' => 'educational-explainer',
                'name' => 'Educational Explainer',
                'category' => 'educational',
                'description' => 'Clear educational content visuals',
                'camera_language' => 'clear framing, logical progression, presenter-focused, demonstrative angles',
                'color_grade' => 'clean, clear, good contrast, readable, accessible',
                'lighting' => 'even professional lighting, clear visibility, no harsh shadows',
                'atmosphere' => 'professional, trustworthy, clear, focused on learning',
                'style' => 'educational content, clear communication, professional explainer',
                'lens_preferences' => json_encode([
                    'establishing' => 'standard 35mm lens',
                    'medium' => 'standard 50mm lens',
                    'close-up' => 'standard 50mm lens',
                ]),
                'sort_order' => 60,
            ],

            // Experimental
            [
                'slug' => 'experimental-artistic',
                'name' => 'Experimental Artistic',
                'category' => 'experimental',
                'description' => 'Abstract artistic visual style',
                'camera_language' => 'unconventional angles, abstract framing, rule-breaking compositions, artistic movement',
                'color_grade' => 'stylized, bold choices, artistic interpretation, unique palette',
                'lighting' => 'dramatic, unconventional, artistic lighting choices, experimental',
                'atmosphere' => 'abstract, dreamlike, surreal, artistic expression',
                'style' => 'art house cinema, experimental, avant-garde, visually striking',
                'lens_preferences' => json_encode([
                    'establishing' => 'fisheye or ultra-wide',
                    'medium' => 'tilt-shift lens',
                    'close-up' => 'lensbaby or specialty lens',
                ]),
                'sort_order' => 70,
            ],

            // Standard fallback
            [
                'slug' => 'standard',
                'name' => 'Standard Cinematic',
                'category' => 'cinematic',
                'description' => 'Balanced professional cinematography',
                'camera_language' => 'smooth movements, balanced compositions, professional framing',
                'color_grade' => 'balanced, natural with cinematic punch, professional grade',
                'lighting' => 'professional three-point lighting, natural motivated sources',
                'atmosphere' => 'professional, polished, versatile',
                'style' => 'professional cinematography, balanced, versatile, high quality',
                'lens_preferences' => json_encode([
                    'establishing' => 'wide-angle 24mm lens',
                    'medium' => 'standard 50mm lens',
                    'close-up' => 'portrait 85mm lens',
                ]),
                'sort_order' => 100,
            ],
        ];

        foreach ($presets as $preset) {
            VwGenrePreset::updateOrCreate(
                ['slug' => $preset['slug']],
                array_merge($preset, ['is_active' => true])
            );
        }
    }
}
