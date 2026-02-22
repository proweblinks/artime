<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwShotType;
use Modules\AppVideoWizard\Services\CameraMovementService;

/**
 * Camera Movement Admin Controller
 *
 * Manages camera movements for the Motion Intelligence system.
 */
class CameraMovementController extends Controller
{
    protected CameraMovementService $cameraMovementService;

    public function __construct(CameraMovementService $cameraMovementService)
    {
        $this->cameraMovementService = $cameraMovementService;
    }

    /**
     * Display camera movements listing.
     */
    public function index(Request $request)
    {
        $query = VwCameraMovement::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('intensity')) {
            $query->where('intensity', $request->intensity);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('prompt_syntax', 'like', "%{$search}%");
            });
        }

        $movements = $query->orderBy('category')
            ->orderBy('sort_order')
            ->paginate(25);

        $categories = VwCameraMovement::getCategoryOptions();
        $intensities = VwCameraMovement::getIntensityOptions();

        $stats = [
            'total' => VwCameraMovement::count(),
            'active' => VwCameraMovement::where('is_active', true)->count(),
            'byCategory' => VwCameraMovement::where('is_active', true)
                ->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
        ];

        return view('appvideowizard::admin.cinematography.camera-movements.index', compact(
            'movements',
            'categories',
            'intensities',
            'stats'
        ));
    }

    /**
     * Show create movement form.
     */
    public function create()
    {
        $categories = VwCameraMovement::getCategoryOptions();
        $intensities = VwCameraMovement::getIntensityOptions();
        $allMovements = VwCameraMovement::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'slug');
        $shotTypes = VwShotType::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'slug');

        return view('appvideowizard::admin.cinematography.camera-movements.create', compact(
            'categories',
            'intensities',
            'allMovements',
            'shotTypes'
        ));
    }

    /**
     * Store new movement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:vw_camera_movements,slug'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:zoom,dolly,crane,pan_tilt,arc,specialty'],
            'description' => ['nullable', 'string'],
            'prompt_syntax' => ['required', 'string', 'max:255'],
            'intensity' => ['required', 'in:subtle,moderate,dynamic,intense'],
            'typical_duration_min' => ['integer', 'min:1', 'max:60'],
            'typical_duration_max' => ['integer', 'min:1', 'max:60'],
            'stackable_with' => ['nullable', 'array'],
            'best_for_shot_types' => ['nullable', 'array'],
            'best_for_emotions' => ['nullable', 'array'],
            'natural_continuation' => ['nullable', 'string', 'max:100'],
            'ending_state' => ['nullable', 'string', 'max:255'],
            'seedance_compatible' => ['boolean'],
            'seedance_prompt_syntax' => ['nullable', 'string', 'max:512'],
            'seedance_shot_size' => ['nullable', 'in:wide,medium,close'],
            'is_active' => ['boolean'],
        ]);

        // Convert arrays to JSON
        $validated['stackable_with'] = isset($validated['stackable_with'])
            ? json_encode($validated['stackable_with']) : null;
        $validated['best_for_shot_types'] = isset($validated['best_for_shot_types'])
            ? json_encode($validated['best_for_shot_types']) : null;
        $validated['best_for_emotions'] = isset($validated['best_for_emotions'])
            ? json_encode($validated['best_for_emotions']) : null;
        $validated['seedance_compatible'] = $request->boolean('seedance_compatible');

        $movement = VwCameraMovement::create($validated);

        session()->flash('success', "Camera movement '{$movement->name}' created successfully.");

        return redirect()->route('admin.video-wizard.cinematography.camera-movements');
    }

    /**
     * Show edit movement form.
     */
    public function edit(VwCameraMovement $cameraMovement)
    {
        $categories = VwCameraMovement::getCategoryOptions();
        $intensities = VwCameraMovement::getIntensityOptions();
        $allMovements = VwCameraMovement::where('is_active', true)
            ->where('id', '!=', $cameraMovement->id)
            ->orderBy('name')
            ->pluck('name', 'slug');
        $shotTypes = VwShotType::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'slug');

        // Common emotions for selection
        $emotions = [
            'tension' => 'Tension',
            'reveal' => 'Reveal',
            'intimacy' => 'Intimacy',
            'drama' => 'Drama',
            'focus' => 'Focus',
            'energy' => 'Energy',
            'transition' => 'Transition',
            'epic' => 'Epic',
            'realization' => 'Realization',
            'isolation' => 'Isolation',
            'journey' => 'Journey',
            'power' => 'Power',
            'scale' => 'Scale',
            'urgency' => 'Urgency',
            'contemplation' => 'Contemplation',
            'shock' => 'Shock',
            'discovery' => 'Discovery',
            'connection' => 'Connection',
        ];

        return view('appvideowizard::admin.cinematography.camera-movements.edit', compact(
            'cameraMovement',
            'categories',
            'intensities',
            'allMovements',
            'shotTypes',
            'emotions'
        ));
    }

    /**
     * Update movement.
     */
    public function update(Request $request, VwCameraMovement $cameraMovement)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:zoom,dolly,crane,pan_tilt,arc,specialty'],
            'description' => ['nullable', 'string'],
            'prompt_syntax' => ['required', 'string', 'max:255'],
            'intensity' => ['required', 'in:subtle,moderate,dynamic,intense'],
            'typical_duration_min' => ['integer', 'min:1', 'max:60'],
            'typical_duration_max' => ['integer', 'min:1', 'max:60'],
            'stackable_with' => ['nullable', 'array'],
            'best_for_shot_types' => ['nullable', 'array'],
            'best_for_emotions' => ['nullable', 'array'],
            'natural_continuation' => ['nullable', 'string', 'max:100'],
            'ending_state' => ['nullable', 'string', 'max:255'],
            'seedance_compatible' => ['boolean'],
            'seedance_prompt_syntax' => ['nullable', 'string', 'max:512'],
            'seedance_shot_size' => ['nullable', 'in:wide,medium,close'],
            'is_active' => ['boolean'],
        ]);

        // Convert arrays to JSON
        $validated['stackable_with'] = isset($validated['stackable_with'])
            ? json_encode($validated['stackable_with']) : null;
        $validated['best_for_shot_types'] = isset($validated['best_for_shot_types'])
            ? json_encode($validated['best_for_shot_types']) : null;
        $validated['best_for_emotions'] = isset($validated['best_for_emotions'])
            ? json_encode($validated['best_for_emotions']) : null;
        $validated['seedance_compatible'] = $request->boolean('seedance_compatible');

        $cameraMovement->update($validated);

        session()->flash('success', "Camera movement '{$cameraMovement->name}' updated successfully.");

        return redirect()->route('admin.video-wizard.cinematography.camera-movements');
    }

    /**
     * Delete movement.
     */
    public function destroy(VwCameraMovement $cameraMovement)
    {
        $name = $cameraMovement->name;
        $cameraMovement->delete();

        session()->flash('success', "Camera movement '{$name}' deleted successfully.");

        return redirect()->route('admin.video-wizard.cinematography.camera-movements');
    }

    /**
     * Toggle movement active status.
     */
    public function toggle(VwCameraMovement $cameraMovement)
    {
        $cameraMovement->update(['is_active' => !$cameraMovement->is_active]);

        $status = $cameraMovement->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Camera movement '{$cameraMovement->name}' {$status}.");

        return redirect()->back();
    }

    /**
     * Test movement prompt generation.
     */
    public function testPrompt(Request $request, VwCameraMovement $cameraMovement)
    {
        $secondarySlug = $request->input('secondary_movement');
        $intensity = $request->input('intensity', $cameraMovement->intensity);

        $result = $this->cameraMovementService->buildMovementPrompt(
            $cameraMovement->slug,
            $secondarySlug,
            $intensity
        );

        return response()->json([
            'success' => true,
            'prompt' => $result['prompt'],
            'stacked' => $result['stacked'],
            'intensity' => $result['intensity'],
            'endingState' => $result['endingState'],
        ]);
    }

    /**
     * Get stackable movements for a primary movement (AJAX).
     */
    public function getStackable(VwCameraMovement $cameraMovement)
    {
        $stackable = $cameraMovement->getStackableMovements();

        return response()->json([
            'success' => true,
            'stackable' => $stackable,
        ]);
    }

    /**
     * Reorder movements.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'movements' => ['required', 'array'],
            'movements.*.id' => ['required', 'exists:vw_camera_movements,id'],
            'movements.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['movements'] as $item) {
            VwCameraMovement::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        VwCameraMovement::clearCache();

        return response()->json(['success' => true]);
    }

    /**
     * Export movements as JSON.
     */
    public function export()
    {
        $data = [
            'cameraMovements' => VwCameraMovement::orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->toArray(),
            'exportedAt' => now()->toISOString(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="camera-movements-export-' . date('Y-m-d') . '.json"');
    }

    /**
     * Import movements from JSON.
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:json', 'max:2048'],
        ]);

        try {
            $content = $request->file('file')->get();
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            session()->flash('error', 'Invalid JSON format: ' . $e->getMessage());
            return redirect()->back();
        }

        if (!isset($data['cameraMovements']) || !is_array($data['cameraMovements'])) {
            session()->flash('error', 'Invalid import file format. Expected "cameraMovements" array.');
            return redirect()->back();
        }

        // Whitelist of allowed fields for import
        $allowedFields = [
            'slug', 'name', 'category', 'description', 'prompt_syntax',
            'intensity', 'typical_duration_min', 'typical_duration_max',
            'stackable_with', 'best_for_shot_types', 'best_for_emotions',
            'natural_continuation', 'ending_state', 'is_active', 'sort_order',
        ];

        $allowedCategories = ['zoom', 'dolly', 'crane', 'pan_tilt', 'arc', 'specialty'];
        $allowedIntensities = ['subtle', 'moderate', 'dynamic', 'intense'];

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($data['cameraMovements'] as $movement) {
            // Validate required fields
            if (!isset($movement['slug']) || !is_string($movement['slug'])) {
                $skipped++;
                continue;
            }

            // Filter to only allowed fields
            $filteredData = array_intersect_key($movement, array_flip($allowedFields));

            // Validate category if present
            if (isset($filteredData['category']) && !in_array($filteredData['category'], $allowedCategories)) {
                $skipped++;
                continue;
            }

            // Validate intensity if present
            if (isset($filteredData['intensity']) && !in_array($filteredData['intensity'], $allowedIntensities)) {
                $skipped++;
                continue;
            }

            // Ensure JSON fields are properly encoded
            foreach (['stackable_with', 'best_for_shot_types', 'best_for_emotions'] as $jsonField) {
                if (isset($filteredData[$jsonField]) && is_array($filteredData[$jsonField])) {
                    $filteredData[$jsonField] = json_encode($filteredData[$jsonField]);
                }
            }

            $existing = VwCameraMovement::where('slug', $filteredData['slug'])->first();

            if ($existing) {
                unset($filteredData['slug']); // Don't update the slug
                $existing->update($filteredData);
                $updated++;
            } else {
                VwCameraMovement::create($filteredData);
                $imported++;
            }
        }

        VwCameraMovement::clearCache();

        $message = "Imported {$imported} new movements, updated {$updated} existing.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} invalid entries.";
        }
        session()->flash('success', $message);

        return redirect()->route('admin.video-wizard.cinematography.camera-movements');
    }

    /**
     * Seed default movements.
     */
    public function seedDefaults()
    {
        $seeder = new \Modules\AppVideoWizard\Database\Seeders\VwCameraMovementSeeder();
        $seeder->run();

        VwCameraMovement::clearCache();

        session()->flash('success', 'Default camera movements seeded successfully.');

        return redirect()->route('admin.video-wizard.cinematography.camera-movements');
    }
}
