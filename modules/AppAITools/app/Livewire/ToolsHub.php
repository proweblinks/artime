<?php

namespace Modules\AppAITools\Livewire;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolsHub extends Component
{
    public array $recentActivity = [];
    public array $usageStats = [];
    public array $recommendedTools = [];
    public array $pinnedTools = [];
    public array $lastResults = [];
    public array $aggregateStats = [];

    protected static array $toolEmojiMap = [
        'video_optimizer' => "\xF0\x9F\x9A\x80",
        'competitor'      => "\xF0\x9F\x8E\xAF",
        'trend'           => "\xF0\x9F\x93\x88",
        'thumbnail'       => "\xF0\x9F\x8E\xA8",
        'channel_audit'   => "\xF0\x9F\x94\x8D",
        'script_studio'   => "\xF0\x9F\x93\x9D",
        'viral_hooks'     => "\xE2\x9A\xA1",
        'content_multiplier' => "\xF0\x9F\x94\x84",
        'thumbnail_arena' => "\xF0\x9F\xA5\x8A",
    ];

    protected static array $toolLabelMap = [
        'video_optimizer' => 'Video Optimization',
        'competitor'      => 'Competitor Analysis',
        'trend'           => 'Trend Prediction',
        'thumbnail'       => 'AI Thumbnail',
        'channel_audit'   => 'Channel Audit',
        'script_studio'   => 'Script Studio',
        'viral_hooks'     => 'Viral Hooks',
        'content_multiplier' => 'Content Multiplier',
        'thumbnail_arena' => 'Thumbnail Arena',
    ];

    public function mount()
    {
        $teamId = session('current_team_id');
        if (!$teamId) {
            return;
        }

        $this->loadPinnedTools($teamId);
        $this->loadUsageStats($teamId);
        $this->loadRecentActivity($teamId);
        $this->loadRecommendedTools($teamId);
        $this->loadLastResults($teamId);
        $this->loadAggregateStats($teamId);
    }

    protected function loadUsageStats(int $teamId): void
    {
        $todayStart = Carbon::today()->timestamp;

        // Count today's uses per tool category
        $todayCounts = AiToolHistory::forTeam($teamId)
            ->where('created', '>=', $todayStart)
            ->selectRaw('tool, COUNT(*) as cnt')
            ->groupBy('tool')
            ->pluck('cnt', 'tool')
            ->toArray();

        $statsConfig = [
            [
                'key'      => 'video_optimizer',
                'name'     => 'Video Optimizer',
                'emoji'    => "\xF0\x9F\x9A\x80",
                'gradient' => 'from-blue-500 to-purple-600',
            ],
            [
                'key'      => 'competitor',
                'name'     => 'Competitor Analysis',
                'emoji'    => "\xF0\x9F\x8E\xAF",
                'gradient' => 'from-red-500 to-orange-600',
            ],
            [
                'key'      => 'trend',
                'name'     => 'Trend Predictor',
                'emoji'    => "\xF0\x9F\x93\x88",
                'gradient' => 'from-cyan-500 to-blue-600',
            ],
            [
                'key'      => 'thumbnail',
                'name'     => 'AI Thumbnails',
                'emoji'    => "\xF0\x9F\x8E\xA8",
                'gradient' => 'from-pink-500 to-rose-600',
            ],
            [
                'key'      => 'channel_audit',
                'name'     => 'Channel Audit',
                'emoji'    => "\xF0\x9F\x94\x8D",
                'gradient' => 'from-emerald-500 to-teal-600',
            ],
        ];

        $this->usageStats = [];
        foreach ($statsConfig as $stat) {
            $used = $todayCounts[$stat['key']] ?? 0;
            $limit = 10; // Default daily limit, can be made plan-based later
            $percent = $limit > 0 ? min(100, round(($used / $limit) * 100)) : 0;

            $this->usageStats[] = [
                'name'     => $stat['name'],
                'emoji'    => $stat['emoji'],
                'gradient' => $stat['gradient'],
                'used'     => $used,
                'limit'    => $limit,
                'percent'  => $percent,
            ];
        }
    }

    protected function loadRecentActivity(int $teamId): void
    {
        $this->recentActivity = AiToolHistory::forTeam($teamId)
            ->completed()
            ->orderByDesc('created')
            ->limit(5)
            ->get()
            ->map(function ($h) {
                $tool = $h->tool;
                return [
                    'id'         => $h->id_secure,
                    'tool'       => $tool,
                    'tool_label' => static::$toolLabelMap[$tool] ?? ucfirst(str_replace('_', ' ', $tool)),
                    'emoji'      => static::$toolEmojiMap[$tool] ?? "\xF0\x9F\x94\xA7",
                    'title'      => $h->title ?? '-',
                    'platform'   => $h->platform,
                    'time_ago'   => Carbon::createFromTimestamp($h->created)->diffForHumans(),
                ];
            })
            ->toArray();
    }

    protected function loadPinnedTools(int $teamId): void
    {
        $raw = get_option("ai_tools_pinned_{$teamId}", '[]');
        $this->pinnedTools = json_decode($raw, true) ?: [];
    }

    public function togglePin(string $toolKey): void
    {
        $teamId = session('current_team_id');
        if (!$teamId) return;

        if (in_array($toolKey, $this->pinnedTools)) {
            $this->pinnedTools = array_values(array_diff($this->pinnedTools, [$toolKey]));
        } else {
            if (count($this->pinnedTools) < 5) {
                $this->pinnedTools[] = $toolKey;
            }
        }

        $value = json_encode($this->pinnedTools);
        $optionName = "ai_tools_pinned_{$teamId}";

        // Use updateOrInsert since the option may not exist yet
        DB::table('options')->updateOrInsert(
            ['name' => $optionName],
            ['value' => $value]
        );

        // Clear cached options
        if (app()->bound('options')) {
            app()->forgetInstance('options');
        }
    }

    protected function loadRecommendedTools(int $teamId): void
    {
        $thirtyDaysAgo = now()->subDays(30)->timestamp;
        $counts = AiToolHistory::forTeam($teamId)
            ->completed()
            ->where('created', '>=', $thirtyDaysAgo)
            ->selectRaw('tool, COUNT(*) as cnt')
            ->groupBy('tool')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'tool')
            ->toArray();

        $tools = config('appaitools.tools', []);
        $pinned = $this->pinnedTools;

        // Rank: most-used first, excluding pinned and enterprise
        $ranked = [];
        foreach ($counts as $toolKey => $count) {
            $configKey = $this->resolveConfigKey($toolKey);
            if ($configKey && isset($tools[$configKey]) && !in_array($configKey, $pinned) && $configKey !== 'enterprise_suite') {
                $ranked[$configKey] = $count;
            }
        }

        // Fill to 3 from remaining tools
        foreach ($tools as $key => $t) {
            if (count($ranked) >= 3) break;
            if (!isset($ranked[$key]) && !in_array($key, $pinned) && $key !== 'enterprise_suite') {
                $ranked[$key] = 0;
            }
        }

        $this->recommendedTools = array_slice(array_keys($ranked), 0, 3);
    }

    protected function loadLastResults(int $teamId): void
    {
        $latest = AiToolHistory::forTeam($teamId)
            ->completed()
            ->selectRaw('tool, MAX(id) as max_id')
            ->groupBy('tool')
            ->pluck('max_id', 'tool')
            ->toArray();

        if (empty($latest)) return;

        $records = AiToolHistory::whereIn('id', array_values($latest))->get();

        foreach ($records as $record) {
            $configKey = $this->resolveConfigKey($record->tool);
            if (!$configKey) continue;

            $result = $record->result_data;
            $snippet = $this->extractSnippet($result);
            if ($snippet) {
                $this->lastResults[$configKey] = [
                    'snippet' => $snippet,
                    'id' => $record->id_secure,
                    'time_ago' => Carbon::createFromTimestamp($record->created)->diffForHumans(),
                ];
            }
        }
    }

    protected function extractSnippet(?array $result): ?string
    {
        if (!$result) return null;

        // Try common result keys
        foreach (['executive_summary', 'summary', 'title'] as $key) {
            if (!empty($result[$key]) && is_string($result[$key])) {
                return Str::limit($result[$key], 60);
            }
        }

        // For numeric scores
        if (isset($result['overall_score'])) {
            return 'Score: ' . $result['overall_score'] . '/100';
        }

        return null;
    }

    protected function loadAggregateStats(int $teamId): void
    {
        $total = AiToolHistory::forTeam($teamId)->completed()->count();
        $creditsUsed = AiToolHistory::forTeam($teamId)->completed()->sum('credits_used');

        $mostUsed = AiToolHistory::forTeam($teamId)->completed()
            ->selectRaw('tool, COUNT(*) as cnt')
            ->groupBy('tool')
            ->orderByDesc('cnt')
            ->first();

        // Streak: consecutive days with at least 1 analysis
        $streak = 0;
        $day = now()->startOfDay();
        for ($i = 0; $i < 60; $i++) {
            $dayStart = $day->copy()->subDays($i)->timestamp;
            $dayEnd = $day->copy()->subDays($i - 1)->timestamp;
            $has = AiToolHistory::forTeam($teamId)->completed()
                ->where('created', '>=', $dayStart)
                ->where('created', '<', $dayEnd)
                ->exists();
            if ($has) {
                $streak++;
            } else {
                break;
            }
        }

        $mostUsedKey = $mostUsed ? $this->resolveConfigKey($mostUsed->tool) : null;
        $tools = config('appaitools.tools', []);

        $this->aggregateStats = [
            'total_analyses' => $total,
            'credits_used' => $creditsUsed,
            'most_used' => $mostUsedKey ? ($tools[$mostUsedKey]['name'] ?? ucfirst($mostUsedKey)) : '-',
            'most_used_emoji' => $mostUsedKey ? ($tools[$mostUsedKey]['emoji'] ?? '') : '',
            'streak' => $streak,
        ];
    }

    protected function resolveConfigKey(string $dbTool): ?string
    {
        $map = [
            'video_optimizer' => 'video_optimizer',
            'competitor' => 'competitor_analysis',
            'competitor_analysis' => 'competitor_analysis',
            'trend' => 'trend_predictor',
            'trend_predictor' => 'trend_predictor',
            'thumbnail' => 'ai_thumbnails',
            'ai_thumbnails' => 'ai_thumbnails',
            'channel_audit' => 'channel_audit',
            'script_studio' => 'more_tools',
            'viral_hooks' => 'more_tools',
            'content_multiplier' => 'more_tools',
            'thumbnail_arena' => 'more_tools',
        ];
        return $map[$dbTool] ?? null;
    }

    public function render()
    {
        return view('appaitools::livewire.tools-hub', [
            'tools' => config('appaitools.tools'),
            'hubCategories' => config('appaitools.hub_categories', []),
            'suggestionEngine' => config('appaitools.suggestion_engine', []),
        ]);
    }
}
