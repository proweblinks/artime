<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AppVideoWizard\Models\VwGenrePreset;

class GenrePresetController extends Controller
{
    /**
     * Display a listing of genre presets.
     */
    public function index(Request $request)
    {
        $query = VwGenrePreset::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('style', 'like', "%{$search}%");
            });
        }

        $presets = $query->orderBy('category')
            ->orderBy('sort_order')
            ->paginate(20);

        $categories = VwGenrePreset::select('category')
            ->distinct()
            ->pluck('category');

        $stats = [
            'total' => VwGenrePreset::count(),
            'active' => VwGenrePreset::where('is_active', true)->count(),
            'categories' => $categories->count(),
        ];

        return view('appvideowizard::admin.cinematography.genre-presets.index', compact(
            'presets',
            'categories',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new genre preset.
     */
    public function create()
    {
        $categories = [
            'documentary' => 'Documentary',
            'cinematic' => 'Cinematic',
            'horror' => 'Horror',
            'comedy' => 'Comedy',
            'social' => 'Social',
            'commercial' => 'Commercial',
            'experimental' => 'Experimental',
            'educational' => 'Educational',
        ];

        return view('appvideowizard::admin.cinematography.genre-presets.create', compact('categories'));
    }

    /**
     * Store a newly created genre preset.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:vw_genre_presets,slug', 'regex:/^[a-z0-9-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['documentary', 'cinematic', 'horror', 'comedy', 'social', 'commercial', 'experimental', 'educational'])],
            'description' => ['nullable', 'string'],
            'camera_language' => ['required', 'string'],
            'color_grade' => ['required', 'string'],
            'lighting' => ['required', 'string'],
            'atmosphere' => ['nullable', 'string'],
            'style' => ['required', 'string'],
            'lens_preferences' => ['nullable', 'array'],
            'prompt_prefix' => ['nullable', 'string'],
            'prompt_suffix' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        // Convert lens preferences to JSON if provided
        if (isset($validated['lens_preferences'])) {
            $validated['lens_preferences'] = json_encode($validated['lens_preferences']);
        }

        // Handle default - only one can be default
        if ($request->boolean('is_default')) {
            VwGenrePreset::where('is_default', true)->update(['is_default' => false]);
        }

        $preset = VwGenrePreset::create($validated);

        session()->flash('success', "Genre preset '{$preset->name}' created successfully.");

        return redirect()->route('admin.video-wizard.cinematography.genre-presets.index');
    }

    /**
     * Show the form for editing a genre preset.
     */
    public function edit(VwGenrePreset $genrePreset)
    {
        $categories = [
            'documentary' => 'Documentary',
            'cinematic' => 'Cinematic',
            'horror' => 'Horror',
            'comedy' => 'Comedy',
            'social' => 'Social',
            'commercial' => 'Commercial',
            'experimental' => 'Experimental',
            'educational' => 'Educational',
        ];

        return view('appvideowizard::admin.cinematography.genre-presets.edit', compact('genrePreset', 'categories'));
    }

    /**
     * Update the specified genre preset.
     */
    public function update(Request $request, VwGenrePreset $genrePreset)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', Rule::unique('vw_genre_presets')->ignore($genrePreset->id), 'regex:/^[a-z0-9-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['documentary', 'cinematic', 'horror', 'comedy', 'social', 'commercial', 'experimental', 'educational'])],
            'description' => ['nullable', 'string'],
            'camera_language' => ['required', 'string'],
            'color_grade' => ['required', 'string'],
            'lighting' => ['required', 'string'],
            'atmosphere' => ['nullable', 'string'],
            'style' => ['required', 'string'],
            'lens_preferences' => ['nullable', 'array'],
            'prompt_prefix' => ['nullable', 'string'],
            'prompt_suffix' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        // Convert lens preferences to JSON if provided
        if (isset($validated['lens_preferences'])) {
            $validated['lens_preferences'] = json_encode($validated['lens_preferences']);
        }

        // Handle default - only one can be default
        if ($request->boolean('is_default') && !$genrePreset->is_default) {
            VwGenrePreset::where('is_default', true)->update(['is_default' => false]);
        }

        $genrePreset->update($validated);

        session()->flash('success', "Genre preset '{$genrePreset->name}' updated successfully.");

        return redirect()->route('admin.video-wizard.cinematography.genre-presets.index');
    }

    /**
     * Remove the specified genre preset.
     */
    public function destroy(VwGenrePreset $genrePreset)
    {
        $name = $genrePreset->name;
        $genrePreset->delete();

        session()->flash('success', "Genre preset '{$name}' deleted successfully.");

        return redirect()->route('admin.video-wizard.cinematography.genre-presets.index');
    }

    /**
     * Toggle active status.
     */
    public function toggle(VwGenrePreset $genrePreset)
    {
        $genrePreset->update(['is_active' => !$genrePreset->is_active]);

        $status = $genrePreset->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Genre preset '{$genrePreset->name}' {$status}.");

        return redirect()->back();
    }

    /**
     * Clone an existing preset.
     */
    public function clone(VwGenrePreset $genrePreset)
    {
        $clone = $genrePreset->replicate();
        $clone->slug = $genrePreset->slug . '-copy-' . time();
        $clone->name = $genrePreset->name . ' (Copy)';
        $clone->is_default = false;
        $clone->save();

        session()->flash('success', "Genre preset cloned as '{$clone->name}'.");

        return redirect()->route('admin.video-wizard.cinematography.genre-presets.edit', $clone);
    }

    /**
     * Reorder presets.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:vw_genre_presets,id'],
        ]);

        foreach ($request->order as $index => $id) {
            VwGenrePreset::where('id', $id)->update(['sort_order' => $index]);
        }

        VwGenrePreset::clearCache();

        return response()->json(['success' => true]);
    }

    /**
     * Export presets as JSON.
     */
    public function export()
    {
        $presets = VwGenrePreset::orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($preset) {
                return [
                    'slug' => $preset->slug,
                    'name' => $preset->name,
                    'category' => $preset->category,
                    'description' => $preset->description,
                    'camera_language' => $preset->camera_language,
                    'color_grade' => $preset->color_grade,
                    'lighting' => $preset->lighting,
                    'atmosphere' => $preset->atmosphere,
                    'style' => $preset->style,
                    'lens_preferences' => $preset->lens_preferences,
                ];
            });

        return response()->json($presets->toArray())
            ->header('Content-Disposition', 'attachment; filename="genre-presets.json"');
    }

    /**
     * Import presets from JSON.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json'],
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $presets = json_decode($content, true);

        if (!is_array($presets)) {
            return back()->withErrors(['file' => 'Invalid JSON format']);
        }

        $imported = 0;
        foreach ($presets as $preset) {
            if (!isset($preset['slug'])) continue;

            VwGenrePreset::updateOrCreate(
                ['slug' => $preset['slug']],
                array_merge($preset, [
                    'is_active' => true,
                    'lens_preferences' => isset($preset['lens_preferences'])
                        ? json_encode($preset['lens_preferences'])
                        : null,
                ])
            );
            $imported++;
        }

        session()->flash('success', "{$imported} genre presets imported successfully.");

        return redirect()->route('admin.video-wizard.cinematography.genre-presets.index');
    }

    /**
     * Preview prompt output for a preset.
     */
    public function preview(Request $request, VwGenrePreset $genrePreset)
    {
        $samplePrompt = $this->generateSamplePrompt($genrePreset);

        return response()->json([
            'preset' => $genrePreset->toArray(),
            'samplePrompt' => $samplePrompt,
        ]);
    }

    /**
     * Generate a sample prompt using this preset.
     */
    protected function generateSamplePrompt(VwGenrePreset $preset): string
    {
        $parts = [];

        // Subject (sample)
        $parts[] = 'A detective walking through a rain-soaked city street at night';

        // Camera language
        $parts[] = $preset->camera_language;

        // Color grade
        $parts[] = $preset->color_grade;

        // Lighting
        $parts[] = $preset->lighting;

        // Atmosphere
        if ($preset->atmosphere) {
            $parts[] = $preset->atmosphere;
        }

        // Style
        $parts[] = $preset->style;

        // Technical
        $parts[] = '4K, cinematic film grain, shallow depth of field';

        return implode('. ', $parts);
    }
}
