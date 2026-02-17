<?php
// Get the actual shot videoPrompt (post-sanitized, what was sent to Seedance)
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$projectId = $argv[1] ?? 301;
$p = DB::table('wizard_projects')->where('id', $projectId)->value('content_config');
$cc = json_decode($p, true);

// Get from multiShotMode (the actual shot data)
$msm = $cc['multiShotMode'] ?? [];
$shot = $msm['decomposedScenes'][0]['shots'][0] ?? [];

echo "=== SHOT videoPrompt (post-sanitized) ===\n";
echo $shot['videoPrompt'] ?? 'NOT FOUND IN SHOT';
echo "\n\n";

// Also get from conceptVariations (pre-sanitized)
$vars = $cc['conceptVariations'] ?? [];
if (!empty($vars[0]['videoPrompt'])) {
    echo "=== CONCEPT videoPrompt (pre-sanitized) ===\n";
    echo $vars[0]['videoPrompt'];
    echo "\n\n";

    // Show diff
    $pre = $vars[0]['videoPrompt'];
    $post = $shot['videoPrompt'] ?? '';
    if ($pre === $post) {
        echo ">>> IDENTICAL — sanitizer may not have run!\n";
    } else {
        echo ">>> DIFFERENT — sanitizer DID run\n";
    }
}
