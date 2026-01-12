<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwCoveragePattern;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Database\Seeders\VwCoveragePatternSeeder;

class CoveragePatternController extends Controller
{
    /**
     * Display listing of coverage patterns.
     */
    public function index(Request $request)
    {
        $query = VwCoveragePattern::query();

        // Filters
        if ($request->filled('scene_type')) {
            $query->where('scene_type', $request->scene_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $patterns = $query->orderBy('scene_type')
            ->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        // Stats
        $stats = [
            'total' => VwCoveragePattern::count(),
            'active' => VwCoveragePattern::where('is_active', true)->count(),
            'byType' => VwCoveragePattern::selectRaw('scene_type, count(*) as count')
                ->groupBy('scene_type')
                ->pluck('count', 'scene_type')
                ->toArray(),
        ];

        // Scene types for filter
        $sceneTypes = VwCoveragePattern::getSceneTypes();

        return view('appvideowizard::admin.cinematography.coverage-patterns.index', compact(
            'patterns',
            'stats',
            'sceneTypes'
        ));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $sceneTypes = VwCoveragePattern::getSceneTypes();
        $pacingOptions = VwCoveragePattern::getPacingOptions();
        $shotTypes = VwShotType::where('is_active', true)->orderBy('name')->pluck('name', 'slug')->toArray();
        $intensities = [
            'subtle' => 'Subtle',
            'moderate' => 'Moderate',
            'dynamic' => 'Dynamic',
            'intense' => 'Intense',
        ];

        return view('appvideowizard::admin.cinematography.coverage-patterns.create', compact(
            'sceneTypes',
            'pacingOptions',
            'shotTypes',
            'intensities'
        ));
    }

    /**
     * Store new pattern.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:50|unique:vw_coverage_patterns,slug',
            'name' => 'required|string|max:100',
            'scene_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'shot_sequence' => 'required|array|min:1',
            'detection_keywords' => 'nullable|array',
            'negative_keywords' => 'nullable|array',
            'recommended_pacing' => 'required|string|max:30',
            'min_shots' => 'required|integer|min:1|max:30',
            'max_shots' => 'required|integer|min:1|max:30',
            'typical_shot_duration' => 'required|integer|min:1|max:60',
            'default_movement_intensity' => 'required|string|max:20',
            'preferred_movements' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_system'] = false;

        VwCoveragePattern::create($validated);

        return redirect()
            ->route('admin.video-wizard.cinematography.coverage-patterns.index')
            ->with('success', 'Coverage pattern created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(VwCoveragePattern $coveragePattern)
    {
        $sceneTypes = VwCoveragePattern::getSceneTypes();
        $pacingOptions = VwCoveragePattern::getPacingOptions();
        $shotTypes = VwShotType::where('is_active', true)->orderBy('name')->pluck('name', 'slug')->toArray();
        $intensities = [
            'subtle' => 'Subtle',
            'moderate' => 'Moderate',
            'dynamic' => 'Dynamic',
            'intense' => 'Intense',
        ];

        return view('appvideowizard::admin.cinematography.coverage-patterns.edit', compact(
            'coveragePattern',
            'sceneTypes',
            'pacingOptions',
            'shotTypes',
            'intensities'
        ));
    }

    /**
     * Update pattern.
     */
    public function update(Request $request, VwCoveragePattern $coveragePattern)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'scene_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'shot_sequence' => 'required|array|min:1',
            'detection_keywords' => 'nullable|array',
            'negative_keywords' => 'nullable|array',
            'recommended_pacing' => 'required|string|max:30',
            'min_shots' => 'required|integer|min:1|max:30',
            'max_shots' => 'required|integer|min:1|max:30',
            'typical_shot_duration' => 'required|integer|min:1|max:60',
            'default_movement_intensity' => 'required|string|max:20',
            'preferred_movements' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $coveragePattern->update($validated);

        return redirect()
            ->route('admin.video-wizard.cinematography.coverage-patterns.index')
            ->with('success', 'Coverage pattern updated successfully.');
    }

    /**
     * Delete pattern.
     */
    public function destroy(VwCoveragePattern $coveragePattern)
    {
        if ($coveragePattern->is_system) {
            return redirect()
                ->back()
                ->with('error', 'System patterns cannot be deleted.');
        }

        $coveragePattern->delete();

        return redirect()
            ->back()
            ->with('success', 'Coverage pattern deleted successfully.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(VwCoveragePattern $coveragePattern)
    {
        $coveragePattern->update(['is_active' => !$coveragePattern->is_active]);

        return redirect()
            ->back()
            ->with('success', 'Pattern status updated.');
    }

    /**
     * Export patterns as JSON.
     */
    public function export()
    {
        $patterns = VwCoveragePattern::orderBy('scene_type')
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();

        return response()->json($patterns)
            ->header('Content-Disposition', 'attachment; filename="coverage-patterns-' . date('Y-m-d') . '.json"');
    }

    /**
     * Seed default patterns.
     */
    public function seedDefaults()
    {
        $seeder = new VwCoveragePatternSeeder();
        $seeder->run();

        return redirect()
            ->back()
            ->with('success', 'Default coverage patterns seeded successfully.');
    }

    /**
     * Test pattern detection.
     */
    public function testDetection(Request $request)
    {
        $text = $request->input('text', '');

        if (empty($text)) {
            return response()->json(['error' => 'No text provided'], 400);
        }

        $patterns = VwCoveragePattern::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        $results = [];
        foreach ($patterns as $pattern) {
            $score = $pattern->matchKeywords($text);
            if ($score > 0) {
                $results[] = [
                    'slug' => $pattern->slug,
                    'name' => $pattern->name,
                    'sceneType' => $pattern->scene_type,
                    'score' => $score,
                    'shotSequence' => $pattern->shot_sequence,
                ];
            }
        }

        // Sort by score
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return response()->json([
            'success' => true,
            'input' => $text,
            'results' => array_slice($results, 0, 5),
            'bestMatch' => $results[0] ?? null,
        ]);
    }
}
