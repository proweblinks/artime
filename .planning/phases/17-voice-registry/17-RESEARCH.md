# Phase 17: Voice Registry - Research

**Researched:** 2026-01-25
**Domain:** Voice assignment tracking and consistency management in Laravel/PHP
**Confidence:** HIGH

## Summary

Phase 17 implements a Voice Registry as a single source of truth for voice assignments across narrator, internal thought, and character dialogue. The research reveals that the existing `validateVoiceContinuity()` method (VOC-04) already implements the core tracking logic needed - it uses a `$characterVoices` array with first-occurrence-wins behavior. This can be extracted and enhanced into a standalone registry class.

The codebase follows a Service Pattern architecture with service classes in `modules/AppVideoWizard/app/Services/`. The registry should be implemented as a simple stateful class (not a traditional Registry Pattern with global state) that wraps existing voice lookup methods (`getNarratorVoice()` and `getVoiceForCharacterName()`) rather than replacing them.

Key insight: This is **refactoring for consistency**, not new functionality. The existing fallback chains work well - the registry adds a tracking layer to ensure "first assigned voice wins" across all speech types.

**Primary recommendation:** Create `VoiceRegistryService.php` with initialization from Character Bible, wrapper methods for voice lookup, and non-blocking validation logging. Integration at `decomposeAllScenes()` ensures registry is populated before any shots are created.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.1+ | Language runtime | Required by Laravel, supports readonly properties |
| Laravel | 11.x | Framework | Project's framework - provides logging, facades, DI |
| Livewire | 3.x | Component system | VideoWizard is Livewire component |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Illuminate\Support\Facades\Log | Laravel 11.x | Logging | Non-blocking validation warnings (VOC-04 pattern) |
| Illuminate\Support\Facades\Cache | Laravel 11.x | Optional caching | If voice lookups become performance bottleneck |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Service class | Livewire property | No separation of concerns, harder to test |
| Service class | Trait | Violates single responsibility, can't be instantiated independently |
| Simple class | Registry Pattern (global state) | Adds unnecessary complexity, harder to test, anti-pattern in modern PHP |

**Installation:**
```bash
# No external dependencies needed - uses Laravel built-ins
```

## Architecture Patterns

### Recommended Project Structure
```
modules/AppVideoWizard/app/Services/
├── VoiceRegistryService.php    # New: Voice tracking registry
├── VideoWizard.php              # Modified: Integration point
└── [other services]             # Unchanged
```

### Pattern 1: Stateful Service Class (Not Registry Pattern)

**What:** Simple PHP class that maintains state during request lifecycle. NOT a traditional Registry Pattern with global state/singletons.

**When to use:** When you need to track state across multiple operations within a single request, particularly for validation or consistency checking.

**Example from existing codebase:**
```php
// Source: ShotContinuityService.php pattern
namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

class VoiceRegistryService
{
    /**
     * Registered voices: character name => voice data
     * First-occurrence-wins: once registered, voice doesn't change
     */
    protected array $characterVoices = [];
    protected ?string $narratorVoiceId = null;
    protected ?string $internalVoiceId = null;

    /**
     * Initialize registry with voices from Character Bible.
     * Called at start of decomposeAllScenes().
     */
    public function initializeFromCharacterBible(array $characterBible, string $narratorVoice): void
    {
        // Pre-seed character voices
        foreach ($characterBible['characters'] ?? [] as $char) {
            $name = $char['name'] ?? null;
            $voiceId = $char['voice']['id'] ?? null;

            if ($name && $voiceId) {
                $this->registerCharacterVoice($name, $voiceId, 'character_bible');
            }
        }

        // Set narrator voice
        $this->narratorVoiceId = $narratorVoice;

        Log::info('Voice registry initialized', [
            'charactersRegistered' => count($this->characterVoices),
            'narratorVoice' => $narratorVoice,
        ]);
    }

    /**
     * Register a character's voice (first-occurrence-wins).
     */
    protected function registerCharacterVoice(string $characterName, string $voiceId, string $source): bool
    {
        $key = strtoupper(trim($characterName));

        if (isset($this->characterVoices[$key])) {
            // Already registered - check for mismatch
            if ($this->characterVoices[$key]['voiceId'] !== $voiceId) {
                Log::warning('Voice registry mismatch detected', [
                    'character' => $characterName,
                    'registered' => $this->characterVoices[$key]['voiceId'],
                    'attempted' => $voiceId,
                    'source' => $source,
                ]);
                return false;
            }
            return true; // Already registered with same voice
        }

        // First occurrence - register
        $this->characterVoices[$key] = [
            'voiceId' => $voiceId,
            'source' => $source,
        ];

        return true;
    }
}
```

### Pattern 2: Wrapper Methods for Existing Lookups

**What:** Registry wraps existing voice lookup methods rather than replacing them, preserving well-tested fallback chains.

**When to use:** During migration/refactoring when existing logic is stable and you want to add a consistency layer.

**Example:**
```php
/**
 * Get voice for character, using registry if available, falling back to existing logic.
 * First-occurrence-wins: once a character's voice is registered, it doesn't change.
 */
public function getVoiceForCharacter(
    string $characterName,
    callable $fallbackLookup
): string {
    $key = strtoupper(trim($characterName));

    // Check registry first
    if (isset($this->characterVoices[$key])) {
        return $this->characterVoices[$key]['voiceId'];
    }

    // Not in registry - use existing fallback logic
    $voiceId = $fallbackLookup($characterName);

    // Register for future lookups (first-occurrence-wins)
    $this->registerCharacterVoice($characterName, $voiceId, 'runtime_lookup');

    return $voiceId;
}

// Usage in VideoWizard.php:
// Before: $voice = $this->getVoiceForCharacterName($speaker);
// After:  $voice = $this->voiceRegistry->getVoiceForCharacter(
//             $speaker,
//             fn($name) => $this->getVoiceForCharacterName($name)
//         );
```

### Pattern 3: Non-Blocking Validation (VOC-04 Pattern)

**What:** Validation that logs warnings but doesn't halt execution. Allows generation to continue while tracking issues.

**When to use:** When consistency is important but not critical enough to block user workflows.

**Example from existing code:**
```php
// Source: VideoWizard.php line 8625 (validateVoiceContinuity)
protected function validateVoiceContinuity(array $scenes): array
{
    $characterVoices = [];  // Track: character => first assigned voice
    $mismatches = [];

    foreach ($scenes as $sceneIndex => $scene) {
        // ... loop through shots ...

        if (!isset($characterVoices[$speakerKey])) {
            // First occurrence - register
            $characterVoices[$speakerKey] = ['voiceId' => $voiceId, ...];
        } else {
            // Check for mismatch
            $expected = $characterVoices[$speakerKey]['voiceId'];
            if ($voiceId !== $expected) {
                $mismatches[] = [...];
                Log::warning('Voice continuity mismatch detected (VOC-04)', [...]);
                // NOTE: Continues execution - non-blocking
            }
        }
    }

    return ['valid' => empty($mismatches), 'characterVoices' => $characterVoices, ...];
}
```

### Pattern 4: Service Class Instantiation

**What:** Services are instantiated inline when needed, not via dependency injection or singleton pattern.

**When to use:** For stateful services that need fresh state per operation.

**Example from existing code:**
```php
// Source: VideoWizard.php line 20383
$service = new ShotIntelligenceService();
$service->setProgressionService(new ShotProgressionService());

// For VoiceRegistry - instantiate at start of decomposeAllScenes():
$this->voiceRegistry = new VoiceRegistryService();
$this->voiceRegistry->initializeFromCharacterBible(
    $this->sceneMemory['characterBible'] ?? [],
    $this->getNarratorVoice()
);
```

### Anti-Patterns to Avoid

- **Global Registry Pattern:** Don't use static methods, singletons, or global state. Registry should be instance-based and passed/stored as needed.
- **Replacing Existing Lookups:** Don't rewrite `getNarratorVoice()` or `getVoiceForCharacterName()`. Wrap them instead to preserve fallback logic.
- **Over-engineering:** Don't add caching, events, or database persistence unless proven necessary. Keep it simple.
- **Blocking Validation:** Don't throw exceptions on voice mismatches. Log warnings and continue (VOC-04 pattern).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Voice assignment logic | Custom resolution algorithm | Existing `getVoiceForCharacterName()` | Already handles Bible → gender → hash fallback chain |
| Narrator voice lookup | New narrator resolution | Existing `getNarratorVoice()` | Already handles Bible → animation.narrator.voice → 'nova' |
| Voice continuity tracking | New validation system | Extract/enhance `validateVoiceContinuity()` | Already implements first-occurrence-wins, just needs refactoring |
| Logging | Custom logging | `Illuminate\Support\Facades\Log` | Laravel's logging facade, already used throughout codebase |
| String normalization | Custom case handling | `strtoupper(trim($name))` | Existing pattern used in VOC-04, ensures consistency |

**Key insight:** The codebase already has all the pieces - `validateVoiceContinuity()` implements the registry logic, just applied post-hoc. Phase 17 moves this logic earlier (initialization + runtime) rather than later (post-validation).

## Common Pitfalls

### Pitfall 1: Treating Registry as Global State

**What goes wrong:** Implementing VoiceRegistry as singleton or with static methods creates shared mutable state across requests.

**Why it happens:** Traditional Registry Pattern uses global state. Modern PHP advice explicitly warns against this.

**How to avoid:**
- Make VoiceRegistry a normal class with instance properties
- Instantiate fresh instance at start of `decomposeAllScenes()`
- Store instance in `$this->voiceRegistry` (Livewire component property)
- Let Livewire's session persistence handle state between requests

**Warning signs:**
- Using `static` properties or methods
- Using `Cache::rememberForever()` for voice mappings
- Accessing registry via `app()->make()` or service container

### Pitfall 2: Breaking Existing Fallback Chains

**What goes wrong:** Replacing `getNarratorVoice()` or `getVoiceForCharacterName()` instead of wrapping them causes voices to change unexpectedly.

**Why it happens:** Fallback chains are complex (Bible → gender → hash for characters, Bible → animation config → 'nova' for narrator). Easy to miss edge cases.

**How to avoid:**
- Use wrapper pattern: registry calls existing methods when voice not registered
- Test edge cases: character not in Bible, narrator without Bible entry, legacy voice format
- Preserve the "first call wins" behavior by registering result of fallback lookup

**Warning signs:**
- Duplicate voice resolution logic in VoiceRegistry
- Tests failing for characters not in Bible
- Narrator voice different than before

### Pitfall 3: Initialization Order Issues

**What goes wrong:** Registry used before initialization, or Character Bible not yet populated when registry initializes.

**Why it happens:** `decomposeAllScenes()` is complex with many steps. Easy to initialize registry too early or too late.

**How to avoid:**
- Initialize registry at very start of `decomposeAllScenes()`, after Character Bible is confirmed loaded
- Check `$this->sceneMemory['characterBible']` exists before initialization
- Log initialization for debugging
- Don't rely on registry in methods called before `decomposeAllScenes()`

**Warning signs:**
- Null pointer errors accessing `$this->voiceRegistry`
- Registry shows 0 characters registered when Bible has characters
- Different voices in shots decomposed early vs. late

### Pitfall 4: Not Handling Legacy Data Formats

**What goes wrong:** Registry expects `$char['voice']['id']` but legacy data has `$char['voice']` as string.

**Why it happens:** Code comment at line 23366 shows "Legacy string" handling - old data format still exists.

**How to avoid:**
- Check both `is_array($char['voice'])` and `is_string($char['voice'])` formats
- Extract voice ID using same logic as `getVoiceForCharacterName()`
- Test with both old and new project data

**Warning signs:**
- Characters with legacy voice format not appearing in registry
- Voice assignments working for new projects but not old ones

### Pitfall 5: Scope Confusion - Narrator vs. Internal Voice

**What goes wrong:** Treating narrator and internal thought as same voice, or not tracking them separately.

**Why it happens:** Both are voiceover (non-lip-sync), but they serve different purposes and can have different voices.

**How to avoid:**
- Registry has separate `$narratorVoiceId` and `$internalVoiceId` properties
- Narrator voice: from `getNarratorVoice()` (narrator character OR global setting)
- Internal voice: from character speaking (via `getVoiceForCharacterName()`)
- See VOC-04 validation - tracks them separately (line 8675 vs line 8713)

**Warning signs:**
- Internal thoughts using narrator voice when character has different voice
- Registry only tracking one "voiceover" voice instead of two types

## Code Examples

### Example 1: VoiceRegistryService Class Structure

```php
// Source: New file - modules/AppVideoWizard/app/Services/VoiceRegistryService.php
<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * Voice Registry Service - Phase 17: Voice Registry
 *
 * Single source of truth for voice assignments across narrator, internal, and character voices.
 * Implements first-occurrence-wins: once a voice is assigned to a character, it stays consistent.
 *
 * Integration: Initialize at start of decomposeAllScenes(), use for all voice lookups.
 */
class VoiceRegistryService
{
    /**
     * Character voices: character name (uppercase) => voice data
     * First-occurrence-wins: once set, doesn't change
     */
    protected array $characterVoices = [];

    /**
     * Narrator voice ID (from getNarratorVoice fallback chain)
     */
    protected ?string $narratorVoiceId = null;

    /**
     * Internal thought voice ID (typically same as speaking character)
     */
    protected ?string $internalVoiceId = null;

    /**
     * Initialize registry from Character Bible and narrator settings.
     * Called at start of decomposeAllScenes().
     *
     * @param array $characterBible The sceneMemory['characterBible'] array
     * @param string $narratorVoice From getNarratorVoice()
     */
    public function initializeFromCharacterBible(array $characterBible, string $narratorVoice): void
    {
        $registered = 0;

        // Pre-seed character voices from Bible
        foreach ($characterBible['characters'] ?? [] as $char) {
            $name = $char['name'] ?? null;

            // Handle both new array format and legacy string format
            $voiceId = null;
            if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
                $voiceId = $char['voice']['id'];
            } elseif (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
                $voiceId = $char['voice']; // Legacy format
            }

            if ($name && $voiceId) {
                $this->registerCharacterVoice($name, $voiceId, 'character_bible');
                $registered++;
            }
        }

        // Set narrator voice
        $this->narratorVoiceId = $narratorVoice;

        Log::info('Voice registry initialized (Phase 17)', [
            'charactersRegistered' => $registered,
            'narratorVoice' => $narratorVoice,
        ]);
    }

    /**
     * Register a character's voice (first-occurrence-wins).
     *
     * @param string $characterName Character name
     * @param string $voiceId Voice ID to register
     * @param string $source Where assignment came from (for debugging)
     * @return bool True if registered, false if mismatch detected
     */
    protected function registerCharacterVoice(string $characterName, string $voiceId, string $source): bool
    {
        $key = strtoupper(trim($characterName));

        if (isset($this->characterVoices[$key])) {
            // Already registered - check for mismatch
            if ($this->characterVoices[$key]['voiceId'] !== $voiceId) {
                Log::warning('Voice registry mismatch detected (Phase 17)', [
                    'character' => $characterName,
                    'registered' => $this->characterVoices[$key]['voiceId'],
                    'attempted' => $voiceId,
                    'registeredSource' => $this->characterVoices[$key]['source'],
                    'attemptedSource' => $source,
                ]);
                return false;
            }
            return true; // Already registered with same voice
        }

        // First occurrence - register
        $this->characterVoices[$key] = [
            'voiceId' => $voiceId,
            'source' => $source,
        ];

        Log::debug('Voice registered (Phase 17)', [
            'character' => $characterName,
            'voiceId' => $voiceId,
            'source' => $source,
        ]);

        return true;
    }

    /**
     * Get voice for character, using registry or fallback lookup.
     * First-occurrence-wins: once registered, voice doesn't change.
     *
     * @param string $characterName Character name
     * @param callable $fallbackLookup Function to call if not in registry
     * @return string Voice ID
     */
    public function getVoiceForCharacter(string $characterName, callable $fallbackLookup): string
    {
        $key = strtoupper(trim($characterName));

        // Check registry first
        if (isset($this->characterVoices[$key])) {
            return $this->characterVoices[$key]['voiceId'];
        }

        // Not in registry - use fallback (existing getVoiceForCharacterName)
        $voiceId = $fallbackLookup($characterName);

        // Register for future lookups (first-occurrence-wins)
        $this->registerCharacterVoice($characterName, $voiceId, 'runtime_lookup');

        return $voiceId;
    }

    /**
     * Get narrator voice ID.
     *
     * @return string Voice ID
     */
    public function getNarratorVoice(): string
    {
        return $this->narratorVoiceId;
    }

    /**
     * Get or set internal thought voice.
     * Internal voice typically matches the thinking character's voice.
     *
     * @param string|null $voiceId Voice to set (null to just get)
     * @return string Current voice ID
     */
    public function getInternalVoice(?string $voiceId = null): string
    {
        if ($voiceId !== null && $this->internalVoiceId === null) {
            $this->internalVoiceId = $voiceId;
            Log::debug('Internal voice registered (Phase 17)', ['voiceId' => $voiceId]);
        }

        return $this->internalVoiceId ?? $this->narratorVoiceId; // Fallback to narrator
    }

    /**
     * Get validation summary for debugging.
     *
     * @return array Registry state summary
     */
    public function getValidationSummary(): array
    {
        return [
            'charactersRegistered' => count($this->characterVoices),
            'narratorVoice' => $this->narratorVoiceId,
            'internalVoice' => $this->internalVoiceId,
            'characters' => array_map(function($data) {
                return ['voiceId' => $data['voiceId'], 'source' => $data['source']];
            }, $this->characterVoices),
        ];
    }
}
```

### Example 2: Integration in VideoWizard.php

```php
// Source: Integration points in VideoWizard.php

// 1. Add property to VideoWizard class (around line 100)
protected ?VoiceRegistryService $voiceRegistry = null;

// 2. Initialize at start of decomposeAllScenes() (line 24723)
public function decomposeAllScenes(): void
{
    $sceneCount = count($this->script['scenes'] ?? []);
    if ($sceneCount === 0) {
        return;
    }

    $this->isLoading = true;
    $decomposed = 0;

    try {
        // Initialize voice registry (Phase 17)
        $this->voiceRegistry = new \Modules\AppVideoWizard\Services\VoiceRegistryService();
        $this->voiceRegistry->initializeFromCharacterBible(
            $this->sceneMemory['characterBible'] ?? [],
            $this->getNarratorVoice()
        );

        foreach ($this->script['scenes'] as $index => $scene) {
            // ... existing scene decomposition ...
        }

        // VOC-04 validation can now compare against registry
        $voiceContinuityResult = $this->validateVoiceContinuity($this->multiShotMode['decomposedScenes'] ?? []);

        // ... rest of method ...
    } catch (\Exception $e) {
        // ... error handling ...
    }
}

// 3. Use registry in overlayNarratorSegments() (line 24143)
// Before:
$shots[$shotIdx]['narratorVoiceId'] = $this->getNarratorVoice();

// After:
$shots[$shotIdx]['narratorVoiceId'] = $this->voiceRegistry->getNarratorVoice();

// 4. Use registry in markInternalThoughtAsVoiceover() (line 24307)
// Before:
$voiceId = $speaker ? $this->getVoiceForCharacterName($speaker) : $this->getNarratorVoice();

// After:
$voiceId = $speaker
    ? $this->voiceRegistry->getVoiceForCharacter(
        $speaker,
        fn($name) => $this->getVoiceForCharacterName($name)
      )
    : $this->voiceRegistry->getNarratorVoice();

// 5. Use registry for dialogue shots (multiple locations - search for "voiceId.*=")
// Pattern: anywhere assigning voiceId for a character
$voiceId = $this->voiceRegistry->getVoiceForCharacter(
    $characterName,
    fn($name) => $this->getVoiceForCharacterName($name)
);
```

### Example 3: Testing Voice Registry

```php
// Recommended test scenarios (not full test file - just examples)

// Test 1: Initialization from Character Bible
$registry = new VoiceRegistryService();
$characterBible = [
    'characters' => [
        ['name' => 'Alice', 'voice' => ['id' => 'nova']],
        ['name' => 'Bob', 'voice' => ['id' => 'onyx']],
    ]
];
$registry->initializeFromCharacterBible($characterBible, 'fable');

$aliceVoice = $registry->getVoiceForCharacter('Alice', fn($n) => 'fallback');
// Expect: 'nova' (from Bible, not fallback)

// Test 2: First-occurrence-wins
$registry = new VoiceRegistryService();
$registry->initializeFromCharacterBible(['characters' => []], 'fable');

// First lookup - uses fallback, registers result
$voice1 = $registry->getVoiceForCharacter('Charlie', fn($n) => 'alloy');
// Expect: 'alloy'

// Second lookup - uses registered voice, ignores fallback
$voice2 = $registry->getVoiceForCharacter('Charlie', fn($n) => 'shimmer');
// Expect: 'alloy' (not 'shimmer' - first wins!)

// Test 3: Legacy voice format handling
$legacyBible = [
    'characters' => [
        ['name' => 'OldChar', 'voice' => 'echo'], // Legacy string format
    ]
];
$registry->initializeFromCharacterBible($legacyBible, 'fable');
$voice = $registry->getVoiceForCharacter('OldChar', fn($n) => 'fallback');
// Expect: 'echo' (handles legacy format)
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Voice lookup at usage time | Registry + lookup | Phase 17 (2026-01) | Single source of truth, easier debugging |
| Post-hoc validation only | Pre-seed + runtime tracking | Phase 17 (2026-01) | Catches mismatches earlier in pipeline |
| Global Registry Pattern | Instance-based service | Modern PHP (2020+) | Avoids global state, easier testing |
| Singleton services | Inline instantiation | This codebase pattern | Simpler, no DI complexity |

**Deprecated/outdated:**
- **Registry Pattern with static methods:** Modern PHP (2020+) advice strongly discourages global state. Use instance-based services instead.
- **Replacing working fallback chains:** Early refactoring attempts often rewrote voice lookup logic. Wrapper pattern preserves existing logic while adding consistency layer.

## Open Questions

1. **Should registry persist across Livewire requests?**
   - What we know: Livewire persists component properties in session
   - What's unclear: Does `$this->voiceRegistry` need to survive between requests, or re-initialize each time?
   - Recommendation: Re-initialize at start of `decomposeAllScenes()` for simplicity. Registry is lightweight and initialization is fast.

2. **How to handle manual voice changes in UI?**
   - What we know: Users can manually select voice for shots via `$this->shotVoiceSelection`
   - What's unclear: Should manual selection override registry, or should registry prevent it?
   - Recommendation: Manual selection should register the voice (first-occurrence-wins applies). Once manual voice set, it becomes the registered voice for that character.

3. **Should validateVoiceContinuity() be replaced or enhanced?**
   - What we know: VOC-04 validation already implements tracking logic
   - What's unclear: Should it be removed in favor of registry validation, or kept as separate check?
   - Recommendation: Keep `validateVoiceContinuity()` as final check after decomposition. Registry catches issues during runtime, validation confirms everything stayed consistent. Defense in depth.

## Sources

### Primary (HIGH confidence)
- Existing codebase analysis: `VideoWizard.php` lines 8598-8766 (voice methods and VOC-04 validation)
- Existing service pattern: `ShotContinuityService.php`, `CharacterLookService.php` (architecture examples)
- Character Bible structure: Multiple references in VideoWizard.php (lines 2301, 2588, 5533, 6090, etc.)

### Secondary (MEDIUM confidence)
- [Service Layer in Laravel — use it!](https://medium.com/@sliusarchyn/service-layer-in-laravel-use-it-ae861fb0f124) - Service pattern benefits in Laravel
- [Service Layer Laravel Tutorial](https://muneebdev.com/service-layer-laravel-tutorial/) - Laravel 11 service patterns and best practices
- [Clean Service-Action Architecture](https://ratheepan.medium.com/clean-service-action-architecture-a-battle-tested-pattern-for-laravel-applications-dc311ecc5c29) - Battle-tested patterns for Laravel applications
- [Laravel Error Handling Patterns](https://betterstack.com/community/guides/scaling-php/laravel-error-handling-patterns/) - Non-blocking error handling with logging

### Tertiary (LOW confidence)
- [Registry Pattern in PHP](https://designpatternsphp.readthedocs.io/en/latest/Structural/Registry/README.html) - Traditional Registry Pattern (NOTE: Explicitly avoid this approach - uses global state)
- [Service Pattern in Laravel: Why it is meaningless](https://nabilhassen.com/laravel-service-pattern-issues) - Critique of service pattern misuse (helps identify anti-patterns)
- [State Pattern in PHP](https://designpatternsphp.readthedocs.io/en/latest/Behavioral/State/README.html) - Alternative pattern for stateful tracking

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Laravel/PHP stack confirmed from codebase analysis
- Architecture: HIGH - Service pattern confirmed from existing services, VOC-04 provides implementation reference
- Integration points: HIGH - Exact line numbers and method names identified in VideoWizard.php
- Pitfalls: MEDIUM-HIGH - Based on code review and modern PHP best practices, some inferred from pattern critiques

**Research date:** 2026-01-25
**Valid until:** 60 days (Laravel service patterns are stable, PHP best practices evolve slowly)

**Key codebase references:**
- VOC-04 implementation: `VideoWizard.php:8625-8766` (validation with first-occurrence-wins)
- Narrator voice: `VideoWizard.php:8598-8608` (getNarratorVoice fallback chain)
- Character voice: `VideoWizard.php:23349-23381` (getVoiceForCharacterName fallback chain)
- Narrator assignment: `VideoWizard.php:24143` (overlayNarratorSegments integration point)
- Internal assignment: `VideoWizard.php:24307` (markInternalThoughtAsVoiceover integration point)
- Decompose entry: `VideoWizard.php:24723` (decomposeAllScenes initialization point)
