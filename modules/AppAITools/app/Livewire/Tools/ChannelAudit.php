<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Modules\AppAITools\Livewire\Tools\Concerns\HasToolHistory;

class ChannelAudit extends Component
{
    use HasToolHistory;

    public string $channelUrl = '';
    public string $platform = '';
    public bool $isLoading = false;
    public ?array $result = null;

    protected function getToolKey(): string { return 'channel_audit'; }

    public function mount()
    {
        $this->platform = get_option('creator_hub_default_platform', 'youtube');
        $this->loadHistory();
    }

    public function audit()
    {
        $this->validate([
            'channelUrl' => 'required|url',
            'platform' => 'required|in:' . implode(',', array_keys(config('appaitools.platforms'))),
        ]);

        $this->isLoading = true;

        try {
            $service = app(\Modules\AppAITools\Services\ChannelAuditService::class);
            $this->result = $service->audit($this->channelUrl, $this->platform);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function resetForm(): void
    {
        $this->channelUrl = '';
        $this->result = null;
        $this->isLoading = false;
    }

    public function render()
    {
        return view('appaitools::livewire.tools.channel-audit', [
            'platforms' => config('appaitools.platforms'),
        ]);
    }
}
