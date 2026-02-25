<?php

namespace Modules\AppAIContents\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppAIContents\Models\CreativeLayoutTemplate;

class CreativeLayoutTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = array_merge(
            $this->overlayTemplates(),
            $this->splitTemplates(),
            $this->editorialTemplates(),
            $this->heroTemplates(),
            $this->minimalTemplates(),
            $this->typographicTemplates(),
            $this->geometricTemplates(),
            $this->magazineTemplates()
        );

        foreach ($templates as $i => $tpl) {
            CreativeLayoutTemplate::updateOrCreate(
                ['slug' => $tpl['slug']],
                array_merge($tpl, ['sort_order' => $i])
            );
        }
    }

    // ─── Helpers to build config quickly ───

    private function cfg(array $canvas, array $imageRegion, array $overlay = [], array $bgBlocks = [], array $decorations = [], array $textRegions = [], array $typo = []): array
    {
        return [
            'version' => 1,
            'canvas' => $canvas,
            'image_region' => $imageRegion,
            'overlay' => $overlay ?: ['type' => 'none'],
            'background_blocks' => $bgBlocks,
            'decorations' => $decorations,
            'text_regions' => $textRegions,
            'typography' => array_merge(['header_font_preference' => 'bold_sans', 'body_font_preference' => 'regular_sans', 'base_size_1080' => 72, 'rtl_size_adjust' => 1.05], $typo),
        ];
    }

    private function grad(string $dir = 'to_bottom', array $stops = []): array
    {
        return ['type' => 'gradient', 'gradient_direction' => $dir, 'gradient_stops' => $stops ?: [
            ['color' => '#000000', 'opacity' => 0, 'position_pct' => 0],
            ['color' => '#000000', 'opacity' => 70, 'position_pct' => 100],
        ]];
    }

    private function headerRegion(float $x, float $y, float $w, string $align = 'left', float $size = 1.0, string $weight = 'bold', string $color = 'light', ?string $transform = null, ?array $shadow = null): array
    {
        $r = ['x_pct' => $x, 'y_pct' => $y, 'width_pct' => $w, 'alignment' => $align, 'size_scale' => $size, 'weight' => $weight, 'color_mode' => $color, 'line_height_scale' => 1.15];
        if ($transform) $r['transform'] = $transform;
        if ($shadow) $r['shadow'] = $shadow;
        else $r['shadow'] = ['x' => 0, 'y' => 2, 'blur' => 8, 'color' => '#000', 'opacity' => 50];
        return $r;
    }

    private function descRegion(float $x, float $y, float $w, string $align = 'left', string $color = 'light', float $size = 0.44): array
    {
        return ['x_pct' => $x, 'y_pct' => $y, 'width_pct' => $w, 'alignment' => $align, 'size_scale' => $size, 'weight' => 'normal', 'color_mode' => $color, 'line_height_scale' => 1.4];
    }

    private function ctaRegion(float $x, float $y, float $w = 50, string $align = 'left', string $pillBg = 'light', string $pillText = 'brand_color'): array
    {
        return ['x_pct' => $x, 'y_pct' => $y, 'width_pct' => $w, 'alignment' => $align, 'size_scale' => 0.39, 'weight' => 'semibold', 'style' => 'pill', 'pill_bg_color_mode' => $pillBg, 'pill_text_color_mode' => $pillText, 'pill_border_radius_pct' => 50];
    }

    // ─── OVERLAY category (7 templates, first = backward-compat "bottom-overlay") ───

    private function overlayTemplates(): array
    {
        return [
            [
                'slug' => 'bottom-overlay', 'name' => 'Bottom Overlay', 'category' => 'overlay',
                'description' => 'Full-bleed image with gradient from bottom, text pinned to bottom.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>78,'position_pct'=>60],['color'=>'#000','opacity'=>90,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 75, 90), 'description' => $this->descRegion(5, 83, 85), 'cta' => $this->ctaRegion(5, 91)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'overlay-top-fade', 'name' => 'Top Fade Overlay', 'category' => 'overlay',
                'description' => 'Full-bleed image with top-down gradient, text at top.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_top', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>75,'position_pct'=>60],['color'=>'#000','opacity'=>88,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 6, 90), 'description' => $this->descRegion(5, 15, 85), 'cta' => $this->ctaRegion(5, 23)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'overlay-vignette', 'name' => 'Vignette Overlay', 'category' => 'overlay',
                'description' => 'Full-bleed with dark vignette, text bottom-left.',
                'config' => $this->cfg(
                    ['background_color' => '#000000'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>55,'position_pct'=>50],['color'=>'#000','opacity'=>85,'position_pct'=>100]]),
                    [], [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 78, 'width_pct' => 15, 'height_pct' => 0.4, 'color_mode' => 'accent']],
                    ['header' => $this->headerRegion(5, 79, 90), 'description' => $this->descRegion(5, 87, 80), 'cta' => $this->ctaRegion(5, 93)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'overlay-brand-tint', 'name' => 'Brand Tint Overlay', 'category' => 'overlay',
                'description' => 'Full image with brand-colored gradient tint.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    ['type' => 'gradient', 'gradient_direction' => 'to_bottom', 'gradient_stops' => [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>65,'position_pct'=>100]]],
                    [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 70, 'width_pct' => 100, 'height_pct' => 30, 'color_mode' => 'brand_color', 'opacity' => 40]],
                    [],
                    ['header' => $this->headerRegion(5, 74, 90, 'left', 1.0, 'bold', 'light'), 'description' => $this->descRegion(5, 83, 85), 'cta' => $this->ctaRegion(5, 91)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'overlay-dual-gradient', 'name' => 'Dual Gradient', 'category' => 'overlay',
                'description' => 'Top and bottom gradients with text split.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>70,'position_pct'=>0],['color'=>'#000','opacity'=>0,'position_pct'=>30],['color'=>'#000','opacity'=>0,'position_pct'=>70],['color'=>'#000','opacity'=>80,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 5, 90, 'left', 1.1, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(5, 85, 85), 'cta' => $this->ctaRegion(5, 93)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'overlay-right-text', 'name' => 'Right Text Overlay', 'category' => 'overlay',
                'description' => 'Full image with text aligned right over gradient.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>80,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(10, 76, 85, 'right'), 'description' => $this->descRegion(15, 84, 80, 'right'), 'cta' => $this->ctaRegion(40, 92, 55, 'right')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'overlay-center-bottom', 'name' => 'Center Bottom Overlay', 'category' => 'overlay',
                'description' => 'Full image, centered text at bottom.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>82,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 76, 90, 'center', 1.05), 'description' => $this->descRegion(10, 85, 80, 'center'), 'cta' => $this->ctaRegion(25, 92, 50, 'center')]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── SPLIT category (7 templates, first = backward-compat "split-bottom") ───

    private function splitTemplates(): array
    {
        return [
            [
                'slug' => 'split-bottom', 'name' => 'Split Bottom', 'category' => 'split',
                'description' => 'Image top 55%, brand-color block bottom 45%.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 55, 'fit' => 'cover', 'gravity' => 'top'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 55, 'width_pct' => 100, 'height_pct' => 45, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(5, 60, 90, 'left', 0.9, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 70, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 80, 50, 'left', 'light', 'brand_color')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'split-top', 'name' => 'Split Top', 'category' => 'split',
                'description' => 'Brand block top 40%, image bottom 60%.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 40, 'width_pct' => 100, 'height_pct' => 60, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 40, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 5, 'width_pct' => 12, 'height_pct' => 0.4, 'color_mode' => 'light']],
                    ['header' => $this->headerRegion(5, 8, 90, 'left', 0.9, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 20, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 30, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'split-half', 'name' => 'Half & Half', 'category' => 'split',
                'description' => 'Image left 50%, text panel right 50%.',
                'config' => $this->cfg(
                    ['background_color' => '#f8f8f8'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 50, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 50, 'width_pct' => 100, 'height_pct' => 50, 'color_mode' => 'dark', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(5, 55, 90, 'left', 0.85, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 66, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 77, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'split-diagonal', 'name' => 'Diagonal Split', 'category' => 'split',
                'description' => 'Image top with diagonal cut, brand panel below.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 58, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 55, 'width_pct' => 100, 'height_pct' => 45, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [['type' => 'diagonal', 'x1_pct' => 0, 'y1_pct' => 58, 'x2_pct' => 100, 'y2_pct' => 52, 'color_mode' => 'brand_color', 'opacity' => 100, 'thickness_pct' => 0.8]],
                    ['header' => $this->headerRegion(5, 62, 90, 'left', 0.9, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 73, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 83, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'split-wave', 'name' => 'Wave Split', 'category' => 'split',
                'description' => 'Image top with wave separator into brand panel.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 58, 'fit' => 'cover', 'gravity' => 'center', 'clip_shape' => 'wave_top', 'clip_params' => ['amplitude_pct' => 0, 'frequency' => 0]],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 55, 'width_pct' => 100, 'height_pct' => 45, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [['type' => 'wave_separator', 'y_pct' => 54, 'amplitude_pct' => 2.5, 'frequency' => 2, 'color_mode' => 'brand_color', 'fill_height_pct' => 4]],
                    ['header' => $this->headerRegion(5, 62, 90, 'left', 0.9, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 73, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 83, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'split-thirds', 'name' => 'Split Thirds', 'category' => 'split',
                'description' => 'Brand header top third, image middle, text bottom.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 28, 'width_pct' => 100, 'height_pct' => 44, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 28, 'color_mode' => 'brand_color', 'opacity' => 100], ['type' => 'rect', 'x_pct' => 0, 'y_pct' => 72, 'width_pct' => 100, 'height_pct' => 28, 'color_mode' => 'dark', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(5, 6, 90, 'left', 0.85, 'bold', 'light', 'uppercase', null), 'description' => $this->descRegion(5, 76, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 87, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'split-asymmetric', 'name' => 'Asymmetric Split', 'category' => 'split',
                'description' => 'Image 65% top, narrow brand strip with text.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 65, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 65, 'width_pct' => 100, 'height_pct' => 35, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 68, 'width_pct' => 8, 'height_pct' => 0.3, 'color_mode' => 'light']],
                    ['header' => $this->headerRegion(5, 70, 90, 'left', 0.8, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 79, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 88, 50)]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── EDITORIAL category (6 templates) ───

    private function editorialTemplates(): array
    {
        return [
            [
                'slug' => 'editorial-clean', 'name' => 'Clean Editorial', 'category' => 'editorial',
                'description' => 'White background, image inset with padding, text below.',
                'config' => $this->cfg(
                    ['background_color' => '#f8f8f8'],
                    ['x_pct' => 5, 'y_pct' => 5, 'width_pct' => 90, 'height_pct' => 55, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 64, 'width_pct' => 10, 'height_pct' => 0.3, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(5, 66, 90, 'left', 0.85, 'bold', 'dark', null, null), 'description' => $this->descRegion(5, 76, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 86, 50, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'editorial-frame', 'name' => 'Framed Editorial', 'category' => 'editorial',
                'description' => 'Dark frame around inset image, elegant text.',
                'config' => $this->cfg(
                    ['background_color' => '#1a1a2e'],
                    ['x_pct' => 8, 'y_pct' => 8, 'width_pct' => 84, 'height_pct' => 52, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 8, 'y_pct' => 64, 'width_pct' => 15, 'height_pct' => 0.3, 'color_mode' => 'accent']],
                    ['header' => $this->headerRegion(8, 67, 84, 'left', 0.8, 'bold', 'light', null, null), 'description' => $this->descRegion(8, 77, 80, 'left', 'light'), 'cta' => $this->ctaRegion(8, 87, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'editorial-sidebar', 'name' => 'Sidebar Editorial', 'category' => 'editorial',
                'description' => 'Brand sidebar left, image and text right.',
                'config' => $this->cfg(
                    ['background_color' => '#ffffff'],
                    ['x_pct' => 12, 'y_pct' => 5, 'width_pct' => 83, 'height_pct' => 50, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 10, 'height_pct' => 100, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(14, 60, 80, 'left', 0.8, 'bold', 'dark', null, null), 'description' => $this->descRegion(14, 72, 78, 'left', 'muted'), 'cta' => $this->ctaRegion(14, 83, 50, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'editorial-quote', 'name' => 'Quote Editorial', 'category' => 'editorial',
                'description' => 'Large quote-style header over dark panel.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 50, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 50, 'width_pct' => 100, 'height_pct' => 50, 'color_mode' => 'dark', 'opacity' => 100]],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 54, 'width_pct' => 2, 'height_pct' => 12, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(10, 55, 85, 'left', 1.0, 'bold', 'light', null, null), 'description' => $this->descRegion(10, 72, 80, 'left', 'muted'), 'cta' => $this->ctaRegion(10, 85, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'editorial-border', 'name' => 'Border Editorial', 'category' => 'editorial',
                'description' => 'Thin brand border around entire card.',
                'config' => $this->cfg(
                    ['background_color' => '#ffffff'],
                    ['x_pct' => 4, 'y_pct' => 4, 'width_pct' => 92, 'height_pct' => 54, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 1, 'y_pct' => 1, 'width_pct' => 98, 'height_pct' => 98, 'color_mode' => 'brand_color', 'opacity' => 15]],
                    [['type' => 'bar', 'x_pct' => 4, 'y_pct' => 62, 'width_pct' => 92, 'height_pct' => 0.15, 'color_mode' => 'brand_color', 'opacity' => 40]],
                    ['header' => $this->headerRegion(6, 66, 88, 'left', 0.8, 'bold', 'dark', null, null), 'description' => $this->descRegion(6, 76, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(6, 87, 50, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'editorial-minimal-dark', 'name' => 'Dark Editorial', 'category' => 'editorial',
                'description' => 'Dark bg with small image and clean text.',
                'config' => $this->cfg(
                    ['background_color' => '#0d0d0d'],
                    ['x_pct' => 5, 'y_pct' => 8, 'width_pct' => 90, 'height_pct' => 48, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 60, 'width_pct' => 8, 'height_pct' => 0.25, 'color_mode' => 'accent']],
                    ['header' => $this->headerRegion(5, 63, 90, 'left', 0.8, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 74, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 85, 50)]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── HERO category (6 templates, first = backward-compat "center-hero") ───

    private function heroTemplates(): array
    {
        return [
            [
                'slug' => 'center-hero', 'name' => 'Center Hero', 'category' => 'hero',
                'description' => 'Full-bleed image, centered large header, desc below.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    ['type' => 'gradient', 'gradient_direction' => 'to_bottom', 'gradient_stops' => [['color'=>'#000','opacity'=>35,'position_pct'=>0],['color'=>'#000','opacity'=>55,'position_pct'=>100]]],
                    [], [],
                    ['header' => $this->headerRegion(5, 38, 90, 'center', 1.2, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(10, 52, 80, 'center'), 'cta' => $this->ctaRegion(25, 90, 50, 'center')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'hero-bold', 'name' => 'Bold Hero', 'category' => 'hero',
                'description' => 'Extra large text centered, heavy vignette.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    ['type' => 'gradient', 'gradient_direction' => 'to_bottom', 'gradient_stops' => [['color'=>'#000','opacity'=>40,'position_pct'=>0],['color'=>'#000','opacity'=>65,'position_pct'=>100]]],
                    [], [],
                    ['header' => $this->headerRegion(5, 32, 90, 'center', 1.5, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(10, 52, 80, 'center'), 'cta' => $this->ctaRegion(25, 88, 50, 'center')]
                ), 'is_active' => true, 'config_typo' => ['base_size_1080' => 52],
            ],
            [
                'slug' => 'hero-left', 'name' => 'Left Hero', 'category' => 'hero',
                'description' => 'Full image, large left-aligned hero text.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>30,'position_pct'=>0],['color'=>'#000','opacity'=>70,'position_pct'=>100]]),
                    [], [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 37, 'width_pct' => 15, 'height_pct' => 0.5, 'color_mode' => 'accent']],
                    ['header' => $this->headerRegion(5, 40, 70, 'left', 1.15, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(5, 55, 65), 'cta' => $this->ctaRegion(5, 90)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'hero-brand-panel', 'name' => 'Hero Brand Panel', 'category' => 'hero',
                'description' => 'Full image with brand-colored transparent panel.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    [],
                    [['type' => 'rect', 'x_pct' => 3, 'y_pct' => 35, 'width_pct' => 60, 'height_pct' => 35, 'color_mode' => 'brand_color', 'opacity' => 80, 'border_radius_pct' => 1]],
                    [],
                    ['header' => $this->headerRegion(6, 38, 54, 'left', 0.9, 'bold', 'light', null, null), 'description' => $this->descRegion(6, 50, 52, 'left', 'light'), 'cta' => $this->ctaRegion(6, 61, 40)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'hero-stacked', 'name' => 'Stacked Hero', 'category' => 'hero',
                'description' => 'Stacked large header lines over full image.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>45,'position_pct'=>0],['color'=>'#000','opacity'=>60,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 25, 90, 'center', 1.3, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(10, 45, 80, 'center'), 'cta' => $this->ctaRegion(25, 92, 50, 'center')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'hero-minimal-text', 'name' => 'Minimal Hero', 'category' => 'hero',
                'description' => 'Full image, very small centered text, lots of photo.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>50,'position_pct'=>85],['color'=>'#000','opacity'=>75,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(10, 86, 80, 'center', 0.7, 'bold', 'light'), 'description' => $this->descRegion(15, 92, 70, 'center', 'light', 0.4), 'cta' => $this->ctaRegion(30, 96, 40, 'center')]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── MINIMAL category (6 templates) ───

    private function minimalTemplates(): array
    {
        return [
            [
                'slug' => 'minimal-white', 'name' => 'Minimal White', 'category' => 'minimal',
                'description' => 'Clean white background, small image, elegant text.',
                'config' => $this->cfg(
                    ['background_color' => '#ffffff'],
                    ['x_pct' => 10, 'y_pct' => 10, 'width_pct' => 80, 'height_pct' => 45, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 10, 'y_pct' => 59, 'width_pct' => 6, 'height_pct' => 0.2, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(10, 62, 80, 'left', 0.75, 'bold', 'dark', null, null), 'description' => $this->descRegion(10, 73, 75, 'left', 'muted'), 'cta' => $this->ctaRegion(10, 84, 40, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'minimal-dark', 'name' => 'Minimal Dark', 'category' => 'minimal',
                'description' => 'Dark background, centered small image, light text.',
                'config' => $this->cfg(
                    ['background_color' => '#0a0a0a'],
                    ['x_pct' => 15, 'y_pct' => 12, 'width_pct' => 70, 'height_pct' => 42, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [],
                    ['header' => $this->headerRegion(10, 60, 80, 'center', 0.7, 'bold', 'light', null, null), 'description' => $this->descRegion(15, 72, 70, 'center', 'muted'), 'cta' => $this->ctaRegion(25, 84, 50, 'center')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'minimal-left-align', 'name' => 'Minimal Left', 'category' => 'minimal',
                'description' => 'Left-aligned text, right image.',
                'config' => $this->cfg(
                    ['background_color' => '#fafafa'],
                    ['x_pct' => 5, 'y_pct' => 5, 'width_pct' => 90, 'height_pct' => 50, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [],
                    ['header' => $this->headerRegion(5, 60, 90, 'left', 0.7, 'bold', 'dark', null, null), 'description' => $this->descRegion(5, 72, 80, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 83, 40, 'left', 'dark', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'minimal-centered', 'name' => 'Minimal Centered', 'category' => 'minimal',
                'description' => 'Everything centered, generous whitespace.',
                'config' => $this->cfg(
                    ['background_color' => '#ffffff'],
                    ['x_pct' => 20, 'y_pct' => 15, 'width_pct' => 60, 'height_pct' => 38, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 45, 'y_pct' => 57, 'width_pct' => 10, 'height_pct' => 0.2, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(10, 60, 80, 'center', 0.7, 'bold', 'dark', null, null), 'description' => $this->descRegion(15, 72, 70, 'center', 'muted'), 'cta' => $this->ctaRegion(30, 84, 40, 'center', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'minimal-strip', 'name' => 'Minimal Strip', 'category' => 'minimal',
                'description' => 'Small image strip at top, clean text below.',
                'config' => $this->cfg(
                    ['background_color' => '#f5f5f5'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 35, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [],
                    ['header' => $this->headerRegion(8, 42, 84, 'left', 0.75, 'bold', 'dark', null, null), 'description' => $this->descRegion(8, 55, 80, 'left', 'muted'), 'cta' => $this->ctaRegion(8, 70, 40, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'minimal-card', 'name' => 'Minimal Card', 'category' => 'minimal',
                'description' => 'Card-style with rounded image and padding.',
                'config' => $this->cfg(
                    ['background_color' => '#f0f0f0'],
                    ['x_pct' => 6, 'y_pct' => 6, 'width_pct' => 88, 'height_pct' => 48, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 3, 'y_pct' => 3, 'width_pct' => 94, 'height_pct' => 94, 'color_mode' => 'light', 'opacity' => 100, 'border_radius_pct' => 2]],
                    [],
                    ['header' => $this->headerRegion(8, 60, 84, 'left', 0.75, 'bold', 'dark', null, null), 'description' => $this->descRegion(8, 72, 80, 'left', 'muted'), 'cta' => $this->ctaRegion(8, 84, 40, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── TYPOGRAPHIC category (6 templates) ───

    private function typographicTemplates(): array
    {
        return [
            [
                'slug' => 'typo-bold-statement', 'name' => 'Bold Statement', 'category' => 'typographic',
                'description' => 'Extra large bold text dominates, small image.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 55, 'width_pct' => 100, 'height_pct' => 45, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 5, 'width_pct' => 20, 'height_pct' => 0.5, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(5, 8, 90, 'left', 1.4, 'bold', 'light', 'uppercase', null), 'description' => $this->descRegion(5, 35, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 47, 50)]
                ), 'is_active' => true, 'config_typo' => ['base_size_1080' => 52],
            ],
            [
                'slug' => 'typo-caps-centered', 'name' => 'Caps Centered', 'category' => 'typographic',
                'description' => 'All caps header centered over image.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>50,'position_pct'=>0],['color'=>'#000','opacity'=>70,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 35, 90, 'center', 1.3, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(10, 52, 80, 'center'), 'cta' => $this->ctaRegion(25, 92, 50, 'center')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'typo-wide-spaced', 'name' => 'Wide Spaced', 'category' => 'typographic',
                'description' => 'Wide letter-spacing header, elegant feel.',
                'config' => $this->cfg(
                    ['background_color' => '#1a1a2e'],
                    ['x_pct' => 0, 'y_pct' => 45, 'width_pct' => 100, 'height_pct' => 55, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_top', [['color'=>'#1a1a2e','opacity'=>0,'position_pct'=>0],['color'=>'#1a1a2e','opacity'=>90,'position_pct'=>100]]),
                    [], [],
                    ['header' => array_merge($this->headerRegion(5, 8, 90, 'left', 0.9, 'bold', 'light', 'uppercase', null), ['letter_spacing_pct' => 3]), 'description' => $this->descRegion(5, 25, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 37, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'typo-header-only', 'name' => 'Header Only', 'category' => 'typographic',
                'description' => 'Massive header text, no description, minimal.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>40,'position_pct'=>0],['color'=>'#000','opacity'=>75,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 60, 90, 'left', 1.6, 'bold', 'light', 'uppercase'), 'cta' => $this->ctaRegion(5, 90)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'typo-brand-bg', 'name' => 'Brand Typography', 'category' => 'typographic',
                'description' => 'Brand color background, white text, small image.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 50, 'width_pct' => 100, 'height_pct' => 50, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 50, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(5, 8, 90, 'left', 1.1, 'bold', 'light', 'uppercase', null), 'description' => $this->descRegion(5, 28, 85, 'left', 'light'), 'cta' => $this->ctaRegion(5, 40, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'typo-right-heavy', 'name' => 'Right Heavy Type', 'category' => 'typographic',
                'description' => 'Right-aligned large type over image.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>35,'position_pct'=>0],['color'=>'#000','opacity'=>75,'position_pct'=>100]]),
                    [], [],
                    ['header' => $this->headerRegion(5, 55, 90, 'right', 1.2, 'bold', 'light', 'uppercase'), 'description' => $this->descRegion(15, 72, 80, 'right'), 'cta' => $this->ctaRegion(40, 90, 55, 'right')]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── GEOMETRIC category (6 templates) ───

    private function geometricTemplates(): array
    {
        return [
            [
                'slug' => 'geo-circles', 'name' => 'Geometric Circles', 'category' => 'geometric',
                'description' => 'Decorative circles with brand colors.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>20,'position_pct'=>0],['color'=>'#000','opacity'=>80,'position_pct'=>100]]),
                    [],
                    [['type' => 'circle', 'cx_pct' => 85, 'cy_pct' => 10, 'radius_pct' => 8, 'color_mode' => 'brand_color', 'opacity' => 30], ['type' => 'circle', 'cx_pct' => 10, 'cy_pct' => 90, 'radius_pct' => 5, 'color_mode' => 'accent', 'opacity' => 25], ['type' => 'circle', 'cx_pct' => 92, 'cy_pct' => 95, 'radius_pct' => 3, 'color_mode' => 'brand_light', 'opacity' => 20]],
                    ['header' => $this->headerRegion(5, 75, 85), 'description' => $this->descRegion(5, 84, 80), 'cta' => $this->ctaRegion(5, 92)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'geo-corner-triangle', 'name' => 'Corner Triangle', 'category' => 'geometric',
                'description' => 'Brand triangle in corner with text.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>70,'position_pct'=>100]]),
                    [],
                    [['type' => 'triangle', 'x1_pct' => 0, 'y1_pct' => 0, 'x2_pct' => 40, 'y2_pct' => 0, 'x3_pct' => 0, 'y3_pct' => 30, 'color_mode' => 'brand_color', 'opacity' => 60]],
                    ['header' => $this->headerRegion(5, 76, 90), 'description' => $this->descRegion(5, 85, 85), 'cta' => $this->ctaRegion(5, 93)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'geo-stripe', 'name' => 'Side Stripe', 'category' => 'geometric',
                'description' => 'Vertical brand stripe on left side.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 5, 'y_pct' => 0, 'width_pct' => 95, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>75,'position_pct'=>100]]),
                    [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 4, 'height_pct' => 100, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(8, 76, 87), 'description' => $this->descRegion(8, 85, 82), 'cta' => $this->ctaRegion(8, 93)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'geo-blocks', 'name' => 'Color Blocks', 'category' => 'geometric',
                'description' => 'Multiple color blocks framing image.',
                'config' => $this->cfg(
                    ['background_color' => '#0d0d0d'],
                    ['x_pct' => 8, 'y_pct' => 15, 'width_pct' => 84, 'height_pct' => 45, 'fit' => 'cover', 'gravity' => 'center'],
                    [],
                    [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 10, 'color_mode' => 'brand_color', 'opacity' => 100], ['type' => 'rect', 'x_pct' => 0, 'y_pct' => 90, 'width_pct' => 100, 'height_pct' => 10, 'color_mode' => 'brand_secondary', 'opacity' => 80]],
                    [],
                    ['header' => $this->headerRegion(8, 64, 84, 'left', 0.85, 'bold', 'light', null, null), 'description' => $this->descRegion(8, 75, 80, 'left', 'muted'), 'cta' => $this->ctaRegion(8, 85, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'geo-diagonal-bars', 'name' => 'Diagonal Bars', 'category' => 'geometric',
                'description' => 'Diagonal decorative bars over image.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 100, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>80,'position_pct'=>100]]),
                    [],
                    [['type' => 'bar', 'x_pct' => 0, 'y_pct' => 68, 'width_pct' => 35, 'height_pct' => 0.4, 'color_mode' => 'brand_color'], ['type' => 'bar', 'x_pct' => 0, 'y_pct' => 70, 'width_pct' => 25, 'height_pct' => 0.3, 'color_mode' => 'accent', 'opacity' => 60]],
                    ['header' => $this->headerRegion(5, 74, 90), 'description' => $this->descRegion(5, 83, 85), 'cta' => $this->ctaRegion(5, 92)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'geo-frame-accent', 'name' => 'Accent Frame', 'category' => 'geometric',
                'description' => 'Accent-colored partial frame around image.',
                'config' => $this->cfg(
                    ['background_color' => '#0d0d0d'],
                    ['x_pct' => 5, 'y_pct' => 5, 'width_pct' => 90, 'height_pct' => 55, 'fit' => 'cover', 'gravity' => 'center'],
                    [],
                    [],
                    [['type' => 'bar', 'x_pct' => 3, 'y_pct' => 3, 'width_pct' => 30, 'height_pct' => 0.3, 'color_mode' => 'brand_color'], ['type' => 'bar', 'x_pct' => 3, 'y_pct' => 3, 'width_pct' => 0.3, 'height_pct' => 15, 'color_mode' => 'brand_color'], ['type' => 'bar', 'x_pct' => 67, 'y_pct' => 62, 'width_pct' => 30, 'height_pct' => 0.3, 'color_mode' => 'accent'], ['type' => 'bar', 'x_pct' => 97, 'y_pct' => 47, 'width_pct' => 0.3, 'height_pct' => 15, 'color_mode' => 'accent']],
                    ['header' => $this->headerRegion(5, 65, 90, 'left', 0.85, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 76, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 88, 50)]
                ), 'is_active' => true,
            ],
        ];
    }

    // ─── MAGAZINE category (6 templates, first = backward-compat "magazine") ───

    private function magazineTemplates(): array
    {
        return [
            [
                'slug' => 'magazine', 'name' => 'Magazine Classic', 'category' => 'magazine',
                'description' => 'Light bg, image top with rounded bottom, text below.',
                'config' => $this->cfg(
                    ['background_color' => '#f8f8f8'],
                    ['x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 55, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [],
                    ['header' => $this->headerRegion(5, 60, 90, 'left', 0.8, 'bold', 'dark', null, null), 'description' => $this->descRegion(5, 72, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 84, 50, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'magazine-dark', 'name' => 'Magazine Dark', 'category' => 'magazine',
                'description' => 'Dark magazine style with image and text.',
                'config' => $this->cfg(
                    ['background_color' => '#1a1a1a'],
                    ['x_pct' => 5, 'y_pct' => 5, 'width_pct' => 90, 'height_pct' => 50, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 5, 'y_pct' => 59, 'width_pct' => 15, 'height_pct' => 0.3, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(5, 62, 90, 'left', 0.8, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 74, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 86, 50)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'magazine-centered', 'name' => 'Magazine Centered', 'category' => 'magazine',
                'description' => 'Centered magazine layout.',
                'config' => $this->cfg(
                    ['background_color' => '#ffffff'],
                    ['x_pct' => 8, 'y_pct' => 8, 'width_pct' => 84, 'height_pct' => 48, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [],
                    [['type' => 'bar', 'x_pct' => 42, 'y_pct' => 60, 'width_pct' => 16, 'height_pct' => 0.2, 'color_mode' => 'brand_color']],
                    ['header' => $this->headerRegion(8, 64, 84, 'center', 0.8, 'bold', 'dark', null, null), 'description' => $this->descRegion(12, 76, 76, 'center', 'muted'), 'cta' => $this->ctaRegion(25, 88, 50, 'center', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'magazine-brand-header', 'name' => 'Magazine Brand Header', 'category' => 'magazine',
                'description' => 'Brand-colored header strip, image, description.',
                'config' => $this->cfg(
                    ['background_color' => '#ffffff'],
                    ['x_pct' => 0, 'y_pct' => 18, 'width_pct' => 100, 'height_pct' => 48, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 18, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(5, 3, 90, 'left', 0.8, 'bold', 'light', null, null), 'description' => $this->descRegion(5, 72, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 84, 50, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'top-header', 'name' => 'Top Header Magazine', 'category' => 'magazine',
                'description' => 'Bold header at top over brand bar, image below, desc at bottom.',
                'config' => $this->cfg(
                    ['background_color' => '#111111'],
                    ['x_pct' => 0, 'y_pct' => 22, 'width_pct' => 100, 'height_pct' => 78, 'fit' => 'cover', 'gravity' => 'center'],
                    $this->grad('to_bottom', [['color'=>'#000','opacity'=>0,'position_pct'=>0],['color'=>'#000','opacity'=>75,'position_pct'=>85],['color'=>'#000','opacity'=>85,'position_pct'=>100]]),
                    [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 22, 'color_mode' => 'brand_color', 'opacity' => 100]],
                    [],
                    ['header' => $this->headerRegion(5, 4, 90, 'left', 0.85, 'bold', 'light', 'uppercase', null), 'description' => $this->descRegion(5, 85, 85), 'cta' => $this->ctaRegion(5, 93)]
                ), 'is_active' => true,
            ],
            [
                'slug' => 'magazine-two-tone', 'name' => 'Two-Tone Magazine', 'category' => 'magazine',
                'description' => 'Two background tones split with image overlay.',
                'config' => $this->cfg(
                    ['background_color' => '#f8f8f8'],
                    ['x_pct' => 5, 'y_pct' => 10, 'width_pct' => 90, 'height_pct' => 48, 'fit' => 'cover', 'gravity' => 'center'],
                    [], [['type' => 'rect', 'x_pct' => 0, 'y_pct' => 0, 'width_pct' => 100, 'height_pct' => 35, 'color_mode' => 'brand_color', 'opacity' => 15]],
                    [],
                    ['header' => $this->headerRegion(5, 63, 90, 'left', 0.8, 'bold', 'dark', null, null), 'description' => $this->descRegion(5, 75, 85, 'left', 'muted'), 'cta' => $this->ctaRegion(5, 87, 50, 'left', 'brand_color', 'light')]
                ), 'is_active' => true,
            ],
        ];
    }
}
