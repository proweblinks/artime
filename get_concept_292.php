<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$p = DB::table('wizard_projects')->where('id', 292)->value('content_config');
$cc = json_decode($p, true);
$vars = $cc['conceptVariations'] ?? [];
if (!empty($vars[0])) {
    echo json_encode($vars[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    // Check all keys for analysis data
    echo "=== content_config keys ===\n";
    echo json_encode(array_keys($cc), JSON_PRETTY_PRINT) . "\n\n";

    // Check multiShotMode for analysis
    $msm = $cc['multiShotMode'] ?? [];
    if (!empty($msm['decomposedScenes'][0]['shots'][0])) {
        $shot = $msm['decomposedScenes'][0]['shots'][0];
        echo "=== Shot videoPrompt ===\n";
        echo ($shot['videoPrompt'] ?? 'NOT FOUND') . "\n\n";
        echo "=== Shot description ===\n";
        echo ($shot['description'] ?? 'NOT FOUND') . "\n\n";
    }

    // Check selectedConceptIndex and conceptVariations
    echo "=== selectedConceptIndex ===\n";
    echo json_encode($cc['selectedConceptIndex'] ?? 'NOT SET') . "\n\n";
    echo "=== conceptVariations count ===\n";
    echo count($cc['conceptVariations'] ?? []) . "\n\n";

    // Check content key
    $content = $cc['content'] ?? [];
    echo "=== content keys ===\n";
    echo json_encode(array_keys($content)) . "\n\n";
    echo "=== content data ===\n";
    echo json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
