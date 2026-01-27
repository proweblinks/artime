# Phase 20: Component Splitting - Research

**Researched:** 2026-01-27
**Domain:** Livewire 3 component architecture, parent-child communication, modal extraction
**Confidence:** HIGH

## Summary

This research investigates patterns for splitting the massive VideoWizard component (32,331 lines, 431 public methods, 152 public properties) into manageable child components. The goal is extracting wizard steps and modals into separate Livewire components while maintaining existing functionality and state coordination.

Livewire 3's "island architecture" means each component operates independently with its own state. Parent-child communication requires explicit patterns: props for data down, events for data up, and special attributes (#[Reactive], #[Modelable], $parent) for state synchronization. The research confirms that extracting modals (Character Bible, Location Bible) is lower risk than step extraction due to their isolated state requirements.

**Primary recommendation:** Use PHP traits for code organization first, extract modals into child components second, defer step extraction to a later phase due to complex state interdependencies.

## Standard Stack

The established libraries/tools for this domain:

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| livewire/livewire | 3.6.4 | Component framework | Already installed, provides #[Reactive], #[Modelable], events |
| mhmiton/laravel-modules-livewire | ^3.0 | Module integration | Already installed, handles namespacing |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| wire-elements/modal | 2.x | Modal management | When extracting modals as standalone components |
| Alpine.js | 3.x | Client-side reactivity | Already integrated, use @entangle for sync |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Child Livewire components | Blade partials (@include) | No "live" functionality, but zero performance overhead |
| #[Reactive] props | Events | Events are more explicit but require more boilerplate |
| Full component extraction | PHP Traits | Traits keep state unified, components isolate performance |

**Installation:**
No new packages required - all tools already installed.

## Architecture Patterns

### Current Structure Analysis

```
VideoWizard.php (32,331 lines)
├── 152 public properties (serialized every request)
├── 431 public methods
├── 7 wizard steps (inline via @include)
├── 12+ modals (inline via @include)
├── Complex nested arrays: $storyboard, $sceneMemory, $script
└── Phase 19 optimizations: #[Locked], #[Computed], file-based image storage
```

### Recommended Extraction Strategy (Priority Order)

**Phase A: Traits for Code Organization (LOW RISK)**
```
app/Livewire/Traits/
├── WithCharacterBible.php     # Character management methods
├── WithLocationBible.php      # Location management methods
├── WithScriptGeneration.php   # Script methods
├── WithStoryboard.php         # Image generation methods
├── WithAnimation.php          # Video generation methods
├── WithMultiShot.php          # Shot decomposition methods
└── WithProjectManager.php     # Project CRUD methods
```

**Phase B: Modal Child Components (MEDIUM RISK)**
```
app/Livewire/Modals/
├── CharacterBibleModal.php    # Extracted from parent
└── LocationBibleModal.php     # Extracted from parent
```

**Phase C: Step Child Components (HIGH RISK - DEFER)**
```
app/Livewire/Steps/
├── ConceptStep.php            # Requires $concept, services
├── ScriptStep.php             # Requires $script, $sceneMemory, services
├── StoryboardStep.php         # Most complex - 6,922 line blade
└── ... (7 total)
```

### Pattern 1: PHP Traits for Method Organization

**What:** Extract related methods into traits while keeping all state in parent
**When to use:** Large components where methods can be logically grouped but share state
**Example:**
```php
// Source: https://livewire.laravel.com/docs/2.x/traits
// Source: https://www.csrhymes.com/2020/12/01/splitting-a-large-livewire-component.html

// app/Livewire/Traits/WithCharacterBible.php
trait WithCharacterBible
{
    // Trait-specific mount hook (auto-called by Livewire)
    public function mountWithCharacterBible()
    {
        // Initialize character bible state
    }

    // Character Bible methods extracted from VideoWizard
    public function addCharacter(): void
    {
        $this->sceneMemory['characterBible']['characters'][] = [
            'id' => uniqid('char_'),
            'name' => '',
            // ...
        ];
    }

    public function openCharacterBibleModal(): void
    {
        $this->showCharacterBibleModal = true;
    }

    public function closeCharacterBibleModal(): void
    {
        $this->showCharacterBibleModal = false;
        $this->buildSceneDNA();
        $this->saveProject();
    }

    // ... 50+ character-related methods
}

// app/Livewire/VideoWizard.php
class VideoWizard extends Component
{
    use WithCharacterBible;
    use WithLocationBible;
    use WithScriptGeneration;
    // ... other traits
}
```

**Validation rules in traits:**
```php
// Source: https://www.csrhymes.com/2020/12/01/splitting-a-large-livewire-component.html
trait WithCharacterBible
{
    protected array $characterBibleRules = [
        'sceneMemory.characterBible.characters.*.name' => 'required|string|max:100',
    ];
}

// In main component
public function __construct()
{
    $this->rules = array_merge(
        $this->rules,
        $this->characterBibleRules
    );
    parent::__construct();
}
```

### Pattern 2: Modal Child Component with #[Modelable]

**What:** Extract modal into separate Livewire component with two-way binding to parent
**When to use:** Modals with isolated editing that sync back to parent on close
**Example:**
```php
// Source: https://livewire.laravel.com/docs/3.x/nesting

// app/Livewire/Modals/CharacterBibleModal.php
class CharacterBibleModal extends Component
{
    // Two-way binding with parent
    #[Modelable]
    public array $characters = [];

    // Modal visibility (parent controls)
    public bool $show = false;

    // Local editing state
    public int $editingIndex = 0;

    // Local-only methods
    public function addCharacter(): void
    {
        $this->characters[] = [
            'id' => uniqid('char_'),
            'name' => '',
            // ...
        ];
    }

    public function close(): void
    {
        $this->dispatch('character-bible-closed');
    }

    public function render()
    {
        return view('appvideowizard::livewire.modals.character-bible');
    }
}

// In parent VideoWizard blade:
<livewire:appvideowizard::modals.character-bible-modal
    wire:model="sceneMemory.characterBible.characters"
    :show="$showCharacterBibleModal"
    :key="'character-bible-' . count($sceneMemory['characterBible']['characters'])"
/>

// Parent listens for close event
#[On('character-bible-closed')]
public function handleCharacterBibleClosed(): void
{
    $this->showCharacterBibleModal = false;
    $this->buildSceneDNA();
    $this->saveProject();
}
```

### Pattern 3: Step Child Component with #[Reactive] Props

**What:** Extract wizard step as child with reactive data flow from parent
**When to use:** Steps that primarily display/edit parent data
**Example:**
```php
// Source: https://livewire.laravel.com/docs/3.x/nesting

// app/Livewire/Steps/ConceptStep.php
class ConceptStep extends Component
{
    // Reactive props from parent (read + auto-update)
    #[Reactive]
    public array $concept = [];

    #[Reactive]
    public array $suggestedSettings = [];

    // Methods dispatch events to parent
    public function enhanceConcept(): void
    {
        $this->dispatch('enhance-concept', concept: $this->concept);
    }

    public function generateIdeas(): void
    {
        $this->dispatch('generate-ideas', concept: $this->concept);
    }

    public function render()
    {
        return view('appvideowizard::livewire.steps.concept');
    }
}

// In parent VideoWizard blade:
@if($currentStep === 2)
    <livewire:appvideowizard::steps.concept-step
        :concept="$concept"
        :suggestedSettings="$suggestedSettings"
        :key="'concept-step'"
    />
@endif

// Parent handles events
#[On('enhance-concept')]
public function handleEnhanceConcept(array $concept): void
{
    $this->concept = $concept;
    // Call service, etc.
}
```

### Pattern 4: Direct Parent Access via $parent

**What:** Child component calls parent methods directly
**When to use:** Simple operations where event indirection adds unnecessary complexity
**Example:**
```php
// Source: https://livewire.laravel.com/docs/3.x/nesting

// In child component blade:
<button wire:click="$parent.saveProject()">
    Save
</button>

<button wire:click="$parent.buildSceneDNA()">
    Rebuild DNA
</button>
```

### Anti-Patterns to Avoid

- **Over-nesting:** Creating child components for everything. Ask: "Does this need to be live?" If not, use Blade @include
- **Reactive prop abuse:** Adding #[Reactive] to large arrays causes excessive network traffic
- **Tight coupling via $parent:** Reduces child reusability, use events for cross-component communication
- **State duplication:** Don't copy parent arrays to child - use #[Modelable] or events
- **Missing wire:key:** Always provide unique keys in loops: `:key="'modal-' . $id"`

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Modal state management | Custom show/hide logic | wire-elements/modal or @entangle | Handles animations, stacking, escape key |
| Parent-child sync | Manual event juggling | #[Modelable] attribute | Two-way binding built-in |
| Reactive props | Manual re-rendering | #[Reactive] attribute | Framework handles reactivity |
| Lifecycle coordination | Manual mount ordering | Livewire trait hooks (mountTraitName) | Auto-called by framework |
| Cross-component state | Global state stores | Events + parent ownership | Livewire designed for event-driven |

**Key insight:** Livewire 3 provides dedicated APIs (#[Reactive], #[Modelable], $parent) specifically because manual state sync between components is error-prone. Use the framework's patterns.

## Common Pitfalls

### Pitfall 1: Double Server Requests

**What goes wrong:** Child component update triggers its own request, then emits event that triggers parent request
**Why it happens:** Default event dispatching is server-side, causing two round trips
**How to avoid:** Use client-side dispatch: `$dispatch('event-name', { data })` instead of `$this->dispatch()`
**Warning signs:** Noticeable lag on child interactions, two network requests in DevTools

### Pitfall 2: Stale Reactive Props

**What goes wrong:** Child component's #[Reactive] props don't update when parent changes
**Why it happens:** Only parent's own updates trigger child re-render, not sibling updates
**How to avoid:** Ensure parent re-renders when data changes, or use #[Modelable] for two-way
**Warning signs:** Child shows old data after parent method runs

### Pitfall 3: Massive Serialization Payload

**What goes wrong:** Extracting components doesn't reduce payload if child still receives full arrays
**Why it happens:** #[Reactive] on large arrays serializes entire array to child
**How to avoid:** Pass only needed data, use lazy loading (Phase 19 pattern), use #[Locked] on read-only
**Warning signs:** Slow component loads, large wire:snapshot in HTML

### Pitfall 4: Lost State on Re-render

**What goes wrong:** Child component state resets when parent re-renders
**Why it happens:** Missing or inconsistent wire:key causes Livewire to recreate component
**How to avoid:** Always provide stable, unique key: `:key="'child-' . $uniqueId"`
**Warning signs:** Form inputs clear unexpectedly, modal closes on parent update

### Pitfall 5: Validation Rule Conflicts in Traits

**What goes wrong:** Multiple traits define $rules, only last one wins
**Why it happens:** PHP property override behavior
**How to avoid:** Define trait-specific rule arrays, merge in component constructor
**Warning signs:** Validation passes when it shouldn't, missing error messages

### Pitfall 6: Breaking Phase 19 Optimizations

**What goes wrong:** Extracted components re-introduce base64 in state, remove #[Locked]
**Why it happens:** Copy-paste code without Phase 19 patterns
**How to avoid:** Follow Phase 19 patterns: file-based storage, lazy loading, #[Locked] on read-only
**Warning signs:** Payload size increases after extraction

## Code Examples

Verified patterns from official sources:

### Trait Lifecycle Hooks
```php
// Source: https://livewire.laravel.com/docs/3.x/lifecycle-hooks
trait WithCharacterBible
{
    // Auto-called: mount + TraitName
    public function mountWithCharacterBible()
    {
        // Initialize state
    }

    // Auto-called: updated + TraitName
    public function updatedWithCharacterBible($value, $key)
    {
        // Handle updates
    }

    // Auto-called: rendering + TraitName
    public function renderingWithCharacterBible()
    {
        // Before render
    }
}
```

### #[Modelable] Two-Way Binding
```php
// Source: https://livewire.laravel.com/docs/3.x/nesting

// Child component
use Livewire\Attributes\Modelable;

class CharacterBibleModal extends Component
{
    #[Modelable]
    public array $characters = [];
}

// Parent blade
<livewire:character-bible-modal wire:model="sceneMemory.characterBible.characters" />
```

### Client-Side Event Dispatch (Recommended)
```php
// Source: https://livewire.laravel.com/docs/3.x/nesting

// In child blade (ONE server request)
<button wire:click="$dispatch('remove-character', { index: {{ $index }} })">
    Remove
</button>

// Parent PHP
#[On('remove-character')]
public function handleRemoveCharacter(int $index): void
{
    unset($this->sceneMemory['characterBible']['characters'][$index]);
    $this->sceneMemory['characterBible']['characters'] = array_values(
        $this->sceneMemory['characterBible']['characters']
    );
}
```

### @entangle for Alpine.js Sync
```php
// Source: https://livewire.laravel.com/docs/3.x/alpine

// In blade
<div x-data="{ show: @entangle('showCharacterBibleModal') }">
    <div x-show="show" x-transition>
        <!-- Modal content -->
    </div>
</div>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| $emit / $emitUp | $dispatch / #[On] | Livewire 3.0 | Unified event syntax |
| $this->emit() | $this->dispatch() | Livewire 3.0 | Server-side events |
| wire:model | wire:model.live / wire:model.blur | Livewire 3.0 | Explicit update timing |
| Nested $refresh | #[Reactive] attribute | Livewire 3.0 | Declarative reactivity |
| Manual prop binding | #[Modelable] attribute | Livewire 3.0 | Built-in two-way sync |

**Deprecated/outdated:**
- `$emit`, `$emitUp`, `$emitSelf`: Use `$dispatch` instead
- `protected $listeners`: Use `#[On('event')]` attribute instead
- `wire:model` without modifier: Now requires `.live`, `.blur`, or `.change`

## Risk Assessment

### Modal Extraction (MEDIUM RISK)

**CharacterBibleModal:**
- Current methods: ~50 in VideoWizard
- Properties: `$showCharacterBibleModal`, `$editingCharacterIndex`, part of `$sceneMemory`
- Dependencies: `buildSceneDNA()`, `saveProject()`, `ReferenceImageStorageService`
- Blade size: 763 lines
- **Risk factors:** Image upload/generation callbacks, trait management, scene assignment toggles
- **Mitigation:** Keep parent callback methods, emit events from child

**LocationBibleModal:**
- Current methods: ~40 in VideoWizard
- Properties: `$showLocationBibleModal`, `$editingLocationIndex`, part of `$sceneMemory`
- Dependencies: `buildSceneDNA()`, `saveProject()`, `ReferenceImageStorageService`
- Blade size: 538 lines
- **Risk factors:** Scene-location one-to-one enforcement, image generation
- **Mitigation:** Same as CharacterBible

### Step Extraction (HIGH RISK - DEFER)

**Why defer:**
1. Deep state interdependencies ($script, $storyboard, $sceneMemory all interconnected)
2. Complex service orchestration (ConceptService, ScriptGenerationService, etc.)
3. Progressive generation state machine spans multiple steps
4. Multi-shot decomposition touches multiple property trees
5. Phase 19 optimizations heavily coupled to single component

**Recommended approach:**
1. Use traits for code organization (Phase 20A)
2. Extract modals as child components (Phase 20B)
3. Evaluate step extraction in future phase after traits stabilize

### Trait Extraction (LOW RISK)

**Why low risk:**
1. No state changes - just code organization
2. All tests continue passing
3. Blade templates unchanged
4. Reversible with simple code moves

## Open Questions

Things that couldn't be fully resolved:

1. **#[Modelable] with nested array paths**
   - What we know: Works with simple arrays
   - What's unclear: Does `wire:model="sceneMemory.characterBible.characters"` work?
   - Recommendation: Test with simple extraction first, fallback to events if needed

2. **Trait method count impact on performance**
   - What we know: PHP traits are zero-cost at runtime
   - What's unclear: Does Livewire reflection slow down with 20+ traits?
   - Recommendation: Benchmark with 5 traits first, monitor request time

3. **wire-elements/modal compatibility with existing modals**
   - What we know: Package provides modal infrastructure
   - What's unclear: Migration path from inline modals to package
   - Recommendation: Consider for new modals, migrate existing gradually

## Sources

### Primary (HIGH confidence)
- [Livewire 3.x Nesting Components](https://livewire.laravel.com/docs/3.x/nesting) - Props, #[Reactive], #[Modelable], events
- [Livewire Understanding Nesting](https://livewire.laravel.com/docs/understanding-nesting) - Island architecture, performance implications
- [Livewire 3.x Lifecycle Hooks](https://livewire.laravel.com/docs/3.x/lifecycle-hooks) - Trait-specific hooks

### Secondary (MEDIUM confidence)
- [GitHub Discussion #5555](https://github.com/livewire/livewire/discussions/5555) - Maintainer guidance on splitting components
- [C.S. Rhymes: Splitting Large Components](https://www.csrhymes.com/2020/12/01/splitting-a-large-livewire-component.html) - Trait patterns
- [Fly.io Laravel Bytes: Modelable](https://fly.io/laravel-bytes/modelable-events-data-livewire/) - #[Modelable] patterns

### Tertiary (LOW confidence)
- [wire-elements/modal](https://github.com/wire-elements/modal) - Modal package (not yet verified in this codebase)
- [Medium: Speed Up Livewire](https://medium.com/@thenibirahmed/speed-up-livewire-v3-the-only-guide-you-need-32fe73338098) - Performance tips

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Already installed, version verified
- Architecture patterns: HIGH - Official Livewire documentation verified
- Pitfalls: MEDIUM - Based on community discussions and maintainer guidance
- Risk assessment: HIGH - Based on direct codebase analysis

**Research date:** 2026-01-27
**Valid until:** 2026-02-27 (30 days - stable framework, patterns unlikely to change)

---

## Appendix: Current VideoWizard Analysis

### Component Statistics
- **Total lines:** 32,331
- **Public methods:** 431
- **Public properties:** 152
- **Constants:** 5 major arrays (AI_MODEL_TIERS, VISUAL_MODES, etc.)

### Method Distribution by Domain
| Domain | Estimated Methods | Extraction Priority |
|--------|-------------------|---------------------|
| Character Bible | ~50 | HIGH (trait + component) |
| Location Bible | ~40 | HIGH (trait + component) |
| Script Generation | ~60 | MEDIUM (trait only) |
| Storyboard/Images | ~80 | MEDIUM (trait only) |
| Animation/Video | ~70 | MEDIUM (trait only) |
| Multi-Shot | ~50 | MEDIUM (trait only) |
| Project Management | ~30 | LOW (trait only) |
| Utility/Helpers | ~50 | LOW (trait only) |

### Blade View Sizes
| View | Lines | Complexity |
|------|-------|------------|
| storyboard.blade.php | 6,922 | VERY HIGH |
| script.blade.php | 2,973 | HIGH |
| animation.blade.php | 2,676 | HIGH |
| character-bible.blade.php | 763 | MEDIUM |
| location-bible.blade.php | 538 | MEDIUM |

### Phase 19 Optimizations to Preserve
1. `#[Locked]` on 8 read-only properties
2. `#[Computed]` on 5 derived methods
3. `wire:model.blur` on 58 textareas
4. `ReferenceImageStorageService` for Base64 storage
5. `debouncedBuildSceneDNA` with 2-second threshold
