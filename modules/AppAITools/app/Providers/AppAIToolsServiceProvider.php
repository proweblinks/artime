<?php

namespace Modules\AppAITools\Providers;

use Illuminate\Support\Facades\Blade;
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
     * Note: The menu item is defined in AppVideoWizardServiceProvider pointing to admin/creator-hub/settings.
     */
    protected function registerAdminMenu(): void
    {
        // No-op: menu link is already registered by AppVideoWizardServiceProvider
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

        // Enterprise Suite components
        Livewire::component('app-ai-tools::enterprise-dashboard', \Modules\AppAITools\Livewire\Enterprise\EnterpriseDashboard::class);
        Livewire::component('app-ai-tools::enterprise.placement-finder', \Modules\AppAITools\Livewire\Enterprise\PlacementFinder::class);
        Livewire::component('app-ai-tools::enterprise.monetization-analyzer', \Modules\AppAITools\Livewire\Enterprise\MonetizationAnalyzer::class);
        Livewire::component('app-ai-tools::enterprise.sponsorship-calculator', \Modules\AppAITools\Livewire\Enterprise\SponsorshipCalculator::class);
        Livewire::component('app-ai-tools::enterprise.revenue-diversification', \Modules\AppAITools\Livewire\Enterprise\RevenueDiversification::class);
        Livewire::component('app-ai-tools::enterprise.cpm-booster', \Modules\AppAITools\Livewire\Enterprise\CpmBooster::class);
        Livewire::component('app-ai-tools::enterprise.audience-profiler', \Modules\AppAITools\Livewire\Enterprise\AudienceProfiler::class);
        Livewire::component('app-ai-tools::enterprise.digital-product-architect', \Modules\AppAITools\Livewire\Enterprise\DigitalProductArchitect::class);
        Livewire::component('app-ai-tools::enterprise.affiliate-finder', \Modules\AppAITools\Livewire\Enterprise\AffiliateFinder::class);
        Livewire::component('app-ai-tools::enterprise.multi-income-converter', \Modules\AppAITools\Livewire\Enterprise\MultiIncomeConverter::class);
        Livewire::component('app-ai-tools::enterprise.brand-deal-matchmaker', \Modules\AppAITools\Livewire\Enterprise\BrandDealMatchmaker::class);
        Livewire::component('app-ai-tools::enterprise.licensing-scout', \Modules\AppAITools\Livewire\Enterprise\LicensingScout::class);
        Livewire::component('app-ai-tools::enterprise.revenue-automation', \Modules\AppAITools\Livewire\Enterprise\RevenueAutomation::class);

        // Cross-Platform YouTube↔TikTok tools
        Livewire::component('app-ai-tools::enterprise.tiktok-yt-converter', \Modules\AppAITools\Livewire\Enterprise\TiktokYtConverter::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-yt-arbitrage', \Modules\AppAITools\Livewire\Enterprise\TiktokYtArbitrage::class);

        // TikTok Enterprise tools
        Livewire::component('app-ai-tools::enterprise.tiktok-hashtag-strategy', \Modules\AppAITools\Livewire\Enterprise\TiktokHashtagStrategy::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-seo-analyzer', \Modules\AppAITools\Livewire\Enterprise\TiktokSeoAnalyzer::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-posting-time', \Modules\AppAITools\Livewire\Enterprise\TiktokPostingTime::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-hook-analyzer', \Modules\AppAITools\Livewire\Enterprise\TiktokHookAnalyzer::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-sound-trends', \Modules\AppAITools\Livewire\Enterprise\TiktokSoundTrends::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-viral-predictor', \Modules\AppAITools\Livewire\Enterprise\TiktokViralPredictor::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-creator-fund', \Modules\AppAITools\Livewire\Enterprise\TiktokCreatorFund::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-duet-stitch', \Modules\AppAITools\Livewire\Enterprise\TiktokDuetStitch::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-brand-partnership', \Modules\AppAITools\Livewire\Enterprise\TiktokBrandPartnership::class);
        Livewire::component('app-ai-tools::enterprise.tiktok-shop-optimizer', \Modules\AppAITools\Livewire\Enterprise\TiktokShopOptimizer::class);
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
