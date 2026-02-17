<?php
// Debug script to inspect concept data from a wizard project
// Usage: php get_concept_292.php [project_id]
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$projectId = $argv[1] ?? 292;
$p = DB::table('wizard_projects')->where('id', $projectId)->value('content_config');
$cc = json_decode($p, true);
$vars = $cc['conceptVariations'] ?? [];

if (!empty($vars[0])) {
    $v = $vars[0];

    // Show visual analysis if stored
    if (!empty($v['_visualAnalysis'])) {
        echo "=== FULL VISUAL ANALYSIS (Gemini) ===\n";
        echo $v['_visualAnalysis'];
        echo "\n\n";
    }

    if (!empty($v['_audioTranscript'])) {
        echo "=== AUDIO TRANSCRIPT ===\n";
        echo $v['_audioTranscript'];
        echo "\n\n";
    }

    echo "=== VIDEO PROMPT ===\n";
    echo $v['videoPrompt'] ?? 'NOT FOUND';
    echo "\n\n";

    echo "=== AUDIO TYPE & DESCRIPTION ===\n";
    echo "audioType: " . ($v['audioType'] ?? 'N/A') . "\n";
    echo "audioDescription: " . ($v['audioDescription'] ?? 'N/A') . "\n\n";

    echo "=== DIALOGUE LINES ===\n";
    echo json_encode($v['dialogueLines'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "=== SITUATION ===\n";
    echo $v['situation'] ?? 'N/A';
    echo "\n\n";

    echo "=== SKELETON TYPE ===\n";
    echo $v['_skeleton']['type'] ?? 'N/A';
    echo "\n";
} else {
    echo "No concept variations found for project $projectId\n";
}
