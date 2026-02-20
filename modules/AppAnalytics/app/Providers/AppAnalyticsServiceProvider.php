<?php

namespace Modules\AppAnalytics\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\AppAnalytics\Console\SnapshotCommand;
use Core;

class AppAnalyticsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppAnalytics';
    protected string $nameLower = 'appanalytics';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerSubMenu();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerLivewireComponents();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        \Plan::addPermissions($this->name, [
            "sort" => 3500,
            "view" => "permissions",
        ]);

        \Pricing::addSubFeatures([
            "sort"      => 140,
            "parent"    => "features",
            "tab_id"    => 'analytics',
            "tab_name"  => __("Analytics"),
            "key"       => "appanalytics",
            "label"     => __("Social Media Analytics"),
            "check"     => true,
            "type"      => "boolean",
            "raw"       => 0,
        ]);
    }

    public function register(): void
    {
        $this->commands([
            SnapshotCommand::class,
        ]);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            $schedule->command('appanalytics:snapshots')->dailyAt('03:00');
        });
    }

    protected function registerSubMenu(): void
    {
        Core::addSubMenu("AdminAPIIntegration", [
            [
                "uri" => "admin/api-integration/analytics",
                "name" => "Analytics",
                "position" => 600000,
                "icon" => "fa-light fa-chart-mixed",
                "color" => "#6366F1"
            ],
        ]);
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('app-analytics::analytics-dashboard', \Modules\AppAnalytics\Livewire\AnalyticsDashboard::class);
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower . '.' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }
        return $paths;
    }
}
