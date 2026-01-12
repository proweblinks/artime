<?php

namespace Modules\AppVideoWizard\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppVideoWizardServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppVideoWizard';

    protected string $nameLower = 'appvideowizard';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->registerLivewireComponents();
        $this->registerAdminMenu();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register admin sidebar menu for Video Wizard.
     */
    protected function registerAdminMenu(): void
    {
        // Use view composer to inject admin menu after sidebar is shared
        View::composer('*', function ($view) {
            // Only run once per request
            static $menuRegistered = false;
            if ($menuRegistered) {
                return;
            }

            // Check if user is admin
            $loginAs = session('login_as', 'client');
            if ($loginAs !== 'admin') {
                return;
            }

            // Get current sidebar data
            $sidebar = View::shared('sidebar');
            if (!$sidebar || !isset($sidebar['top'])) {
                return;
            }

            // Check if Video Wizard admin menu already exists
            foreach ($sidebar['top'] as $item) {
                if (isset($item['uri']) && str_contains($item['uri'], 'admin/video-wizard')) {
                    $menuRegistered = true;
                    return;
                }
            }

            // Add Video Wizard admin menu under AI Settings tab
            $videoWizardMenu = [
                'id' => 'admin-video-wizard',
                'uri' => 'admin/video-wizard',
                'role' => 'admin',
                'platform' => 0,
                'section' => 'top',
                'tab_id' => 10, // AI Settings tab
                'tab_name' => 'AI Settings',
                'position' => 4000, // Before AI Templates (5000)
                'name' => 'Video Creator',
                'color' => '#8b5cf6',
                'icon' => 'fa-light fa-video',
                'sub_menu' => [
                    [
                        'uri' => 'admin/video-wizard',
                        'name' => 'Dashboard',
                        'position' => 100,
                        'icon' => 'fa-light fa-gauge',
                    ],
                    [
                        'uri' => 'admin/video-wizard/prompts',
                        'name' => 'AI Prompts',
                        'position' => 90,
                        'icon' => 'fa-light fa-message-bot',
                    ],
                    [
                        'uri' => 'admin/video-wizard/narrative',
                        'name' => 'Narrative Structures',
                        'position' => 85,
                        'icon' => 'fa-light fa-film',
                    ],
                    [
                        'uri' => 'admin/video-wizard/production-types',
                        'name' => 'Production Types',
                        'position' => 80,
                        'icon' => 'fa-light fa-clapperboard',
                    ],
                    [
                        'uri' => 'admin/video-wizard/logs',
                        'name' => 'Generation Logs',
                        'position' => 70,
                        'icon' => 'fa-light fa-list-timeline',
                    ],
                    [
                        'uri' => 'admin/video-wizard/logs/analytics',
                        'name' => 'Analytics',
                        'position' => 60,
                        'icon' => 'fa-light fa-chart-mixed',
                    ],
                ],
            ];

            // Insert menu and re-sort
            $sidebar['top'][] = $videoWizardMenu;

            // Sort by tab_id then position
            usort($sidebar['top'], function ($a, $b) {
                if ($a['tab_id'] !== $b['tab_id']) {
                    return $a['tab_id'] <=> $b['tab_id'];
                }
                return ($b['position'] ?? 0) <=> ($a['position'] ?? 0);
            });

            // Re-share the sidebar
            View::share('sidebar', $sidebar);
            $menuRegistered = true;
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register pricing features like working modules
        \Pricing::addSubFeatures([
            "sort"      => 130,
            "parent"    => "features",
            "tab_id"    => 'video-wizard',
            "tab_name"  => __("Video Creator"),
            "key"       => "appvideowizard",
            "label"     => __("AI Video Creator"),
            "check"     => true,
            "type"      => "boolean",
            "raw"       => 0,
        ]);

        // Phase 1-5: Register intelligence services
        $this->registerIntelligenceServices();
    }

    /**
     * Register Phase 1-5 intelligence services for dependency injection.
     */
    protected function registerIntelligenceServices(): void
    {
        // Phase 1: Camera Movement Service
        $this->app->singleton(
            \Modules\AppVideoWizard\Services\CameraMovementService::class,
            fn () => new \Modules\AppVideoWizard\Services\CameraMovementService()
        );

        // Phase 2: Video Prompt Builder Service
        $this->app->singleton(
            \Modules\AppVideoWizard\Services\VideoPromptBuilderService::class,
            fn () => new \Modules\AppVideoWizard\Services\VideoPromptBuilderService()
        );

        // Phase 3: Shot Continuity Service (depends on Phase 1)
        $this->app->singleton(
            \Modules\AppVideoWizard\Services\ShotContinuityService::class,
            fn ($app) => new \Modules\AppVideoWizard\Services\ShotContinuityService(
                $app->make(\Modules\AppVideoWizard\Services\CameraMovementService::class)
            )
        );

        // Phase 4: Scene Type Detector Service
        $this->app->singleton(
            \Modules\AppVideoWizard\Services\SceneTypeDetectorService::class,
            fn () => new \Modules\AppVideoWizard\Services\SceneTypeDetectorService()
        );

        // Shot Intelligence Service (depends on Phases 1-4)
        $this->app->singleton(
            \Modules\AppVideoWizard\Services\ShotIntelligenceService::class,
            fn ($app) => new \Modules\AppVideoWizard\Services\ShotIntelligenceService(
                $app->make(\Modules\AppVideoWizard\Services\ShotContinuityService::class),
                $app->make(\Modules\AppVideoWizard\Services\SceneTypeDetectorService::class),
                $app->make(\Modules\AppVideoWizard\Services\CameraMovementService::class),
                $app->make(\Modules\AppVideoWizard\Services\VideoPromptBuilderService::class)
            )
        );

        // Phase 5: Enhanced Prompt Service (unified facade)
        $this->app->singleton(
            \Modules\AppVideoWizard\Services\EnhancedPromptService::class,
            fn () => new \Modules\AppVideoWizard\Services\EnhancedPromptService()
        );
    }

    /**
     * Register Livewire components manually.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('appvideowizard::video-wizard', \Modules\AppVideoWizard\Livewire\VideoWizard::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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
     * Register and publish assets.
     */
    public function registerAssets(): void
    {
        $assetsPath = module_path($this->name, 'resources/assets');
        $publicPath = public_path('modules/'.$this->nameLower);

        // Publish JS and CSS assets
        $this->publishes([
            $assetsPath.'/js' => $publicPath.'/js',
        ], ['assets', $this->nameLower.'-assets']);
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
