<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NarrativeStructureController extends Controller
{
    /**
     * Display narrative structures overview.
     */
    public function index()
    {
        // Load all narrative structure configs
        $storyArcs = config('appvideowizard.story_arcs', []);
        $narrativePresets = config('appvideowizard.narrative_presets', []);
        $tensionCurves = config('appvideowizard.tension_curves', []);
        $emotionalJourneys = config('appvideowizard.emotional_journeys', []);

        // Get enabled/disabled status from settings
        $settings = $this->getSettings();

        // Calculate stats
        $stats = [
            'total_arcs' => count($storyArcs),
            'total_presets' => count($narrativePresets),
            'total_curves' => count($tensionCurves),
            'total_journeys' => count($emotionalJourneys),
            'enabled_arcs' => count(array_filter($storyArcs, fn($k) => !in_array($k, $settings['disabled_arcs'] ?? []), ARRAY_FILTER_USE_KEY)),
            'enabled_presets' => count(array_filter($narrativePresets, fn($k) => !in_array($k, $settings['disabled_presets'] ?? []), ARRAY_FILTER_USE_KEY)),
        ];

        return view('appvideowizard::admin.narrative-structures.index', compact(
            'storyArcs',
            'narrativePresets',
            'tensionCurves',
            'emotionalJourneys',
            'settings',
            'stats'
        ));
    }

    /**
     * Show story arcs management.
     */
    public function storyArcs()
    {
        $storyArcs = config('appvideowizard.story_arcs', []);
        $settings = $this->getSettings();

        return view('appvideowizard::admin.narrative-structures.story-arcs', compact('storyArcs', 'settings'));
    }

    /**
     * Show narrative presets management.
     */
    public function presets()
    {
        $narrativePresets = config('appvideowizard.narrative_presets', []);
        $storyArcs = config('appvideowizard.story_arcs', []);
        $tensionCurves = config('appvideowizard.tension_curves', []);
        $emotionalJourneys = config('appvideowizard.emotional_journeys', []);
        $settings = $this->getSettings();

        return view('appvideowizard::admin.narrative-structures.presets', compact(
            'narrativePresets',
            'storyArcs',
            'tensionCurves',
            'emotionalJourneys',
            'settings'
        ));
    }

    /**
     * Show tension curves management.
     */
    public function tensionCurves()
    {
        $tensionCurves = config('appvideowizard.tension_curves', []);
        $settings = $this->getSettings();

        return view('appvideowizard::admin.narrative-structures.tension-curves', compact('tensionCurves', 'settings'));
    }

    /**
     * Show emotional journeys management.
     */
    public function emotionalJourneys()
    {
        $emotionalJourneys = config('appvideowizard.emotional_journeys', []);
        $settings = $this->getSettings();

        return view('appvideowizard::admin.narrative-structures.emotional-journeys', compact('emotionalJourneys', 'settings'));
    }

    /**
     * Update settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'default_preset' => 'nullable|string|max:100',
            'default_arc' => 'nullable|string|max:100',
            'default_curve' => 'nullable|string|max:100',
            'default_journey' => 'nullable|string|max:100',
            'disabled_arcs' => 'nullable|array',
            'disabled_presets' => 'nullable|array',
            'disabled_curves' => 'nullable|array',
            'disabled_journeys' => 'nullable|array',
            'show_advanced_by_default' => 'boolean',
        ]);

        $settings = $this->getSettings();
        $settings = array_merge($settings, $validated);

        $this->saveSettings($settings);

        Cache::forget('vw_narrative_settings');

        session()->flash('success', 'Narrative structure settings updated successfully.');

        return redirect()->back();
    }

    /**
     * Toggle item enabled status.
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:arc,preset,curve,journey',
            'key' => 'required|string|max:100',
        ]);

        $settings = $this->getSettings();
        $disabledKey = 'disabled_' . $validated['type'] . 's';

        if (!isset($settings[$disabledKey])) {
            $settings[$disabledKey] = [];
        }

        $key = $validated['key'];
        if (in_array($key, $settings[$disabledKey])) {
            // Enable it
            $settings[$disabledKey] = array_values(array_diff($settings[$disabledKey], [$key]));
            $enabled = true;
        } else {
            // Disable it
            $settings[$disabledKey][] = $key;
            $enabled = false;
        }

        $this->saveSettings($settings);
        Cache::forget('vw_narrative_settings');

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Get narrative structure settings.
     */
    protected function getSettings(): array
    {
        return Cache::remember('vw_narrative_settings', 3600, function () {
            $stored = get_option('vw_narrative_settings', '{}');
            return json_decode($stored, true) ?: [
                'default_preset' => null,
                'default_arc' => null,
                'default_curve' => null,
                'default_journey' => null,
                'disabled_arcs' => [],
                'disabled_presets' => [],
                'disabled_curves' => [],
                'disabled_journeys' => [],
                'show_advanced_by_default' => false,
            ];
        });
    }

    /**
     * Save narrative structure settings.
     */
    protected function saveSettings(array $settings): void
    {
        set_option('vw_narrative_settings', json_encode($settings));
    }

    /**
     * Export narrative structures as JSON.
     */
    public function export()
    {
        $data = [
            'story_arcs' => config('appvideowizard.story_arcs', []),
            'narrative_presets' => config('appvideowizard.narrative_presets', []),
            'tension_curves' => config('appvideowizard.tension_curves', []),
            'emotional_journeys' => config('appvideowizard.emotional_journeys', []),
            'settings' => $this->getSettings(),
            'exported_at' => now()->toISOString(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="narrative-structures.json"');
    }
}
