<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AppVideoWizard\Models\VwSeedanceStyle;
use Modules\AppVideoWizard\Database\Seeders\VwSeedanceStyleSeeder;

class SeedanceStyleController extends Controller
{
    public function index(Request $request)
    {
        $query = VwSeedanceStyle::query();

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
                  ->orWhere('prompt_syntax', 'like', "%{$search}%");
            });
        }

        $styles = $query->orderBy('category')->orderBy('sort_order')->paginate(30);

        $stats = [
            'total' => VwSeedanceStyle::count(),
            'active' => VwSeedanceStyle::where('is_active', true)->count(),
            'visual' => VwSeedanceStyle::where('category', 'visual')->where('is_active', true)->count(),
            'lighting' => VwSeedanceStyle::where('category', 'lighting')->where('is_active', true)->count(),
            'color' => VwSeedanceStyle::where('category', 'color')->where('is_active', true)->count(),
        ];

        return view('appvideowizard::admin.cinematography.seedance-styles.index', compact('styles', 'stats'));
    }

    public function create()
    {
        $categories = VwSeedanceStyle::getCategoryOptions();
        return view('appvideowizard::admin.cinematography.seedance-styles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:100|unique:vw_seedance_styles,slug',
            'name' => 'required|string|max:255',
            'category' => 'required|in:visual,lighting,color',
            'description' => 'nullable|string',
            'prompt_syntax' => 'required|string|max:512',
            'compatible_genres' => 'nullable|array',
            'compatible_moods' => 'nullable|array',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = true;
        // Eloquent $casts handles JSON encoding — do NOT json_encode() here

        VwSeedanceStyle::create($validated);

        return redirect()->route('admin.video-wizard.cinematography.seedance-styles')
            ->with('success', 'Seedance style created successfully.');
    }

    public function edit(VwSeedanceStyle $seedanceStyle)
    {
        $categories = VwSeedanceStyle::getCategoryOptions();
        return view('appvideowizard::admin.cinematography.seedance-styles.edit', compact('seedanceStyle', 'categories'));
    }

    public function update(Request $request, VwSeedanceStyle $seedanceStyle)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:visual,lighting,color',
            'description' => 'nullable|string',
            'prompt_syntax' => 'required|string|max:512',
            'compatible_genres' => 'nullable|array',
            'compatible_moods' => 'nullable|array',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Eloquent $casts handles JSON encoding — do NOT json_encode() here

        $seedanceStyle->update($validated);

        return redirect()->route('admin.video-wizard.cinematography.seedance-styles')
            ->with('success', 'Seedance style updated successfully.');
    }

    public function destroy(VwSeedanceStyle $seedanceStyle)
    {
        $seedanceStyle->delete();

        return redirect()->route('admin.video-wizard.cinematography.seedance-styles')
            ->with('success', 'Seedance style deleted.');
    }

    public function toggle(VwSeedanceStyle $seedanceStyle)
    {
        $seedanceStyle->update(['is_active' => !$seedanceStyle->is_active]);

        return back()->with('success', 'Style ' . ($seedanceStyle->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function seedDefaults()
    {
        $seeder = new VwSeedanceStyleSeeder();
        $seeder->run();

        VwSeedanceStyle::clearCache();

        return back()->with('success', 'Default Seedance styles seeded successfully.');
    }
}
