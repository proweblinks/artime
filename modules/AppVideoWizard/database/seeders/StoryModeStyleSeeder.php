<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\StoryModeStyle;

class StoryModeStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $styles = [
            [
                'slug' => '2d-line',
                'name' => '2D Line',
                'category' => 'illustration',
                'description' => 'Clean line art with minimal color and hand-drawn feel',
                'style_instruction' => 'Clean line art illustration style, minimal color palette, hand-drawn quality with precise linework, simple flat backgrounds, elegant outlines, modern graphic novel aesthetic, white or light backgrounds with bold dark outlines',
                'config' => ['color_palette' => 'minimal', 'line_weight' => 'medium', 'texture' => 'clean'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'collage',
                'name' => 'Collage',
                'category' => 'artistic',
                'description' => 'Mixed media collage with cut-out paper and layered textures',
                'style_instruction' => 'Mixed media collage art style, cut-out paper elements, layered textures, vintage magazine clippings, overlapping materials, craft paper background, tactile handmade quality, slightly rough edges, warm earthy tones with pops of color',
                'config' => ['color_palette' => 'warm', 'texture' => 'layered', 'composition' => 'collage'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'animation',
                'name' => 'Animation',
                'category' => 'animation',
                'description' => '3D Pixar-style animation with vibrant colors',
                'style_instruction' => '3D Pixar-style animated render, vibrant saturated colors, smooth subsurface scattering on skin, soft ambient occlusion, rounded character designs, cinematic lighting with warm key light and cool fill, detailed environment design, professional animation studio quality',
                'config' => ['color_palette' => 'vibrant', 'rendering' => '3d', 'style' => 'pixar'],
                'sort_order' => 3,
            ],
            [
                'slug' => 'blue-vox',
                'name' => 'Blue Vox',
                'category' => 'animation',
                'description' => 'Blue-tinted voxel art with geometric 3D blocks',
                'style_instruction' => 'Voxel art style with blue-tinted color palette, geometric 3D cube blocks, isometric perspective, low-poly aesthetic, soft blue lighting, clean digital rendering, Minecraft-inspired but more refined, cool blue and cyan color scheme with white accents',
                'config' => ['color_palette' => 'blue', 'rendering' => 'voxel', 'perspective' => 'isometric'],
                'sort_order' => 4,
            ],
            [
                'slug' => 'claymation',
                'name' => 'Claymation',
                'category' => 'animation',
                'description' => 'Stop-motion clay animation with textured surfaces',
                'style_instruction' => 'Claymation stop-motion animation style, visible clay textures and fingerprint marks, warm studio lighting with soft shadows, slightly imperfect handcrafted quality, matte clay surfaces, miniature set design, Aardman or Laika studio aesthetic, warm color palette',
                'config' => ['color_palette' => 'warm', 'texture' => 'clay', 'rendering' => 'stop-motion'],
                'sort_order' => 5,
            ],
            [
                'slug' => 'claire',
                'name' => 'Claire',
                'category' => 'illustration',
                'description' => 'Portrait illustration with warm tones and editorial style',
                'style_instruction' => 'Editorial portrait illustration style, warm color tones, soft watercolor-like rendering with digital precision, elegant character design, fashion illustration influence, expressive faces with simplified features, warm golden and rose tones, magazine cover quality',
                'config' => ['color_palette' => 'warm-rose', 'style' => 'editorial', 'rendering' => 'digital-watercolor'],
                'sort_order' => 6,
            ],
            [
                'slug' => 'marcinelle',
                'name' => 'Marcinelle',
                'category' => 'illustration',
                'description' => 'Franco-Belgian comic book style with bold outlines',
                'style_instruction' => 'Franco-Belgian comic book style (Marcinelle school), bold black outlines, bright primary colors, expressive cartoony character designs, dynamic poses, clear ligne claire influence, Tintin and Spirou aesthetic, clean color fills with minimal shading, white speech bubble style',
                'config' => ['color_palette' => 'primary', 'line_weight' => 'bold', 'style' => 'franco-belgian'],
                'sort_order' => 7,
            ],
            [
                'slug' => 'pen-ink',
                'name' => 'Pen & Ink',
                'category' => 'artistic',
                'description' => 'Detailed pen and ink illustration with cross-hatching',
                'style_instruction' => 'Detailed pen and ink illustration, intricate cross-hatching for shading, fine linework, monochrome black and white, high contrast, stippling and hatching techniques, vintage engraving quality, detailed textures through line density variation, classic book illustration style',
                'config' => ['color_palette' => 'monochrome', 'line_weight' => 'fine', 'technique' => 'cross-hatching'],
                'sort_order' => 8,
            ],
            [
                'slug' => 'schematic',
                'name' => 'Schematic',
                'category' => 'artistic',
                'description' => 'Technical drawing style with blueprint aesthetic',
                'style_instruction' => 'Technical schematic drawing style, blueprint aesthetic with deep blue background and white linework, engineering diagram quality, wireframe elements, measurement annotations, grid overlay, architectural rendering, patent illustration style, clean precise lines, labeled components',
                'config' => ['color_palette' => 'blueprint', 'style' => 'technical', 'rendering' => 'wireframe'],
                'sort_order' => 9,
            ],
            [
                'slug' => 'vox',
                'name' => 'Vox',
                'category' => 'animation',
                'description' => 'Colorful voxel/isometric 3D with low-poly geometric style',
                'style_instruction' => 'Colorful voxel art, isometric 3D perspective, low-poly geometric shapes, bright and playful color palette, clean sharp edges, game-like aesthetic, miniature diorama feel, warm lighting, pixel-perfect cube construction, cheerful and vibrant scene design',
                'config' => ['color_palette' => 'colorful', 'rendering' => 'voxel', 'perspective' => 'isometric'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'watercolor',
                'name' => 'Watercolor',
                'category' => 'artistic',
                'description' => 'Soft watercolor painting with flowing colors',
                'style_instruction' => 'Soft watercolor painting style, flowing wet-on-wet color bleeds, visible paper texture, translucent layered washes, gentle color gradients, organic shapes with soft edges, slightly imperfect and organic feel, pastel to medium saturation, white paper showing through, artistic painterly quality',
                'config' => ['color_palette' => 'pastel', 'texture' => 'paper', 'technique' => 'wet-on-wet'],
                'sort_order' => 11,
            ],
            [
                'slug' => 'halftone',
                'name' => 'Halftone',
                'category' => 'artistic',
                'description' => 'Retro halftone dot printing and newspaper comic style',
                'style_instruction' => 'Retro halftone dot printing style, newspaper comic aesthetic, Ben-Day dots pattern, limited CMYK color palette, pop art influence, Roy Lichtenstein inspired, bold outlines with dotted shading, vintage print quality, slightly misregistered colors, pulp comic book feel',
                'config' => ['color_palette' => 'cmyk', 'texture' => 'halftone-dots', 'style' => 'pop-art'],
                'sort_order' => 12,
            ],
            [
                'slug' => 'economic',
                'name' => 'Economic',
                'category' => 'illustration',
                'description' => 'Minimalist infographic style with clean charts and diagrams',
                'style_instruction' => 'Minimalist infographic illustration style, clean geometric shapes, flat design with limited color palette, data visualization aesthetic, simple icon-like figures, chart and diagram inspired layouts, corporate clean design, subtle grid alignment, professional presentation quality, muted business colors',
                'config' => ['color_palette' => 'muted', 'style' => 'infographic', 'rendering' => 'flat'],
                'sort_order' => 13,
            ],
            [
                'slug' => 'cinematic',
                'name' => 'Cinematic Realistic',
                'category' => 'realistic',
                'description' => 'Photorealistic cinematic with film grain and dramatic lighting',
                'style_instruction' => 'Photorealistic cinematic style, dramatic film lighting with strong key light and atmospheric haze, subtle film grain texture, shallow depth of field, anamorphic lens flare, Hollywood color grading with teal and orange tones, 35mm film aesthetic, high production value, cinematic aspect ratio composition',
                'config' => ['color_palette' => 'cinematic', 'rendering' => 'photorealistic', 'style' => 'film'],
                'sort_order' => 14,
            ],
        ];

        foreach ($styles as $styleData) {
            StoryModeStyle::updateOrCreate(
                ['slug' => $styleData['slug']],
                array_merge($styleData, [
                    'is_active' => true,
                    'is_system' => true,
                ])
            );
        }

        $this->command->info('Story Mode styles seeded successfully (' . count($styles) . ' styles).');
    }
}
