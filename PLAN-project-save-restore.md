# Video Wizard - Project Save/Restore Implementation Plan

## Current State Analysis

### What IS Being Saved
- Platform & Format settings (platform, aspect_ratio, format, target_duration, production_type, production_subtype)
- Concept data (rawInput, refinedConcept, keywords, etc.)
- Script (title, hook, scenes with all details, cta)
- Storyboard (scenes with images, imageModel, visualStyle, technicalSpecs, promptChain, styleBible)
- Animation (voiceover settings, scene animations)
- Assembly (transitions, music settings, caption settings)
- Step tracking (currentStep, maxReachedStep)
- Project metadata (name, status, user_id, team_id)

### What IS NOT Being Saved (DATA LOSS ON REFRESH)
1. **Scene Memory** - Character Bible, Location Bible definitions
2. **Multi-Shot Mode** - Decomposed scenes/shots
3. **Pending Async Jobs** - Not recovered on page load
4. **Concept Variations** - Generated alternatives lost

### Database Fields Available But Unused
- `character_intelligence` - Can repurpose for Character Bible
- `content_config` - Can repurpose for Scene Memory config
- `export_config` - For export preferences

---

## Implementation Plan

### Phase 1: Save Scene Memory Data (CRITICAL)

**Problem:** Character Bible and Location Bible are completely lost on page refresh.

**Solution:** Save `sceneMemory` to database using existing `content_config` field.

#### 1.1 Update saveProject() Method

```php
// In VideoWizard.php saveProject()
$data = [
    // ... existing fields ...
    'content_config' => [
        'sceneMemory' => $this->sceneMemory,
        'multiShotMode' => $this->multiShotMode,
        'conceptVariations' => $this->conceptVariations,
    ],
];
```

#### 1.2 Update loadProject() Method

```php
// In VideoWizard.php loadProject()
if ($project->content_config) {
    $config = $project->content_config;
    if (isset($config['sceneMemory'])) {
        $this->sceneMemory = array_merge($this->sceneMemory, $config['sceneMemory']);
    }
    if (isset($config['multiShotMode'])) {
        $this->multiShotMode = array_merge($this->multiShotMode, $config['multiShotMode']);
    }
    if (isset($config['conceptVariations'])) {
        $this->conceptVariations = $config['conceptVariations'];
    }
}
```

---

### Phase 2: Auto-Save on Critical Actions

**Problem:** Data only saves when navigating steps or explicitly calling saveProject().

**Solution:** Add auto-save triggers for data-changing operations.

#### 2.1 Add Auto-Save to Scene Memory Operations

Methods that need `$this->saveProject()` added:
- `addCharacter()` - After adding a character
- `updateCharacter()` - After editing a character
- `removeCharacter()` - After deleting a character
- `addLocation()` - After adding a location
- `updateLocation()` - After editing a location
- `removeLocation()` - After deleting a location
- `updateStyleBible()` - After style changes

#### 2.2 Add Debounced Auto-Save

For frequent changes, implement debounced saving:

```php
// Add property
public int $lastSaveTime = 0;

// Add method
protected function debouncedSave(int $delayMs = 2000): void
{
    $now = time() * 1000;
    if ($now - $this->lastSaveTime > $delayMs) {
        $this->saveProject();
        $this->lastSaveTime = $now;
    }
}
```

---

### Phase 3: Update URL with Project ID

**Problem:** After creating a new project, refreshing loses the project ID.

**Solution:** Update browser URL when project is created/saved.

#### 3.1 Dispatch Browser Event to Update URL

```php
// In saveProject() after creating new project
if (!$hadProjectId && $this->projectId) {
    $this->dispatch('update-url', projectId: $this->projectId);
}
```

#### 3.2 Add JavaScript Handler in View

```javascript
// In storyboard.blade.php or video-wizard.blade.php
document.addEventListener('livewire:init', () => {
    Livewire.on('update-url', ({ projectId }) => {
        const url = new URL(window.location);
        url.searchParams.set('project', projectId);
        window.history.replaceState({}, '', url);
    });
});
```

---

### Phase 4: Recover Pending Async Jobs

**Problem:** If user refreshes while HiDream is generating, job tracking is lost.

**Solution:** On mount, check for pending processing jobs and restore polling.

#### 4.1 Update mount() Method

```php
public function mount($project = null)
{
    if ($project instanceof WizardProject && $project->exists) {
        $this->loadProject($project);
        $this->recoverPendingJobs($project);
    }
}

protected function recoverPendingJobs(WizardProject $project): void
{
    $pendingJobs = WizardProcessingJob::where('project_id', $project->id)
        ->whereIn('status', ['pending', 'processing'])
        ->get();

    if ($pendingJobs->isNotEmpty()) {
        $this->pendingJobs = $pendingJobs->map(fn($job) => [
            'id' => $job->id,
            'type' => $job->type,
            'external_job_id' => $job->external_job_id,
            'input_data' => $job->input_data,
        ])->toArray();

        // Start polling for these jobs
        $this->dispatch('resume-job-polling');
    }
}
```

---

### Phase 5: Add "Save Project" Button to UI

**Problem:** Users have no explicit way to save their work.

**Solution:** Add visible save button with status indicator.

#### 5.1 Add Save Button to Header/Footer

```html
<!-- In storyboard.blade.php or main wizard view -->
<div class="vw-save-indicator" style="position: fixed; top: 1rem; right: 1rem; z-index: 100;">
    <button wire:click="saveProject"
            wire:loading.attr="disabled"
            wire:target="saveProject"
            class="vw-save-btn"
            title="{{ __('Save Project') }}">
        <span wire:loading.remove wire:target="saveProject">
            {{ $projectId ? '💾' : '➕' }} {{ __('Save') }}
        </span>
        <span wire:loading wire:target="saveProject">
            ⏳ {{ __('Saving...') }}
        </span>
    </button>
    @if($projectId)
        <span class="vw-project-id">ID: {{ $projectId }}</span>
    @endif
</div>
```

#### 5.2 Add CSS for Save Button

```css
.vw-save-btn {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 0.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.vw-save-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}
.vw-project-id {
    margin-left: 0.5rem;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
}
```

---

### Phase 6: Project List / Load Existing Projects

**Problem:** No easy way to see or load existing projects.

**Solution:** The route already exists (`/app/video-wizard/projects`), ensure controller method works.

#### 6.1 Verify projects() Controller Method

```php
// In AppVideoWizardController.php
public function projects()
{
    $projects = WizardProject::where('user_id', auth()->id())
        ->orderBy('updated_at', 'desc')
        ->paginate(12);

    return view('appvideowizard::projects', compact('projects'));
}
```

#### 6.2 Create Projects List View (if missing)

Create `/modules/AppVideoWizard/resources/views/projects.blade.php` with project cards showing:
- Project name
- Thumbnail (if available)
- Current step indicator
- Last modified date
- Continue / Delete buttons

---

## Files to Modify

| File | Changes |
|------|---------|
| `VideoWizard.php` | Update saveProject(), loadProject(), add recoverPendingJobs() |
| `storyboard.blade.php` | Add URL update JS, save button |
| `video-wizard.blade.php` | Add save button, project indicator |
| `AppVideoWizardController.php` | Verify projects() method |
| `projects.blade.php` | Create if missing |

---

## Implementation Order

1. **Phase 1** - Save sceneMemory/multiShotMode (prevents data loss)
2. **Phase 3** - Update URL with project ID (enables refresh)
3. **Phase 5** - Add Save button to UI (user control)
4. **Phase 2** - Auto-save on critical actions (safety net)
5. **Phase 4** - Recover pending jobs (async job recovery)
6. **Phase 6** - Project list view (management)

---

## Testing Checklist

- [ ] Create new project, refresh page - project should reload
- [ ] Add characters to Character Bible, refresh - characters persist
- [ ] Add locations to Location Bible, refresh - locations persist
- [ ] Start HiDream generation, refresh - job continues/recovers
- [ ] Generate script, refresh - script persists
- [ ] Generate images, refresh - images persist
- [ ] Edit visual style, refresh - settings persist
- [ ] Multi-shot decomposition, refresh - shots persist
