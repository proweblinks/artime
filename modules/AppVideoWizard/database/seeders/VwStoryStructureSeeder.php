<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\VwStoryStructure;

class VwStoryStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Story structures based on Hollywood standards and professional screenwriting.
     */
    public function run(): void
    {
        $structures = [
            [
                'slug' => 'classic-three-act',
                'name' => 'Classic Three-Act',
                'description' => 'Traditional Hollywood structure used in most feature films. Setup (25%) → Confrontation (50%) → Resolution (25%)',
                'structure_type' => 'three_act',
                'act_distribution' => json_encode([
                    'act1' => [
                        'percentage' => 25,
                        'label' => 'Setup',
                        'beats' => ['hook', 'introduction', 'normalcy', 'inciting-incident', 'refusal', 'acceptance'],
                        'description' => 'Establish the world, introduce characters, present the conflict'
                    ],
                    'act2' => [
                        'percentage' => 50,
                        'label' => 'Confrontation',
                        'beats' => ['exploration', 'fun-games', 'ally-enemy', 'first-challenge', 'midpoint-twist', 'stakes-raised', 'all-is-lost', 'dark-moment'],
                        'description' => 'Rising action, character growth, obstacles and setbacks'
                    ],
                    'act3' => [
                        'percentage' => 25,
                        'label' => 'Resolution',
                        'beats' => ['realization', 'gathering-forces', 'climax', 'resolution', 'new-normal', 'final-image'],
                        'description' => 'Climax and resolution, emotional payoff'
                    ],
                ]),
                'pacing_curve' => json_encode([
                    [0, 7],    // Hook - attention grabbing
                    [10, 3],   // Introduction - calm
                    [25, 8],   // Inciting incident - spike
                    [35, 5],   // Exploration - moderate
                    [50, 9],   // Midpoint - major spike
                    [65, 6],   // Rising stakes
                    [75, 9],   // All is lost - emotional low but intensity high
                    [90, 10],  // Climax - maximum intensity
                    [100, 4],  // Resolution - calm
                ]),
                'best_for' => json_encode(['drama', 'thriller', 'action', 'romance', 'comedy']),
                'min_scenes' => 5,
                'max_scenes' => 30,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'shorts-structure',
                'name' => 'Short-Form Structure',
                'description' => 'Optimized for 15-60 second social media content. Hook (10%) → Content (70%) → CTA (20%)',
                'structure_type' => 'shorts',
                'act_distribution' => json_encode([
                    'hook' => [
                        'percentage' => 10,
                        'label' => 'Hook',
                        'beats' => ['hook'],
                        'description' => 'Immediate attention grab in first 1-3 seconds'
                    ],
                    'content' => [
                        'percentage' => 70,
                        'label' => 'Content',
                        'beats' => ['fun-games', 'action', 'midpoint-twist'],
                        'description' => 'Deliver the core value/entertainment'
                    ],
                    'cta' => [
                        'percentage' => 20,
                        'label' => 'CTA/Payoff',
                        'beats' => ['climax', 'resolution'],
                        'description' => 'Call to action or satisfying payoff'
                    ],
                ]),
                'pacing_curve' => json_encode([
                    [0, 9],    // Hook - high energy start
                    [10, 7],   // Content start
                    [50, 8],   // Content peak
                    [80, 9],   // Pre-CTA build
                    [100, 6],  // CTA/Payoff
                ]),
                'best_for' => json_encode(['social', 'viral', 'commercial', 'tiktok', 'reels']),
                'min_scenes' => 2,
                'max_scenes' => 5,
                'sort_order' => 2,
            ],
            [
                'slug' => 'hero-journey',
                'name' => 'Hero\'s Journey',
                'description' => 'Joseph Campbell\'s mythic 12-stage structure. Departure → Initiation → Return',
                'structure_type' => 'hero_journey',
                'act_distribution' => json_encode([
                    'departure' => [
                        'percentage' => 25,
                        'label' => 'Departure',
                        'beats' => ['normalcy', 'inciting-incident', 'refusal', 'acceptance'],
                        'description' => 'The call to adventure and crossing the threshold'
                    ],
                    'initiation' => [
                        'percentage' => 50,
                        'label' => 'Initiation',
                        'beats' => ['exploration', 'ally-enemy', 'first-challenge', 'midpoint-twist', 'all-is-lost', 'dark-moment'],
                        'description' => 'Tests, allies, enemies, the ordeal'
                    ],
                    'return' => [
                        'percentage' => 25,
                        'label' => 'Return',
                        'beats' => ['realization', 'climax', 'resolution', 'new-normal'],
                        'description' => 'The reward and return transformed'
                    ],
                ]),
                'pacing_curve' => json_encode([
                    [0, 5],
                    [15, 7],   // Call to adventure
                    [30, 4],   // Refusal
                    [40, 6],   // Crossing threshold
                    [60, 8],   // Ordeal
                    [75, 10],  // Supreme ordeal
                    [90, 9],   // Return
                    [100, 5],
                ]),
                'best_for' => json_encode(['fantasy', 'adventure', 'epic', 'coming-of-age']),
                'min_scenes' => 8,
                'max_scenes' => 40,
                'sort_order' => 3,
            ],
            [
                'slug' => 'save-the-cat',
                'name' => 'Save the Cat',
                'description' => 'Blake Snyder\'s 15-beat screenwriting structure, popular in Hollywood',
                'structure_type' => 'save_the_cat',
                'act_distribution' => json_encode([
                    'act1' => [
                        'percentage' => 25,
                        'label' => 'Thesis',
                        'beats' => ['hook', 'introduction', 'inciting-incident', 'acceptance'],
                        'description' => 'Opening image through break into two'
                    ],
                    'act2a' => [
                        'percentage' => 25,
                        'label' => 'Antithesis Pt 1',
                        'beats' => ['fun-games', 'ally-enemy', 'first-challenge'],
                        'description' => 'Fun and games, B story, midpoint'
                    ],
                    'act2b' => [
                        'percentage' => 25,
                        'label' => 'Antithesis Pt 2',
                        'beats' => ['midpoint-twist', 'stakes-raised', 'all-is-lost', 'dark-moment'],
                        'description' => 'Bad guys close in through dark night'
                    ],
                    'act3' => [
                        'percentage' => 25,
                        'label' => 'Synthesis',
                        'beats' => ['realization', 'climax', 'final-image'],
                        'description' => 'Break into three through final image'
                    ],
                ]),
                'pacing_curve' => json_encode([
                    [0, 6],
                    [12, 8],   // Catalyst
                    [25, 6],   // Break into two
                    [37, 7],   // Fun and games
                    [50, 9],   // Midpoint
                    [62, 7],   // Bad guys close in
                    [75, 10],  // All is lost
                    [85, 9],   // Dark night / Break into three
                    [95, 10],  // Finale
                    [100, 5],  // Final image
                ]),
                'best_for' => json_encode(['screenplay', 'drama', 'comedy', 'romance', 'action']),
                'min_scenes' => 10,
                'max_scenes' => 30,
                'sort_order' => 4,
            ],
            [
                'slug' => 'documentary-arc',
                'name' => 'Documentary Arc',
                'description' => 'Non-fiction narrative structure for documentaries and explainers',
                'structure_type' => 'documentary',
                'act_distribution' => json_encode([
                    'setup' => [
                        'percentage' => 20,
                        'label' => 'Context',
                        'beats' => ['hook', 'introduction'],
                        'description' => 'Establish the topic and why it matters'
                    ],
                    'exploration' => [
                        'percentage' => 40,
                        'label' => 'Investigation',
                        'beats' => ['exploration', 'first-challenge', 'stakes-raised'],
                        'description' => 'Explore the subject, present evidence'
                    ],
                    'revelation' => [
                        'percentage' => 25,
                        'label' => 'Discovery',
                        'beats' => ['midpoint-twist', 'realization'],
                        'description' => 'Key insights and revelations'
                    ],
                    'conclusion' => [
                        'percentage' => 15,
                        'label' => 'Impact',
                        'beats' => ['resolution', 'final-image'],
                        'description' => 'Conclusions and call to action'
                    ],
                ]),
                'pacing_curve' => json_encode([
                    [0, 7],    // Hook
                    [20, 5],   // Context
                    [40, 6],   // Investigation
                    [65, 8],   // Key revelation
                    [85, 7],   // Building to conclusion
                    [100, 5],  // Resolution
                ]),
                'best_for' => json_encode(['documentary', 'educational', 'explainer', 'corporate']),
                'min_scenes' => 4,
                'max_scenes' => 20,
                'sort_order' => 5,
            ],
            [
                'slug' => 'commercial-structure',
                'name' => 'Commercial Structure',
                'description' => 'Advertising structure optimized for product/service promotion',
                'structure_type' => 'custom',
                'act_distribution' => json_encode([
                    'attention' => [
                        'percentage' => 15,
                        'label' => 'Attention',
                        'beats' => ['hook'],
                        'description' => 'Grab attention, present the problem'
                    ],
                    'interest' => [
                        'percentage' => 30,
                        'label' => 'Interest',
                        'beats' => ['introduction', 'exploration'],
                        'description' => 'Build interest, agitate the problem'
                    ],
                    'desire' => [
                        'percentage' => 35,
                        'label' => 'Desire',
                        'beats' => ['fun-games', 'midpoint-twist'],
                        'description' => 'Present solution, create desire'
                    ],
                    'action' => [
                        'percentage' => 20,
                        'label' => 'Action',
                        'beats' => ['climax', 'resolution'],
                        'description' => 'Call to action, make it easy'
                    ],
                ]),
                'pacing_curve' => json_encode([
                    [0, 8],
                    [15, 6],
                    [45, 7],
                    [80, 9],
                    [100, 7],
                ]),
                'best_for' => json_encode(['commercial', 'advertising', 'promo', 'product']),
                'min_scenes' => 3,
                'max_scenes' => 8,
                'sort_order' => 6,
            ],
        ];

        foreach ($structures as $structure) {
            VwStoryStructure::updateOrCreate(
                ['slug' => $structure['slug']],
                array_merge($structure, ['is_active' => true])
            );
        }
    }
}
