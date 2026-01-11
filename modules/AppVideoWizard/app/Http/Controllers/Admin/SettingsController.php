<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwSetting;

class SettingsController extends Controller
{
    /**
     * Display the dynamic settings page.
     */
    public function index()
    {
        $categories = VwSetting::getCategoryLabels();
        $categoryIcons = VwSetting::getCategoryIcons();

        // Get all settings grouped by category
        $settingsByCategory = [];
        foreach (array_keys($categories) as $category) {
            $settingsByCategory[$category] = VwSetting::where('category', $category)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        // Calculate stats
        $stats = [
            'total' => VwSetting::count(),
            'active' => VwSetting::where('is_active', true)->count(),
            'categories' => count(array_filter($settingsByCategory, fn($s) => $s->count() > 0)),
        ];

        return view('appvideowizard::admin.dynamic-settings.index', compact(
            'categories',
            'categoryIcons',
            'settingsByCategory',
            'stats'
        ));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $slug => $value) {
            $setting = VwSetting::where('slug', $slug)->first();

            if (!$setting) {
                continue;
            }

            // Handle different input types
            $processedValue = $this->processValue($value, $setting);

            $setting->update(['value' => $processedValue]);
        }

        // Clear cache
        VwSetting::clearCache();

        session()->flash('success', 'Settings updated successfully.');

        return redirect()->route('admin.video-wizard.dynamic-settings.index');
    }

    /**
     * Process value based on setting type.
     */
    protected function processValue(mixed $value, VwSetting $setting): string
    {
        // Handle checkboxes (they don't send value when unchecked)
        if ($setting->input_type === 'checkbox') {
            return $value ? 'true' : 'false';
        }

        // Handle JSON/array values
        if ($setting->value_type === 'json' || $setting->value_type === 'array') {
            if (is_string($value)) {
                // Validate JSON
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $setting->value; // Keep old value if invalid
                }
                return $value;
            }
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Reset a category to defaults.
     */
    public function resetCategory(Request $request, string $category)
    {
        $settings = VwSetting::where('category', $category)->get();

        foreach ($settings as $setting) {
            if ($setting->default_value !== null) {
                $setting->update(['value' => $setting->default_value]);
            }
        }

        VwSetting::clearCache();

        session()->flash('success', "Settings for '" . VwSetting::getCategoryLabels()[$category] . "' reset to defaults.");

        return redirect()->back();
    }

    /**
     * Reset all settings to defaults.
     */
    public function resetAll()
    {
        $settings = VwSetting::all();

        foreach ($settings as $setting) {
            if ($setting->default_value !== null) {
                $setting->update(['value' => $setting->default_value]);
            }
        }

        VwSetting::clearCache();

        session()->flash('success', 'All settings reset to defaults.');

        return redirect()->route('admin.video-wizard.dynamic-settings.index');
    }

    /**
     * Toggle a boolean setting.
     */
    public function toggle(VwSetting $setting)
    {
        if ($setting->value_type !== 'boolean') {
            session()->flash('error', 'This setting cannot be toggled.');
            return redirect()->back();
        }

        $currentValue = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        $setting->update(['value' => $currentValue ? 'false' : 'true']);

        $status = !$currentValue ? 'enabled' : 'disabled';
        session()->flash('success', "'{$setting->name}' {$status}.");

        return redirect()->back();
    }

    /**
     * Get settings as JSON (for API/AJAX).
     */
    public function getJson(Request $request)
    {
        $category = $request->input('category');

        if ($category) {
            $settings = VwSetting::getByCategory($category);
        } else {
            $settings = VwSetting::getAllCached();
        }

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Update a single setting via AJAX.
     */
    public function updateSingle(Request $request, VwSetting $setting)
    {
        $value = $request->input('value');
        $processedValue = $this->processValue($value, $setting);

        $setting->update(['value' => $processedValue]);
        VwSetting::clearCache();

        return response()->json([
            'success' => true,
            'setting' => $setting->toConfigArray(),
        ]);
    }

    /**
     * Re-seed default settings (useful after updates).
     */
    public function seedDefaults()
    {
        $seeder = new \Modules\AppVideoWizard\Database\Seeders\VwSettingSeeder();
        $seeder->run();

        session()->flash('success', 'Default settings seeded successfully. Existing values preserved.');

        return redirect()->route('admin.video-wizard.dynamic-settings.index');
    }
}
