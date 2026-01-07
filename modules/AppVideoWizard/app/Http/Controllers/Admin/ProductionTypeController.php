<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwProductionType;

class ProductionTypeController extends Controller
{
    /**
     * Display list of production types.
     */
    public function index()
    {
        $types = VwProductionType::whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->get();

        return view('appvideowizard::admin.production-types.index', compact('types'));
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        $parentTypes = VwProductionType::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $parentId = $request->get('parent_id');

        return view('appvideowizard::admin.production-types.create', compact('parentTypes', 'parentId'));
    }

    /**
     * Store new production type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:vw_production_types,id',
            'slug' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'characteristics' => 'nullable|array',
            'default_narration' => 'nullable|string|max:50',
            'suggested_duration_min' => 'nullable|integer|min:1',
            'suggested_duration_max' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Set sort order
        $maxOrder = VwProductionType::where('parent_id', $validated['parent_id'] ?? null)
            ->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        VwProductionType::create($validated);

        session()->flash('success', 'Production type created successfully.');

        return redirect()->route('admin.video-wizard.production-types.index');
    }

    /**
     * Show edit form.
     */
    public function edit(VwProductionType $productionType)
    {
        $parentTypes = VwProductionType::whereNull('parent_id')
            ->where('id', '!=', $productionType->id)
            ->orderBy('sort_order')
            ->get();

        return view('appvideowizard::admin.production-types.edit', compact('productionType', 'parentTypes'));
    }

    /**
     * Update production type.
     */
    public function update(Request $request, VwProductionType $productionType)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:vw_production_types,id',
            'slug' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'characteristics' => 'nullable|array',
            'default_narration' => 'nullable|string|max:50',
            'suggested_duration_min' => 'nullable|integer|min:1',
            'suggested_duration_max' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Prevent setting self as parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $productionType->id) {
            $validated['parent_id'] = null;
        }

        $productionType->update($validated);

        session()->flash('success', 'Production type updated successfully.');

        return redirect()->route('admin.video-wizard.production-types.index');
    }

    /**
     * Delete production type.
     */
    public function destroy(VwProductionType $productionType)
    {
        // Delete will cascade to children due to foreign key
        $productionType->delete();

        session()->flash('success', 'Production type deleted successfully.');

        return redirect()->route('admin.video-wizard.production-types.index');
    }

    /**
     * Toggle active status.
     */
    public function toggle(VwProductionType $productionType)
    {
        $productionType->update(['is_active' => !$productionType->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $productionType->is_active,
        ]);
    }

    /**
     * Reorder production types.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:vw_production_types,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            VwProductionType::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        VwProductionType::clearCache();

        return response()->json(['success' => true]);
    }

    /**
     * Seed default production types from config.
     */
    public function seedDefaults()
    {
        $config = config('appvideowizard.production_types', []);

        $sortOrder = 0;
        foreach ($config as $slug => $type) {
            $sortOrder++;

            $parent = VwProductionType::updateOrCreate(
                ['slug' => $slug, 'parent_id' => null],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'] ?? null,
                    'description' => $type['description'] ?? null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]
            );

            if (!empty($type['subTypes'])) {
                $subSortOrder = 0;
                foreach ($type['subTypes'] as $subSlug => $subType) {
                    $subSortOrder++;

                    VwProductionType::updateOrCreate(
                        ['slug' => $subSlug, 'parent_id' => $parent->id],
                        [
                            'name' => $subType['name'],
                            'icon' => $subType['icon'] ?? null,
                            'description' => $subType['description'] ?? null,
                            'characteristics' => $subType['characteristics'] ?? null,
                            'default_narration' => $subType['defaultNarration'] ?? null,
                            'suggested_duration_min' => $subType['suggestedDuration']['min'] ?? null,
                            'suggested_duration_max' => $subType['suggestedDuration']['max'] ?? null,
                            'sort_order' => $subSortOrder,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        session()->flash('success', 'Default production types seeded successfully.');

        return redirect()->route('admin.video-wizard.production-types.index');
    }
}
