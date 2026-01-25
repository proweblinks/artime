<?php

use Modules\AppVideoWizard\Services\PromptTemplateLibrary;
use Modules\AppVideoWizard\Services\CinematographyVocabulary;

beforeEach(function () {
    $this->library = new PromptTemplateLibrary();
});

describe('PromptTemplateLibrary', function () {

    describe('SHOT_TEMPLATES', function () {

        test('all standard shot types have word budgets', function () {
            $shotTypes = ['close-up', 'medium', 'wide', 'establishing', 'extreme-close-up'];

            foreach ($shotTypes as $shotType) {
                expect(PromptTemplateLibrary::SHOT_TEMPLATES)->toHaveKey($shotType);
                expect(PromptTemplateLibrary::SHOT_TEMPLATES[$shotType])->toHaveKey('word_budget');
            }
        });

        test('all templates have required keys', function () {
            $requiredKeys = ['emphasis', 'default_lens', 'word_budget', 'priority_order'];

            foreach (PromptTemplateLibrary::SHOT_TEMPLATES as $type => $template) {
                foreach ($requiredKeys as $key) {
                    expect($template)->toHaveKey($key, "Shot type '$type' missing key '$key'");
                }
            }
        });

    });

    describe('word budget validation', function () {

        test('all word budgets sum to 100', function () {
            foreach (PromptTemplateLibrary::SHOT_TEMPLATES as $type => $template) {
                $sum = array_sum($template['word_budget']);
                expect($sum)->toBe(100, "Shot type '$type' budget sums to $sum, expected 100");
            }
        });

        test('all word budgets have same sections', function () {
            $expectedSections = ['subject', 'action', 'environment', 'lighting', 'style'];

            foreach (PromptTemplateLibrary::SHOT_TEMPLATES as $type => $template) {
                foreach ($expectedSections as $section) {
                    expect($template['word_budget'])->toHaveKey($section, "Shot type '$type' missing budget for '$section'");
                }
            }
        });

        test('validateWordBudget returns true for valid budget', function () {
            $validBudget = ['subject' => 30, 'action' => 20, 'environment' => 20, 'lighting' => 15, 'style' => 15];

            expect($this->library->validateWordBudget($validBudget))->toBeTrue();
        });

        test('validateWordBudget returns false for invalid budget', function () {
            $invalidBudget = ['subject' => 30, 'action' => 20, 'environment' => 20, 'lighting' => 15, 'style' => 10];

            expect($this->library->validateWordBudget($invalidBudget))->toBeFalse();
        });

    });

    describe('priority orders', function () {

        test('all priority orders contain expected components', function () {
            $expectedComponents = ['subject', 'action', 'environment', 'lighting', 'style'];

            foreach (PromptTemplateLibrary::SHOT_TEMPLATES as $type => $template) {
                foreach ($expectedComponents as $component) {
                    expect($template['priority_order'])->toContain($component, "Shot type '$type' priority missing '$component'");
                }
            }
        });

        test('close-up prioritizes subject first', function () {
            $priority = PromptTemplateLibrary::SHOT_TEMPLATES['close-up']['priority_order'];

            expect($priority[0])->toBe('subject');
        });

        test('establishing prioritizes environment first', function () {
            $priority = PromptTemplateLibrary::SHOT_TEMPLATES['establishing']['priority_order'];

            expect($priority[0])->toBe('environment');
        });

        test('wide prioritizes environment first', function () {
            $priority = PromptTemplateLibrary::SHOT_TEMPLATES['wide']['priority_order'];

            expect($priority[0])->toBe('environment');
        });

    });

    describe('getTemplateForShotType', function () {

        test('returns close-up template with facial emphasis', function () {
            $template = $this->library->getTemplateForShotType('close-up');

            expect($template['emphasis'])->toContain('facial_detail');
            expect($template['default_lens'])->toBe('85mm');
            expect($template['word_budget']['subject'])->toBe(35);
        });

        test('returns wide template with environment emphasis', function () {
            $template = $this->library->getTemplateForShotType('wide');

            expect($template['emphasis'])->toContain('environment');
            expect($template['word_budget']['environment'])->toBe(35);
        });

        test('returns establishing template with location emphasis', function () {
            $template = $this->library->getTemplateForShotType('establishing');

            expect($template['emphasis'])->toContain('location');
            expect($template['word_budget']['environment'])->toBe(45);
        });

        test('defaults to medium for unknown shot type', function () {
            $template = $this->library->getTemplateForShotType('unknown-shot-type');
            $mediumTemplate = $this->library->getTemplateForShotType('medium');

            expect($template['word_budget'])->toBe($mediumTemplate['word_budget']);
        });

        test('includes lens_psychology from vocabulary', function () {
            $template = $this->library->getTemplateForShotType('close-up');

            expect($template)->toHaveKey('lens_psychology');
            expect($template['lens_psychology']['focal_length'])->toBe('85mm');
        });

    });

    describe('getWordBudget', function () {

        test('returns environment at 35% for wide', function () {
            $budget = $this->library->getWordBudget('wide');

            expect($budget['environment'])->toBe(35);
        });

        test('returns subject at 35% for close-up', function () {
            $budget = $this->library->getWordBudget('close-up');

            expect($budget['subject'])->toBe(35);
        });

        test('returns environment at 45% for establishing', function () {
            $budget = $this->library->getWordBudget('establishing');

            expect($budget['environment'])->toBe(45);
        });

    });

    describe('getPriorityOrder', function () {

        test('returns subject first for close-up', function () {
            $priority = $this->library->getPriorityOrder('close-up');

            expect($priority[0])->toBe('subject');
        });

        test('returns environment first for establishing', function () {
            $priority = $this->library->getPriorityOrder('establishing');

            expect($priority[0])->toBe('environment');
        });

        test('returns environment first for wide', function () {
            $priority = $this->library->getPriorityOrder('wide');

            expect($priority[0])->toBe('environment');
        });

        test('returns medium priority for unknown shot type', function () {
            $unknownPriority = $this->library->getPriorityOrder('unknown');
            $mediumPriority = $this->library->getPriorityOrder('medium');

            expect($unknownPriority)->toBe($mediumPriority);
        });

    });

    describe('getShotTypeFromContext', function () {

        test('detects close-up from description', function () {
            $shotType = $this->library->getShotTypeFromContext([
                'description' => 'close up of the character face',
            ]);

            expect($shotType)->toBe('close-up');
        });

        test('detects establishing from description', function () {
            $shotType = $this->library->getShotTypeFromContext([
                'description' => 'establishing shot of the city skyline',
            ]);

            expect($shotType)->toBe('establishing');
        });

        test('infers shot type from focus on eyes', function () {
            $shotType = $this->library->getShotTypeFromContext([
                'description' => 'focus on the eyes',
                'focus' => 'eyes',
            ]);

            expect($shotType)->toBe('extreme-close-up');
        });

        test('returns two-shot for multiple subjects', function () {
            $shotType = $this->library->getShotTypeFromContext([
                'description' => 'two people talking',
                'subject_count' => 2,
            ]);

            expect($shotType)->toBe('two-shot');
        });

        test('defaults to medium for ambiguous context', function () {
            $shotType = $this->library->getShotTypeFromContext([
                'description' => 'a person in a room',
            ]);

            expect($shotType)->toBe('medium');
        });

    });

    describe('calculateWordCounts', function () {

        test('allocates words proportionally', function () {
            $counts = $this->library->calculateWordCounts('close-up', 100);

            expect($counts['subject'])->toBe(35);
            expect($counts['action'])->toBe(20);
            expect($counts['environment'])->toBe(10);
            expect($counts['lighting'])->toBe(18);
            expect($counts['style'])->toBe(17);
        });

        test('distributes remainder to highest priority', function () {
            // 77 words with close-up (35% subject = 26.95, floors to 26)
            // Remainder goes to subject (highest priority)
            $counts = $this->library->calculateWordCounts('close-up', 77);

            $total = array_sum($counts);
            expect($total)->toBe(77);
        });

        test('works with zero words', function () {
            $counts = $this->library->calculateWordCounts('close-up', 0);

            expect(array_sum($counts))->toBe(0);
        });

    });

    describe('getAvailableShotTypes', function () {

        test('returns all shot types', function () {
            $types = $this->library->getAvailableShotTypes();

            expect($types)->toContain('close-up');
            expect($types)->toContain('medium');
            expect($types)->toContain('wide');
            expect($types)->toContain('establishing');
            expect($types)->toContain('extreme-close-up');
        });

    });

    describe('shot type normalization', function () {

        test('handles variations of close-up', function () {
            $template1 = $this->library->getTemplateForShotType('closeup');
            $template2 = $this->library->getTemplateForShotType('close up');
            $template3 = $this->library->getTemplateForShotType('close-up');

            expect($template1['word_budget'])->toBe($template3['word_budget']);
            expect($template2['word_budget'])->toBe($template3['word_budget']);
        });

        test('handles ots abbreviation', function () {
            $template = $this->library->getTemplateForShotType('ots');

            expect($template['emphasis'])->toContain('foreground_subject');
        });

    });

});
