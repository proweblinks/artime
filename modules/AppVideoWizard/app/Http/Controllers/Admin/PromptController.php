<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Facades\AI;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AppVideoWizard\Models\VwPrompt;
use Modules\AppVideoWizard\Models\VwPromptHistory;
use Modules\AppVideoWizard\Services\PromptService;

class PromptController extends Controller
{
    protected PromptService $promptService;

    public function __construct(PromptService $promptService)
    {
        $this->promptService = $promptService;
    }

    /**
     * Display list of prompts.
     */
    public function index(Request $request)
    {
        $query = VwPrompt::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $prompts = $query->orderBy('name')->paginate(20);

        return view('appvideowizard::admin.prompts.index', compact('prompts'));
    }

    /**
     * Show create prompt form.
     */
    public function create()
    {
        return view('appvideowizard::admin.prompts.create');
    }

    /**
     * Store new prompt.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:100|unique:vw_prompts,slug',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prompt_template' => 'required|string',
            'variables' => 'nullable|array',
            'model' => 'required|string|max:100',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:100|max:100000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $prompt = VwPrompt::create($validated);

        session()->flash('success', 'Prompt created successfully.');

        return redirect()->route('admin.video-wizard.prompts.edit', $prompt);
    }

    /**
     * Show edit prompt form.
     */
    public function edit(VwPrompt $prompt)
    {
        $history = $prompt->history()->limit(10)->get();
        $placeholders = $prompt->getPlaceholders();

        return view('appvideowizard::admin.prompts.edit', compact('prompt', 'history', 'placeholders'));
    }

    /**
     * Update prompt.
     */
    public function update(Request $request, VwPrompt $prompt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prompt_template' => 'required|string',
            'variables' => 'nullable|array',
            'model' => 'required|string|max:100',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:100|max:100000',
            'is_active' => 'boolean',
            'change_notes' => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Check if prompt template changed
        if ($prompt->prompt_template !== $validated['prompt_template']) {
            // Create new version
            $prompt->createVersion(
                $validated,
                auth()->id(),
                $validated['change_notes'] ?? null
            );
        } else {
            // Just update without versioning
            $prompt->update($validated);
        }

        session()->flash('success', 'Prompt updated successfully.');

        return redirect()->route('admin.video-wizard.prompts.edit', $prompt);
    }

    /**
     * Delete prompt.
     */
    public function destroy(VwPrompt $prompt)
    {
        $prompt->delete();

        session()->flash('success', 'Prompt deleted successfully.');

        return redirect()->route('admin.video-wizard.prompts.index');
    }

    /**
     * Toggle prompt active status.
     */
    public function toggle(VwPrompt $prompt)
    {
        $prompt->update(['is_active' => !$prompt->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $prompt->is_active,
        ]);
    }

    /**
     * Show prompt version history.
     */
    public function history(VwPrompt $prompt)
    {
        $history = $prompt->history()->with('changedBy')->paginate(20);

        return view('appvideowizard::admin.prompts.history', compact('prompt', 'history'));
    }

    /**
     * Rollback to a previous version.
     */
    public function rollback(VwPrompt $prompt, int $version)
    {
        $success = $prompt->rollbackToVersion($version, auth()->id());

        if ($success) {
            session()->flash('success', "Rolled back to version {$version}.");
        } else {
            session()->flash('error', "Could not find version {$version}.");
        }

        return redirect()->route('admin.video-wizard.prompts.edit', $prompt);
    }

    /**
     * Test a prompt with sample data.
     */
    public function test(Request $request, VwPrompt $prompt)
    {
        $validated = $request->validate([
            'variables' => 'nullable|array',
        ]);

        try {
            $compiledPrompt = $prompt->compile($validated['variables'] ?? []);

            // Optionally call AI to test
            if ($request->boolean('call_ai')) {
                // Get team ID safely: prefer session, fallback to authenticated user's team
                $teamId = session('current_team_id');
                if ($teamId === null && auth()->check()) {
                    $teamId = auth()->user()->current_team_id ?? auth()->user()->team_id ?? 0;
                }
                $teamId = (int) ($teamId ?? 0);

                $result = AI::process($compiledPrompt, 'text', ['maxResult' => 1], $teamId);

                return response()->json([
                    'success' => true,
                    'compiled_prompt' => $compiledPrompt,
                    'ai_response' => $result['data'][0] ?? null,
                    'error' => $result['error'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'compiled_prompt' => $compiledPrompt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed default prompts.
     */
    public function seedDefaults()
    {
        $this->promptService->seedDefaultPrompts();

        session()->flash('success', 'Default prompts seeded successfully.');

        return redirect()->route('admin.video-wizard.prompts.index');
    }
}
