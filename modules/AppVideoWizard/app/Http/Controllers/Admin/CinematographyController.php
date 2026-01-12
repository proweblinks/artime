<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwGenrePreset;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Models\VwEmotionalBeat;
use Modules\AppVideoWizard\Models\VwStoryStructure;
use Modules\AppVideoWizard\Models\VwCameraSpec;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Services\ShotContinuityService;

class CinematographyController extends Controller
{
    /**
     * Display cinematography dashboard with all systems overview.
     */
    public function index()
    {
        $stats = [
            'genrePresets' => [
                'total' => VwGenrePreset::count(),
                'active' => VwGenrePreset::where('is_active', true)->count(),
                'categories' => VwGenrePreset::select('category')->distinct()->count(),
            ],
            'shotTypes' => [
                'total' => VwShotType::count(),
                'active' => VwShotType::where('is_active', true)->count(),
                'categories' => VwShotType::select('category')->distinct()->count(),
            ],
            'emotionalBeats' => [
                'total' => VwEmotionalBeat::count(),
                'active' => VwEmotionalBeat::where('is_active', true)->count(),
                'positions' => VwEmotionalBeat::select('story_position')->distinct()->count(),
            ],
            'storyStructures' => [
                'total' => VwStoryStructure::count(),
                'active' => VwStoryStructure::where('is_active', true)->count(),
            ],
            'cameraSpecs' => [
                'total' => VwCameraSpec::count(),
                'active' => VwCameraSpec::where('is_active', true)->count(),
                'lenses' => VwCameraSpec::where('category', 'lens')->count(),
                'filmStocks' => VwCameraSpec::where('category', 'film_stock')->count(),
            ],
            'cameraMovements' => [
                'total' => VwCameraMovement::count(),
                'active' => VwCameraMovement::where('is_active', true)->count(),
                'categories' => VwCameraMovement::select('category')->distinct()->count(),
            ],
        ];

        // Get sample data for preview cards
        $samples = [
            'genre' => VwGenrePreset::where('is_active', true)->where('is_default', true)->first()
                ?? VwGenrePreset::where('is_active', true)->first(),
            'shot' => VwShotType::where('is_active', true)->inRandomOrder()->first(),
            'beat' => VwEmotionalBeat::where('is_active', true)->where('story_position', 'act3_climax')->first(),
        ];

        return view('appvideowizard::admin.cinematography.index', compact('stats', 'samples'));
    }

    // =====================================
    // SHOT TYPES
    // =====================================

    /**
     * Display shot types listing.
     */
    public function shotTypes(Request $request)
    {
        $query = VwShotType::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
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

        $shotTypes = $query->orderBy('category')
            ->orderBy('sort_order')
            ->paginate(25);

        $categories = [
            'framing' => 'Framing (Shot Size)',
            'angle' => 'Camera Angle',
            'movement' => 'Camera Movement',
            'focus' => 'Focus Techniques',
            'special' => 'Special Shots',
        ];

        $stats = [
            'total' => VwShotType::count(),
            'active' => VwShotType::where('is_active', true)->count(),
        ];

        return view('appvideowizard::admin.cinematography.shot-types.index', compact(
            'shotTypes',
            'categories',
            'stats'
        ));
    }

    /**
     * Toggle shot type active status.
     */
    public function toggleShotType(VwShotType $shotType)
    {
        $shotType->update(['is_active' => !$shotType->is_active]);

        $status = $shotType->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Shot type '{$shotType->name}' {$status}.");

        return redirect()->back();
    }

    /**
     * Edit shot type.
     */
    public function editShotType(VwShotType $shotType)
    {
        $categories = [
            'framing' => 'Framing (Shot Size)',
            'angle' => 'Camera Angle',
            'movement' => 'Camera Movement',
            'focus' => 'Focus Techniques',
            'special' => 'Special Shots',
        ];

        $emotionalBeats = VwEmotionalBeat::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'slug');

        // Get camera movements for Motion Intelligence section
        $cameraMovements = VwCameraMovement::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->pluck('name', 'slug');

        return view('appvideowizard::admin.cinematography.shot-types.edit', compact(
            'shotType',
            'categories',
            'emotionalBeats',
            'cameraMovements'
        ));
    }

    /**
     * Update shot type.
     */
    public function updateShotType(Request $request, VwShotType $shotType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'camera_specs' => ['nullable', 'string'],
            'default_lens' => ['nullable', 'string', 'max:100'],
            'default_aperture' => ['nullable', 'string', 'max:20'],
            'typical_duration_min' => ['integer', 'min:1', 'max:60'],
            'typical_duration_max' => ['integer', 'min:1', 'max:60'],
            'emotional_beats' => ['nullable', 'array'],
            'best_for_genres' => ['nullable', 'array'],
            'prompt_template' => ['nullable', 'string'],
            'motion_description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            // Motion Intelligence fields (Phase 2)
            'primary_movement' => ['nullable', 'string', 'max:100'],
            'movement_intensity' => ['nullable', 'in:static,subtle,moderate,dynamic,intense'],
            'stackable_movements' => ['nullable', 'array'],
            'typical_ending' => ['nullable', 'string', 'max:255'],
            'video_prompt_template' => ['nullable', 'string'],
        ]);

        // Convert arrays to JSON
        if (isset($validated['emotional_beats'])) {
            $validated['emotional_beats'] = json_encode($validated['emotional_beats']);
        }
        if (isset($validated['best_for_genres'])) {
            $validated['best_for_genres'] = json_encode($validated['best_for_genres']);
        }
        if (isset($validated['stackable_movements'])) {
            $validated['stackable_movements'] = json_encode($validated['stackable_movements']);
        }

        $shotType->update($validated);

        session()->flash('success', "Shot type '{$shotType->name}' updated successfully.");

        return redirect()->route('admin.video-wizard.cinematography.shot-types');
    }

    // =====================================
    // EMOTIONAL BEATS
    // =====================================

    /**
     * Display emotional beats listing.
     */
    public function emotionalBeats(Request $request)
    {
        $query = VwEmotionalBeat::query();

        if ($request->filled('position')) {
            $query->where('story_position', $request->position);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $beats = $query->orderBy('sort_order')->paginate(30);

        $positions = [
            'act1_setup' => 'Act 1: Setup',
            'act1_catalyst' => 'Act 1: Catalyst',
            'act2_rising' => 'Act 2: Rising Action',
            'act2_midpoint' => 'Act 2: Midpoint',
            'act2_crisis' => 'Act 2: Crisis',
            'act3_climax' => 'Act 3: Climax',
            'act3_resolution' => 'Act 3: Resolution',
            'standalone' => 'Standalone',
        ];

        // Group beats by act for visual display
        $beatsByAct = VwEmotionalBeat::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy(function ($beat) {
                return explode('_', $beat->story_position)[0] ?? 'standalone';
            });

        $stats = [
            'total' => VwEmotionalBeat::count(),
            'active' => VwEmotionalBeat::where('is_active', true)->count(),
        ];

        return view('appvideowizard::admin.cinematography.emotional-beats.index', compact(
            'beats',
            'positions',
            'beatsByAct',
            'stats'
        ));
    }

    /**
     * Toggle emotional beat active status.
     */
    public function toggleEmotionalBeat(VwEmotionalBeat $emotionalBeat)
    {
        $emotionalBeat->update(['is_active' => !$emotionalBeat->is_active]);

        $status = $emotionalBeat->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Emotional beat '{$emotionalBeat->name}' {$status}.");

        return redirect()->back();
    }

    // =====================================
    // STORY STRUCTURES
    // =====================================

    /**
     * Display story structures listing.
     */
    public function storyStructures()
    {
        $structures = VwStoryStructure::orderBy('sort_order')->get();

        $stats = [
            'total' => VwStoryStructure::count(),
            'active' => VwStoryStructure::where('is_active', true)->count(),
        ];

        return view('appvideowizard::admin.cinematography.story-structures.index', compact(
            'structures',
            'stats'
        ));
    }

    /**
     * Toggle story structure active status.
     */
    public function toggleStoryStructure(VwStoryStructure $storyStructure)
    {
        $storyStructure->update(['is_active' => !$storyStructure->is_active]);

        $status = $storyStructure->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Story structure '{$storyStructure->name}' {$status}.");

        return redirect()->back();
    }

    /**
     * Set default story structure.
     */
    public function setDefaultStructure(VwStoryStructure $storyStructure)
    {
        VwStoryStructure::where('is_default', true)->update(['is_default' => false]);
        $storyStructure->update(['is_default' => true]);

        session()->flash('success', "'{$storyStructure->name}' set as default story structure.");

        return redirect()->back();
    }

    // =====================================
    // CAMERA SPECS
    // =====================================

    /**
     * Display camera specs listing.
     */
    public function cameraSpecs(Request $request)
    {
        $query = VwCameraSpec::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $specs = $query->orderBy('category')
            ->orderBy('sort_order')
            ->paginate(25);

        $categories = [
            'lens' => 'Lenses',
            'camera_body' => 'Camera Bodies',
            'film_stock' => 'Film Stocks / Looks',
            'format' => 'Formats',
        ];

        $stats = [
            'total' => VwCameraSpec::count(),
            'active' => VwCameraSpec::where('is_active', true)->count(),
            'lenses' => VwCameraSpec::where('category', 'lens')->count(),
            'filmStocks' => VwCameraSpec::where('category', 'film_stock')->count(),
        ];

        return view('appvideowizard::admin.cinematography.camera-specs.index', compact(
            'specs',
            'categories',
            'stats'
        ));
    }

    /**
     * Toggle camera spec active status.
     */
    public function toggleCameraSpec(VwCameraSpec $cameraSpec)
    {
        $cameraSpec->update(['is_active' => !$cameraSpec->is_active]);

        $status = $cameraSpec->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Camera spec '{$cameraSpec->name}' {$status}.");

        return redirect()->back();
    }

    // =====================================
    // BULK OPERATIONS
    // =====================================

    /**
     * Clear all cinematography caches.
     */
    public function clearCaches()
    {
        VwGenrePreset::clearCache();
        VwShotType::clearCache();
        VwEmotionalBeat::clearCache();
        VwStoryStructure::clearCache();
        VwCameraSpec::clearCache();
        VwCameraMovement::clearCache();

        session()->flash('success', 'All cinematography caches cleared successfully.');

        return redirect()->back();
    }

    /**
     * Export all cinematography data as JSON.
     */
    public function exportAll()
    {
        $data = [
            'genrePresets' => VwGenrePreset::all()->toArray(),
            'shotTypes' => VwShotType::all()->toArray(),
            'emotionalBeats' => VwEmotionalBeat::all()->toArray(),
            'storyStructures' => VwStoryStructure::all()->toArray(),
            'cameraSpecs' => VwCameraSpec::all()->toArray(),
            'cameraMovements' => VwCameraMovement::all()->toArray(),
            'exportedAt' => now()->toISOString(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="cinematography-export-' . date('Y-m-d') . '.json"');
    }

    // =====================================
    // SHOT CONTINUITY (Phase 3)
    // =====================================

    /**
     * Display shot continuity rules and patterns.
     */
    public function continuity()
    {
        // Get continuity settings
        $enabled = (bool) VwSetting::getValue('shot_continuity_enabled', true);
        $minScore = (int) VwSetting::getValue('shot_continuity_min_score', 60);

        // Get rules status
        $rules = [
            '30_degree' => (bool) VwSetting::getValue('shot_continuity_30_degree_rule', true),
            'jump_cut' => (bool) VwSetting::getValue('shot_continuity_jump_cut_detection', true),
            'movement_flow' => (bool) VwSetting::getValue('shot_continuity_movement_flow', true),
            'coverage_patterns' => (bool) VwSetting::getValue('shot_continuity_coverage_patterns', true),
            'auto_optimize' => (bool) VwSetting::getValue('shot_continuity_auto_optimize', false),
        ];

        // Get static data from service constants
        $coveragePatterns = ShotContinuityService::COVERAGE_PATTERNS;
        $shotCompatibility = ShotContinuityService::SHOT_COMPATIBILITY;
        $movementContinuity = ShotContinuityService::MOVEMENT_CONTINUITY;

        return view('appvideowizard::admin.cinematography.continuity.index', compact(
            'enabled',
            'minScore',
            'rules',
            'coveragePatterns',
            'shotCompatibility',
            'movementContinuity'
        ));
    }
}
