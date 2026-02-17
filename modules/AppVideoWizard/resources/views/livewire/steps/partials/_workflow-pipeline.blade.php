{{-- Workflow Pipeline Visualization --}}
{{-- Shows a vertical flow of nodes with status, timing, and expand/edit capability --}}

<style>
    .vw-pipeline { padding: 0.5rem 0; }

    .vw-pipeline-empty {
        text-align: center;
        padding: 2rem 1rem;
        color: #64748b;
        font-size: 0.85rem;
    }
    .vw-pipeline-empty i { font-size: 1.5rem; margin-bottom: 0.5rem; display: block; color: #475569; }

    /* Node card */
    .vw-node-card {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 0.5rem;
        margin-bottom: 0;
        transition: border-color 0.2s, box-shadow 0.2s;
        overflow: hidden;
    }
    .vw-node-card:hover { border-color: #475569; }
    .vw-node-card.status-completed { border-left: 3px solid #22c55e; }
    .vw-node-card.status-running { border-left: 3px solid #3b82f6; }
    .vw-node-card.status-waiting { border-left: 3px solid #eab308; }
    .vw-node-card.status-paused { border-left: 3px solid #f59e0b; }
    .vw-node-card.status-failed { border-left: 3px solid #ef4444; }
    .vw-node-card.status-pending { border-left: 3px solid #475569; }

    /* Node header row */
    .vw-node-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 0.75rem;
        cursor: pointer;
        user-select: none;
    }
    .vw-node-header:hover { background: #1a2332; }

    .vw-node-status-icon {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        flex-shrink: 0;
    }
    .vw-node-status-icon.pending { background: #334155; color: #94a3b8; }
    .vw-node-status-icon.running { background: #1d4ed8; color: #fff; animation: pulse-blue 1.5s infinite; }
    .vw-node-status-icon.waiting { background: #854d0e; color: #fbbf24; animation: pulse-yellow 2s infinite; }
    .vw-node-status-icon.paused { background: #92400e; color: #fbbf24; }
    .vw-node-status-icon.completed { background: #166534; color: #4ade80; }
    .vw-node-status-icon.failed { background: #991b1b; color: #fca5a5; }

    @keyframes pulse-blue {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        50% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0); }
    }
    @keyframes pulse-yellow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(234, 179, 8, 0.3); }
        50% { box-shadow: 0 0 0 4px rgba(234, 179, 8, 0); }
    }

    .vw-node-info { flex: 1; min-width: 0; }
    .vw-node-name {
        font-size: 0.8rem;
        font-weight: 600;
        color: #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .vw-node-meta {
        font-size: 0.65rem;
        color: #64748b;
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-top: 1px;
    }

    .vw-node-type-badge {
        font-size: 0.6rem;
        padding: 0.1rem 0.35rem;
        border-radius: 0.2rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .vw-node-type-badge.ai_text { background: #1e3a5f; color: #60a5fa; }
    .vw-node-type-badge.ai_image { background: #3b1f5e; color: #c084fc; }
    .vw-node-type-badge.ai_video { background: #5c1e1e; color: #fca5a5; }
    .vw-node-type-badge.transform { background: #1a3a2a; color: #6ee7b7; }
    .vw-node-type-badge.user_input { background: #3b3a1e; color: #fde68a; }
    .vw-node-type-badge.poll_wait { background: #2d2415; color: #fbbf24; }
    .vw-node-type-badge.conditional { background: #1e293b; color: #94a3b8; }
    .vw-node-type-badge.compose { background: #1a2e3b; color: #67e8f9; }

    .vw-node-timing {
        font-size: 0.65rem;
        color: #64748b;
        white-space: nowrap;
    }

    .vw-node-expand-icon {
        color: #475569;
        font-size: 0.7rem;
        transition: transform 0.2s;
        flex-shrink: 0;
    }
    .vw-node-expand-icon.expanded { transform: rotate(180deg); }

    /* Connector line between nodes */
    .vw-node-connector {
        display: flex;
        justify-content: center;
        height: 1.25rem;
        position: relative;
    }
    .vw-node-connector::before {
        content: '';
        width: 2px;
        height: 100%;
        background: #334155;
    }

    /* Parallel split indicator */
    .vw-parallel-badge {
        font-size: 0.6rem;
        color: #64748b;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 0.75rem;
        padding: 0.1rem 0.5rem;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }

    /* Expanded node detail panel */
    .vw-node-detail {
        border-top: 1px solid #1e293b;
        padding: 0.75rem;
        background: #0f172a;
    }

    .vw-node-detail-section {
        margin-bottom: 0.75rem;
    }
    .vw-node-detail-section:last-child { margin-bottom: 0; }

    .vw-node-detail-label {
        font-size: 0.65rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-node-detail-content {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 0.35rem;
        padding: 0.5rem;
        font-size: 0.75rem;
        color: #cbd5e1;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 12rem;
        overflow-y: auto;
        font-family: ui-monospace, monospace;
        line-height: 1.4;
    }

    .vw-node-detail-textarea {
        background: #1e293b;
        border: 1px solid #475569;
        border-radius: 0.35rem;
        padding: 0.5rem;
        font-size: 0.75rem;
        color: #e2e8f0;
        width: 100%;
        min-height: 6rem;
        resize: vertical;
        font-family: ui-monospace, monospace;
        line-height: 1.4;
    }
    .vw-node-detail-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 1px #3b82f6;
    }

    .vw-node-detail-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .vw-node-btn {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        border-radius: 0.3rem;
        border: 1px solid #334155;
        background: #1e293b;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.15s;
    }
    .vw-node-btn:hover { background: #334155; color: #e2e8f0; }
    .vw-node-btn.primary { background: #1d4ed8; border-color: #2563eb; color: #fff; }
    .vw-node-btn.primary:hover { background: #2563eb; }
    .vw-node-btn.danger { color: #fca5a5; }
    .vw-node-btn.danger:hover { background: #7f1d1d; border-color: #991b1b; }

    /* Error display */
    .vw-node-error {
        background: #450a0a;
        border: 1px solid #991b1b;
        border-radius: 0.3rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.7rem;
        color: #fca5a5;
        margin-top: 0.35rem;
    }

    /* Output preview */
    .vw-node-output-preview {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Pipeline header */
    .vw-pipeline-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.5rem;
    }
    .vw-pipeline-header h4 {
        font-size: 0.85rem;
        font-weight: 600;
        color: #e2e8f0;
        margin: 0;
    }
    .vw-pipeline-status {
        font-size: 0.65rem;
        padding: 0.15rem 0.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
    }
    .vw-pipeline-status.running { background: #1e3a5f; color: #60a5fa; }
    .vw-pipeline-status.paused { background: #3b3a1e; color: #fde68a; }
    .vw-pipeline-status.completed { background: #14532d; color: #4ade80; }
    .vw-pipeline-status.failed { background: #450a0a; color: #fca5a5; }
    .vw-pipeline-status.pending { background: #1e293b; color: #94a3b8; }
</style>

@php
    $workflowSummary = $workflowExecutionSummary ?? null;
    $workflowNodes = $workflowSummary['nodes'] ?? [];
    $workflowEdges = $workflowSummary['edges'] ?? [];
    $workflowStatus = $workflowSummary['status'] ?? 'pending';
@endphp

<div class="vw-pipeline"
     x-data="{
        expandedNode: null,
        editingNode: null,
        nodeEdits: {},

        toggleNode(nodeId) {
            this.expandedNode = this.expandedNode === nodeId ? null : nodeId;
            if (this.expandedNode !== nodeId) {
                this.editingNode = null;
            }
        },

        startEdit(nodeId) {
            this.editingNode = nodeId;
        },

        cancelEdit() {
            this.editingNode = null;
            this.nodeEdits = {};
        },

        saveNodeEdits(nodeId) {
            if (Object.keys(this.nodeEdits).length > 0) {
                $wire.call('saveWorkflowNodeEdits', nodeId, this.nodeEdits);
            }
            this.editingNode = null;
            this.nodeEdits = {};
        },

        resetNode(nodeId) {
            $wire.call('resetWorkflowNode', nodeId);
            this.editingNode = null;
            this.nodeEdits = {};
        },

        getStatusIcon(status) {
            const icons = {
                pending: 'fa-circle',
                running: 'fa-play',
                waiting: 'fa-clock',
                paused: 'fa-pause',
                completed: 'fa-check',
                failed: 'fa-xmark'
            };
            return icons[status] || 'fa-circle';
        },

        formatTiming(seconds) {
            if (!seconds) return '';
            if (seconds < 1) return (seconds * 1000).toFixed(0) + 'ms';
            if (seconds < 60) return seconds.toFixed(1) + 's';
            return Math.floor(seconds / 60) + 'm ' + Math.floor(seconds % 60) + 's';
        }
     }">

    @if(empty($workflowNodes))
        <div class="vw-pipeline-empty">
            <i class="fa-solid fa-diagram-project"></i>
            {{ __('No active workflow') }}<br>
            <span style="font-size: 0.75rem;">{{ __('Start generating to see the pipeline') }}</span>
        </div>
    @else
        {{-- Pipeline Header --}}
        <div class="vw-pipeline-header">
            <h4>
                <i class="fa-solid fa-diagram-project" style="margin-right: 0.3rem; opacity: 0.6;"></i>
                {{ $activeWorkflowName ?? 'Workflow' }}
            </h4>
            <span class="vw-pipeline-status {{ $workflowStatus }}">
                {{ ucfirst($workflowStatus) }}
            </span>
        </div>

        {{-- Node list --}}
        @foreach($workflowNodes as $index => $node)
            @php
                $nodeId = $node['id'];
                $nodeStatus = $node['status'] ?? 'pending';
                $nodeTiming = $node['timing'] ?? null;
                $nodeError = $node['error'] ?? null;
                $nodeType = $node['type'] ?? 'unknown';
                $nodeConfig = $node['config'] ?? [];
                $hasOutput = $node['has_output'] ?? false;
            @endphp

            {{-- Connector line between nodes --}}
            @if($index > 0)
                <div class="vw-node-connector"></div>
            @endif

            {{-- Node Card --}}
            <div class="vw-node-card status-{{ $nodeStatus }}">
                {{-- Header (always visible) --}}
                <div class="vw-node-header" @click="toggleNode('{{ $nodeId }}')">
                    {{-- Status icon --}}
                    <div class="vw-node-status-icon {{ $nodeStatus }}">
                        <i class="fa-solid" :class="getStatusIcon('{{ $nodeStatus }}')"></i>
                    </div>

                    {{-- Node info --}}
                    <div class="vw-node-info">
                        <div class="vw-node-name">{{ $node['name'] }}</div>
                        <div class="vw-node-meta">
                            <span class="vw-node-type-badge {{ $nodeType }}">{{ str_replace('_', ' ', $nodeType) }}</span>
                            @if(!empty($node['description']))
                                <span>{{ Str::limit($node['description'], 40) }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Timing --}}
                    @if($nodeTiming)
                        <span class="vw-node-timing" x-text="formatTiming({{ $nodeTiming }})"></span>
                    @elseif($nodeStatus === 'running' || $nodeStatus === 'waiting')
                        <span class="vw-node-timing">
                            <i class="fa-solid fa-spinner fa-spin" style="font-size: 0.6rem;"></i>
                        </span>
                    @endif

                    {{-- Expand arrow --}}
                    <i class="fa-solid fa-chevron-down vw-node-expand-icon"
                       :class="{ 'expanded': expandedNode === '{{ $nodeId }}' }"></i>
                </div>

                {{-- Error display --}}
                @if($nodeError)
                    <div class="vw-node-error" style="margin: 0 0.75rem 0.5rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> {{ $nodeError }}
                    </div>
                @endif

                {{-- Expanded Detail Panel --}}
                <div class="vw-node-detail"
                     x-show="expandedNode === '{{ $nodeId }}'"
                     x-collapse
                     x-cloak>

                    {{-- Config: Prompt Template --}}
                    @if(!empty($nodeConfig['prompt_template']))
                        <div class="vw-node-detail-section">
                            <div class="vw-node-detail-label">
                                <i class="fa-solid fa-file-lines"></i> {{ __('Prompt Template') }}
                                @if($nodeStatus !== 'running')
                                    <button class="vw-node-btn" style="margin-left: auto;"
                                            @click.stop="startEdit('{{ $nodeId }}')"
                                            x-show="editingNode !== '{{ $nodeId }}'">
                                        <i class="fa-solid fa-pen-to-square"></i> {{ __('Edit') }}
                                    </button>
                                @endif
                            </div>
                            <template x-if="editingNode === '{{ $nodeId }}'">
                                <textarea class="vw-node-detail-textarea"
                                          x-model="nodeEdits.prompt_template"
                                          x-init="nodeEdits.prompt_template = {{ json_encode($nodeConfig['prompt_template']) }}"
                                          rows="8"></textarea>
                            </template>
                            <template x-if="editingNode !== '{{ $nodeId }}'">
                                <div class="vw-node-detail-content">{{ Str::limit($nodeConfig['prompt_template'], 500) }}</div>
                            </template>
                        </div>
                    @endif

                    {{-- Config: Rules --}}
                    @if(!empty($nodeConfig['rules']))
                        <div class="vw-node-detail-section">
                            <div class="vw-node-detail-label">
                                <i class="fa-solid fa-list-check"></i> {{ __('Rules') }}
                            </div>
                            <template x-if="editingNode === '{{ $nodeId }}'">
                                <textarea class="vw-node-detail-textarea"
                                          x-model="nodeEdits.rules"
                                          x-init="nodeEdits.rules = nodeEdits.rules || {{ json_encode($nodeConfig['rules']) }}"
                                          rows="6"></textarea>
                            </template>
                            <template x-if="editingNode !== '{{ $nodeId }}'">
                                <div class="vw-node-detail-content">{{ Str::limit($nodeConfig['rules'], 400) }}</div>
                            </template>
                        </div>
                    @endif

                    {{-- Config: Example --}}
                    @if(!empty($nodeConfig['example']))
                        <div class="vw-node-detail-section">
                            <div class="vw-node-detail-label">
                                <i class="fa-solid fa-lightbulb"></i> {{ __('Example') }}
                            </div>
                            <template x-if="editingNode === '{{ $nodeId }}'">
                                <textarea class="vw-node-detail-textarea"
                                          x-model="nodeEdits.example"
                                          x-init="nodeEdits.example = nodeEdits.example || {{ json_encode($nodeConfig['example']) }}"
                                          rows="4"></textarea>
                            </template>
                            <template x-if="editingNode !== '{{ $nodeId }}'">
                                <div class="vw-node-detail-content">{{ Str::limit($nodeConfig['example'], 300) }}</div>
                            </template>
                        </div>
                    @endif

                    {{-- Config: General config display --}}
                    @php
                        $displayConfig = collect($nodeConfig)
                            ->except(['prompt_template', 'rules', 'example', 'service', 'method', 'transform', 'class'])
                            ->filter(fn($v) => !is_null($v) && $v !== '')
                            ->toArray();
                    @endphp
                    @if(!empty($displayConfig))
                        <div class="vw-node-detail-section">
                            <div class="vw-node-detail-label">
                                <i class="fa-solid fa-gear"></i> {{ __('Configuration') }}
                            </div>
                            <div class="vw-node-detail-content">@foreach($displayConfig as $key => $val){{ $key }}: {{ is_array($val) ? json_encode($val) : $val }}
@endforeach</div>
                        </div>
                    @endif

                    {{-- Node Output (if completed) --}}
                    @if($hasOutput && $nodeStatus === 'completed')
                        <div class="vw-node-detail-section" x-data="{ showOutput: false, outputData: null }">
                            <div class="vw-node-detail-label">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> {{ __('Output') }}
                                <button class="vw-node-btn" style="margin-left: auto;"
                                        @click="showOutput = !showOutput; if (showOutput && !outputData) { $wire.getWorkflowNodeOutput('{{ $nodeId }}').then(() => {}) }"
                                        >
                                    <i class="fa-solid" :class="showOutput ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    <span x-text="showOutput ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                                </button>
                            </div>
                            <div x-show="showOutput" x-collapse x-cloak>
                                @php
                                    $nodeOutput = ($workflowSummary['nodes'][$index]['config'] ?? []);
                                    // Get actual output from execution
                                    $execResult = null;
                                    if ($this->activeExecutionId) {
                                        $exec = \Modules\AppVideoWizard\Models\VwWorkflowExecution::find($this->activeExecutionId);
                                        $execResult = $exec ? ($exec->getNodeResult($nodeId)['output'] ?? null) : null;
                                    }
                                @endphp
                                @if($execResult)
                                    <div class="vw-node-detail-content" style="max-height: 15rem;">{{ json_encode($execResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
                                @else
                                    <div class="vw-node-detail-content" style="color: #64748b;">{{ __('Output available after execution') }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Edit Actions --}}
                    <template x-if="editingNode === '{{ $nodeId }}'">
                        <div class="vw-node-detail-actions">
                            <button class="vw-node-btn primary" @click="saveNodeEdits('{{ $nodeId }}')">
                                <i class="fa-solid fa-floppy-disk"></i> {{ __('Save Changes') }}
                            </button>
                            <button class="vw-node-btn" @click="cancelEdit()">
                                {{ __('Cancel') }}
                            </button>
                            <button class="vw-node-btn danger" @click="resetNode('{{ $nodeId }}')" style="margin-left: auto;">
                                <i class="fa-solid fa-rotate-left"></i> {{ __('Reset') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        @endforeach
    @endif
</div>
