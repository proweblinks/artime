<?php

namespace Modules\AppAITools\Livewire;

use Livewire\Component;
use Modules\AppAITools\Models\AiToolHistory;
use Carbon\Carbon;

class ToolsHub extends Component
{
    public array $recentActivity = [];
    public array $usageStats = [];

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

        $this->loadUsageStats($teamId);
        $this->loadRecentActivity($teamId);
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

    public function render()
    {
        return view('appaitools::livewire.tools-hub', [
            'tools' => config('appaitools.tools'),
        ]);
    }
}
