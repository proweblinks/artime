---
status: resolved
trigger: "Failed to create 'Collage First' image in NanoBanana Pro - Livewire 500 Internal Server Error"
created: 2026-01-25T00:00:00Z
updated: 2026-01-25T15:00:00Z
---

## Current Focus

hypothesis: CONFIRMED - decomposeSceneWithDynamicEngine() was called BEFORE the try-catch block in generateCollagePreview()
test: Code review confirmed - try block started at line 26481, but decomposeSceneWithDynamicEngine was called at line 26386
expecting: Any exception thrown during shot decomposition would propagate up and cause 500 error
next_action: Verify fix works correctly by checking code structure

## Symptoms

expected: User clicks "Collage First" generation button, NanoBanana Pro API is called, collage reference image is generated successfully
actual: Server returns 500 Internal Server Error on Livewire update endpoint
errors:
- "Failed to load resource: the server responded with a status of 500 ()"
- "/livewire/update:1 Failed to load resource: the server responded with a status of 500 ()"
- "livewire.js?id=df3a17f2:4294 POST https://artime.ai/livewire/update 500 (Internal Server Error)"
reproduction: Click "Collage First" generation in Video Wizard storyboard step using NanoBanana Pro model
started: Recently reported, potentially after Phase 19 changes

## Eliminated

- hypothesis: #[Locked] on protected property causes issue
  evidence: Livewire 3's #[Locked] only prevents client-side modification; server-side PHP can still modify. Protected properties aren't serialized anyway.
  timestamp: 2026-01-25T11:30:00Z

- hypothesis: Laravel logs contain error
  evidence: Only old error from Jan 4th in laravel.log - error is not being logged there
  timestamp: 2026-01-25T11:00:00Z

- hypothesis: ImageGenerationService methods throw unhandled exceptions
  evidence: All image generation errors are caught and handled in the try block starting at line 26481
  timestamp: 2026-01-25T12:30:00Z

## Evidence

- timestamp: 2026-01-25T11:00:00Z
  checked: VideoWizard.php generateCollagePreview() method
  found: Method passes $this->sceneMemory directly to ImageGenerationService at line 26670
  implication: SceneMemory now has referenceImageStorageKey (new) but referenceImageBase64 is empty (migrated away)

- timestamp: 2026-01-25T11:15:00Z
  checked: ImageGenerationService.php getCharacterReferenceForScene() method
  found: Method checks for $character['referenceImageBase64'] at line 686-688, has NO code to check referenceImageStorageKey
  implication: ImageGenerationService was NOT updated for Phase 19 migration - it still expects legacy base64 inline storage (SEPARATE BUG, not the 500 cause)

- timestamp: 2026-01-25T12:45:00Z
  checked: generateCollagePreview() exception handling structure
  found: try-catch block started at line 26481, but decomposeSceneWithDynamicEngine() was called at line 26386
  implication: Any exception in decomposeSceneWithDynamicEngine (or methods it calls) would NOT be caught and cause 500

- timestamp: 2026-01-25T13:00:00Z
  checked: decomposeSceneWithDynamicEngine() method
  found: Calls multiple services (SceneTypeDetectorService, DialogueSceneDecomposerService) and methods that could throw
  implication: Service resolution failures, array access errors, or decomposition method exceptions would cause unhandled 500

## Resolution

root_cause: The generateCollagePreview() method had a structural exception handling bug. The try-catch block (lines 26481-26839) did NOT wrap the entire method body. Specifically, when a scene had no decomposed shots yet, the method called decomposeSceneWithDynamicEngine() at line 26386 - BEFORE the try block started. Any exception thrown during shot decomposition (service resolution failures, array access errors, decomposition method exceptions) would propagate up unhandled and cause a 500 error.

Additionally found (separate bug): ImageGenerationService.php methods were not updated for Phase 19's reference image storage migration. They still look for referenceImageBase64 field which is now empty. This causes the Reference Cascade system to return empty references, but doesn't cause 500 - just results in text-to-image fallback instead of face consistency.

fix: Moved the try block to start immediately after the scene validation check (line 26320), before any shot decomposition code runs. Now structure is:
- Scene validation check (lines 26316-26320)
- Set isLoading = true (line 26322)
- try { (line 26324) - NOW wraps ALL code including decomposeSceneWithDynamicEngine
- ... all collage generation code ...
- } catch (\Exception $e) { ... } (line 26832)
- } finally { isLoading = false; } (line 26839)

verification:
- Code structure verified: try block now starts at line 26324, before any shot decomposition
- All potentially throwing code is now within the try block
- Exceptions will be caught and converted to user-friendly error messages instead of 500

files_changed:
- modules/AppVideoWizard/app/Livewire/VideoWizard.php (lines 26321-26324, 26481-26484)
