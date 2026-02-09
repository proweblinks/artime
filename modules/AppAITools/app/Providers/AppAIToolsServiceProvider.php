<?php

namespace Modules\AppAITools\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppAIToolsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppAITools';

    protected string $nameLower = 'appaitools';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerLivewireComponents();
        $this->registerAdminMenu();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        \Pricing::addSubFeatures([
            "sort"      => 135,
            "parent"    => "features",
            "tab_id"    => 'ai-tools',
            "tab_name"  => __("AI Tools"),
            "key"       => "appaitools",
            "label"     => __("AI Creator Tools"),
            "check"     => true,
            "type"      => "boolean",
            "raw"       => 0,
        ]);
    }

    /**
     * Register admin sidebar menu for Creator Hub settings.
     */
    protected function registerAdminMenu(): void
    {
        View::composer('*', function ($view) {
            static $menuRegistered = false;
            if ($menuRegistered) {
                return;
            }

            $loginAs = session('login_as', 'client');
            if ($loginAs !== 'admin') {
                return;
            }

            $sidebar = View::shared('sidebar');
            if (!$sidebar || !isset($sidebar['top'])) {
                return;
            }

            // Find the Video Creator admin menu and add Creator Hub settings under it
            foreach ($sidebar['top'] as &$item) {
                if (isset($item['uri']) && str_contains($item['uri'], 'admin/video-wizard')) {
                    // Check if Creator Hub sub-menu already exists
                    $hasCreatorHub = false;
                    if (isset($item['sub_menu'])) {
                        foreach ($item['sub_menu'] as $sub) {
                            if (isset($sub['uri']) && str_contains($sub['uri'], 'admin/creator-hub')) {
                                $hasCreatorHub = true;
                                break;
                            }
                        }
                    }

                    if (!$hasCreatorHub && isset($item['sub_menu'])) {
                        $item['sub_menu'][] = [
                            'uri' => 'admin/creator-hub/settings',
                            'name' => 'Creator Hub Settings',
                            'position' => 105,
                            'icon' => 'fa-light fa-wand-magic-sparkles',
                        ];

                        // Re-sort sub_menu by position descending
                        usort($item['sub_menu'], function ($a, $b) {
                            return ($b['position'] ?? 0) <=> ($a['position'] ?? 0);
                        });
                    }
                    break;
                }
            }
            unset($item);

            View::share('sidebar', $sidebar);
            $menuRegistered = true;
        });
    }

    /**
     * Register Livewire components.
     */
    protected function registerLivewireComponents(): void
    {
        // Main hub
        Livewire::component('app-ai-tools::tools-hub', \Modules\AppAITools\Livewire\ToolsHub::class);

        // Tool components
        Livewire::component('app-ai-tools::video-optimizer', \Modules\AppAITools\Livewire\Tools\VideoOptimizer::class);
        Livewire::component('app-ai-tools::competitor-analysis', \Modules\AppAITools\Livewire\Tools\CompetitorAnalysis::class);
        Livewire::component('app-ai-tools::trend-predictor', \Modules\AppAITools\Livewire\Tools\TrendPredictor::class);
        Livewire::component('app-ai-tools::ai-thumbnails', \Modules\AppAITools\Livewire\Tools\AiThumbnails::class);
        Livewire::component('app-ai-tools::channel-audit', \Modules\AppAITools\Livewire\Tools\ChannelAudit::class);
        Livewire::component('app-ai-tools::more-tools', \Modules\AppAITools\Livewire\Tools\MoreTools::class);

        // Sub-tool components
        Livewire::component('app-ai-tools::script-studio', \Modules\AppAITools\Livewire\SubTools\ScriptStudio::class);
        Livewire::component('app-ai-tools::viral-hook-lab', \Modules\AppAITools\Livewire\SubTools\ViralHookLab::class);
        Livewire::component('app-ai-tools::content-multiplier', \Modules\AppAITools\Livewire\SubTools\ContentMultiplier::class);
        Livewire::component('app-ai-tools::thumbnail-arena', \Modules\AppAITools\Livewire\SubTools\ThumbnailArena::class);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
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

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
