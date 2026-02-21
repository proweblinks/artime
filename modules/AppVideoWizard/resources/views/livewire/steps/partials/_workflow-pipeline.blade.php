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
        background: #ffffff;
        border: 1px solid var(--vw-border);
        border-radius: 0.5rem;
        margin-bottom: 0;
        transition: border-color 0.2s, box-shadow 0.2s;
        overflow: hidden;
    }
    .vw-node-card:hover { border-color: rgba(0, 0, 0, 0.15); }
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
    .vw-node-header:hover { background: rgba(0, 0, 0, 0.03); }

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
    .vw-node-status-icon.pending { background: #e2e8f0; color: #64748b; }
    .vw-node-status-icon.running { background: #1d4ed8; color: #fff; animation: pulse-blue 1.5s infinite; }
    .vw-node-status-icon.waiting { background: #854d0e; color: #d97706; animation: pulse-yellow 2s infinite; }
    .vw-node-status-icon.paused { background: #92400e; color: #d97706; }
    .vw-node-status-icon.completed { background: #166534; color: #4ade80; }
    .vw-node-status-icon.failed { background: #991b1b; color: #dc2626; }

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
        color: var(--vw-text);
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
    .vw-node-type-badge.ai_text { background: #dbeafe; color: #1d4ed8; }
    .vw-node-type-badge.ai_image { background: #f3e8ff; color: #7c3aed; }
    .vw-node-type-badge.ai_video { background: #fee2e2; color: #dc2626; }
    .vw-node-type-badge.transform { background: #dcfce7; color: #16a34a; }
    .vw-node-type-badge.user_input { background: #fef3c7; color: #92400e; }
    .vw-node-type-badge.poll_wait { background: #fef3c7; color: #d97706; }
    .vw-node-type-badge.conditional { background: #f1f5f9; color: #475569; }
    .vw-node-type-badge.compose { background: #e0f2fe; color: #0891b2; }

    .vw-node-timing {
        font-size: 0.65rem;
        color: #64748b;
        white-space: nowrap;
    }

    .vw-node-expand-icon {
        color: #94a3b8;
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
        background: #e2e8f0;
    }

    /* Parallel split indicator */
    .vw-parallel-badge {
        font-size: 0.6rem;
        color: #64748b;
        background: #ffffff;
        border: 1px solid var(--vw-border);
        border-radius: 0.75rem;
        padding: 0.1rem 0.5rem;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }

    /* Expanded node detail panel */
    .vw-node-detail {
        border-top: 1px solid var(--vw-border);
        padding: 0.75rem;
        background: #f8fafc;
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
        background: #ffffff;
        border: 1px solid var(--vw-border);
        border-radius: 0.35rem;
        padding: 0.5rem;
        font-size: 0.75rem;
        color: var(--vw-text);
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 12rem;
        overflow-y: auto;
        font-family: ui-monospace, monospace;
        line-height: 1.4;
    }

    .vw-node-detail-textarea {
        background: #ffffff;
        border: 1px solid var(--vw-border);
        border-radius: 0.35rem;
        padding: 0.5rem;
        font-size: 0.75rem;
        color: var(--vw-text);
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
        border: 1px solid var(--vw-border);
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s;
    }
    .vw-node-btn:hover { background: #f1f5f9; color: var(--vw-text); }
    .vw-node-btn.primary { background: #1d4ed8; border-color: #2563eb; color: #fff; }
    .vw-node-btn.primary:hover { background: #2563eb; }
    .vw-node-btn.danger { color: #dc2626; }
    .vw-node-btn.danger:hover { background: #fef2f2; border-color: #fecaca; }

    /* Error display */
    .vw-node-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 0.3rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.7rem;
        color: #dc2626;
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
        color: var(--vw-text);
        margin: 0;
    }
    .vw-pipeline-status {
        font-size: 0.65rem;
        padding: 0.15rem 0.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
    }
    .vw-pipeline-status.running { background: #dbeafe; color: #1d4ed8; }
    .vw-pipeline-status.paused { background: #fef3c7; color: #92400e; }
    .vw-pipeline-status.completed { background: #dcfce7; color: #166534; }
    .vw-pipeline-status.failed { background: #fef2f2; color: #dc2626; }
    .vw-pipeline-status.pending { background: #f1f5f9; color: #64748b; }
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

                    {{-- Special: Fit to Skeleton node shows before/after comparison --}}
                    @if($nodeId === 'fit_to_skeleton' && $nodeStatus === 'completed')
                        @php
                            $skeletonOutput = null;
                            if ($this->activeExecutionId) {
                                $exec = \Modules\AppVideoWizard\Models\VwWorkflowExecution::find($this->activeExecutionId);
                                $skeletonOutput = $exec ? ($exec->getNodeResult($nodeId)['output'] ?? null) : null;
                            }
                        @endphp
                        @if($skeletonOutput)
                            {{-- Skeleton Type Badge --}}
                            <div class="vw-node-detail-section">
                                <div class="vw-node-detail-label">
                                    <i class="fa-solid fa-shapes"></i> {{ __('Detected Energy Type') }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="background: #dbeafe; color: #1d4ed8; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 0.3rem; font-weight: 700;">
                                        {{ $skeletonOutput['skeleton_type'] ?? 'Unknown' }}
                                    </span>
                                    <span style="color: #64748b; font-size: 0.7rem;">
                                        {{ ($skeletonOutput['original_word_count'] ?? '?') }} → {{ ($skeletonOutput['fitted_word_count'] ?? '?') }} words
                                    </span>
                                </div>
                            </div>

                            {{-- Before/After comparison --}}
                            <div class="vw-node-detail-section" x-data="{ showComparison: true }">
                                <div class="vw-node-detail-label">
                                    <i class="fa-solid fa-code-compare"></i> {{ __('Before / After') }}
                                    <button class="vw-node-btn" style="margin-left: auto;"
                                            @click="showComparison = !showComparison">
                                        <i class="fa-solid" :class="showComparison ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                <div x-show="showComparison" x-collapse>
                                    @if(!empty($skeletonOutput['original_prompt']))
                                        <div style="margin-bottom: 0.5rem;">
                                            <div style="font-size: 0.65rem; color: #ef4444; font-weight: 600; margin-bottom: 0.2rem;">
                                                <i class="fa-solid fa-minus"></i> ORIGINAL ({{ $skeletonOutput['original_word_count'] ?? '?' }} words)
                                            </div>
                                            <div class="vw-node-detail-content" style="border-color: #7f1d1d; max-height: 8rem; font-size: 0.7rem;">{{ $skeletonOutput['original_prompt'] }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($skeletonOutput['fitted_prompt']))
                                        <div>
                                            <div style="font-size: 0.65rem; color: #22c55e; font-weight: 600; margin-bottom: 0.2rem;">
                                                <i class="fa-solid fa-plus"></i> FITTED ({{ $skeletonOutput['fitted_word_count'] ?? '?' }} words)
                                            </div>
                                            <div class="vw-node-detail-content" style="border-color: #86efac; max-height: 8rem; font-size: 0.7rem;">{{ $skeletonOutput['fitted_prompt'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Special: Seedance Compliance Check node --}}
                    @if($nodeId === 'seedance_compliance' && in_array($nodeStatus, ['completed', 'failed']))
                        @php
                            $complianceOutput = null;
                            if ($this->activeExecutionId) {
                                $exec = \Modules\AppVideoWizard\Models\VwWorkflowExecution::find($this->activeExecutionId);
                                $complianceOutput = $exec ? ($exec->getNodeResult($nodeId)['output'] ?? null) : null;
                            }
                        @endphp
                        @if($complianceOutput)
                            {{-- Score Badge --}}
                            <div class="vw-node-detail-section">
                                <div class="vw-node-detail-label">
                                    <i class="fa-solid fa-shield-check"></i> {{ __('Compliance Score') }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    @php
                                        $score = $complianceOutput['score'] ?? 0;
                                        $violationCount = $complianceOutput['violation_count'] ?? 0;
                                        if ($score >= 80) {
                                            $scoreBg = '#dcfce7'; $scoreColor = '#166534'; $scoreLabel = 'PASS';
                                        } elseif ($score >= 50) {
                                            $scoreBg = '#fef3c7'; $scoreColor = '#92400e'; $scoreLabel = 'WARN';
                                        } else {
                                            $scoreBg = '#fef2f2'; $scoreColor = '#dc2626'; $scoreLabel = 'FAIL';
                                        }
                                    @endphp
                                    <span style="background: {{ $scoreBg }}; color: {{ $scoreColor }}; font-size: 0.85rem; padding: 0.25rem 0.7rem; border-radius: 0.3rem; font-weight: 700;">
                                        {{ $score }}/100 — {{ $scoreLabel }}
                                    </span>
                                    @if($violationCount > 0)
                                        <span style="color: #f87171; font-size: 0.7rem; font-weight: 600;">
                                            {{ $violationCount }} {{ $violationCount === 1 ? 'violation' : 'violations' }} fixed
                                        </span>
                                    @else
                                        <span style="color: #4ade80; font-size: 0.7rem; font-weight: 600;">
                                            No violations found
                                        </span>
                                    @endif
                                    <span style="color: #64748b; font-size: 0.7rem;">
                                        {{ ($complianceOutput['original_word_count'] ?? '?') }} → {{ ($complianceOutput['fixed_word_count'] ?? '?') }} words
                                    </span>
                                </div>
                                @if(!empty($complianceOutput['summary']))
                                    <div style="color: #94a3b8; font-size: 0.7rem; margin-top: 0.3rem;">
                                        {{ $complianceOutput['summary'] }}
                                    </div>
                                @endif
                            </div>

                            {{-- Violations List --}}
                            @if($violationCount > 0)
                                <div class="vw-node-detail-section" x-data="{ showViolations: false }">
                                    <div class="vw-node-detail-label">
                                        <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i> {{ __('Violations Found') }}
                                        <button class="vw-node-btn" style="margin-left: auto;"
                                                @click="showViolations = !showViolations">
                                            <i class="fa-solid" :class="showViolations ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <div x-show="showViolations" x-collapse>
                                        <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                            @foreach(($complianceOutput['violations'] ?? []) as $violation)
                                                <div style="background: rgba(127, 29, 29, 0.3); border: 1px solid rgba(248, 113, 113, 0.2); border-radius: 0.3rem; padding: 0.4rem 0.6rem; font-size: 0.7rem;">
                                                    <span style="color: #f87171; font-weight: 600;">{{ $violation['word'] ?? '?' }}</span>
                                                    <span style="color: #64748b;"> — {{ $violation['rule'] ?? '' }}</span>
                                                    @if(!empty($violation['fix']))
                                                        <span style="color: #4ade80;"> → {{ $violation['fix'] }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Before/After comparison --}}
                            <div class="vw-node-detail-section" x-data="{ showComparison: false }">
                                <div class="vw-node-detail-label">
                                    <i class="fa-solid fa-code-compare"></i> {{ __('Before / After') }}
                                    <button class="vw-node-btn" style="margin-left: auto;"
                                            @click="showComparison = !showComparison">
                                        <i class="fa-solid" :class="showComparison ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                <div x-show="showComparison" x-collapse>
                                    @if(!empty($complianceOutput['original_prompt']))
                                        <div style="margin-bottom: 0.5rem;">
                                            <div style="font-size: 0.65rem; color: #ef4444; font-weight: 600; margin-bottom: 0.2rem;">
                                                <i class="fa-solid fa-minus"></i> PRE-COMPLIANCE ({{ $complianceOutput['original_word_count'] ?? '?' }} words)
                                            </div>
                                            <div class="vw-node-detail-content" style="border-color: #7f1d1d; max-height: 8rem; font-size: 0.7rem;">{{ $complianceOutput['original_prompt'] }}</div>
                                        </div>
                                    @endif
                                    @if(!empty($complianceOutput['fixed_prompt']))
                                        <div>
                                            <div style="font-size: 0.65rem; color: #22c55e; font-weight: 600; margin-bottom: 0.2rem;">
                                                <i class="fa-solid fa-plus"></i> POST-COMPLIANCE ({{ $complianceOutput['fixed_word_count'] ?? '?' }} words)
                                            </div>
                                            <div class="vw-node-detail-content" style="border-color: #86efac; max-height: 8rem; font-size: 0.7rem;">{{ $complianceOutput['fixed_prompt'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Node Output (if completed) --}}
                    @if($hasOutput && $nodeStatus === 'completed' && !in_array($nodeId, ['fit_to_skeleton', 'seedance_compliance']))
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
