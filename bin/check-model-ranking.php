<?php
/**
 * Unit-style checks for L-02 (default model) and L-03 (dropdown order).
 *
 * Simulates a Gemini account whose live list carries models newer than the
 * bundled registry (the 3.x mainline), plus side families and niche models,
 * exactly the shape that produced the Loom findings.
 */

define('ABSPATH', '/tmp/');

require dirname(__DIR__) . '/lib/autoload.php';

use AICore\Registry\ModelRegistry;

$fail = 0;
function check($label, $cond) {
    global $fail;
    echo ($cond ? "PASS" : "FAIL") . "  $label\n";
    if (!$cond) { $fail++; }
}

// The live list, deliberately shuffled, mixing mainline, side families,
// dated variants, previews and a Deep Research model.
$live = [
    'gemini-2.5-flash-lite',
    'imagen-4.0-generate-001',
    'gemini-2.5-pro',
    'gemma-3-27b-it',
    'gemini-3.6-deep-research-pro',
    'gemini-2.5-flash',
    'gemini-3.1-pro',
    'gemini-3.6-flash',
    'gemini-2.5-flash-image',
    'gemini-3.6-flash-preview-05-2026',
    'gemini-1.5-pro',
];

// Register the unknowns the way GeminiProvider does on live discovery.
foreach ($live as $id) {
    if (!ModelRegistry::modelExists($id)) {
        ModelRegistry::registerModel($id, [
            'provider' => 'gemini',
            'display_name' => $id,
            'category' => (strpos($id, 'imagen') === 0 || strpos($id, '-image') !== false) ? 'image' : 'text',
            'priority' => 30,
        ]);
    }
}

// --- L-03: display order ---
$sorted = ModelRegistry::sortModelsForDisplay($live);
echo "Display order:\n  " . implode("\n  ", $sorted) . "\n\n";

$pos = array_flip($sorted);
check('3.6 Flash within top 3', $pos['gemini-3.6-flash'] < 3);
check('3.1 Pro within top 4', $pos['gemini-3.1-pro'] < 4);
check('3.6 Flash above every 2.5 model', $pos['gemini-3.6-flash'] < $pos['gemini-2.5-pro']);
check('mainline gemini above gemma', $pos['gemini-1.5-pro'] < $pos['gemma-3-27b-it']);
check('mainline gemini text above imagen', $pos['gemini-1.5-pro'] < $pos['imagen-4.0-generate-001']);
check('text models above image models within gemini', $pos['gemini-2.5-pro'] < $pos['gemini-2.5-flash-image']);
check('plain 3.6 flash above its dated preview', $pos['gemini-3.6-flash'] < $pos['gemini-3.6-flash-preview-05-2026']);
check('Deep Research never first', $sorted[0] !== 'gemini-3.6-deep-research-pro');

// --- L-02: default selection, registry path (getPreferredModel) ---
$preferred = ModelRegistry::getPreferredModel('gemini', $live);
echo "\ngetPreferredModel: $preferred\n";
check('preferred is a 3.6 mainline model', strpos($preferred, 'gemini-3.6') === 0);
check('preferred is not Deep Research', strpos($preferred, 'research') === false);
check('preferred is not an image model', strpos($preferred, 'image') === false);

// --- L-02: default selection, hub path (AI_Core_Model_Defaults) ---
require dirname(__DIR__) . '/includes/class-ai-core-model-defaults.php';
$best = AI_Core_Model_Defaults::best_text_model($live);
echo "best_text_model: $best\n";
check('hub default is a 3.6 mainline model', strpos($best, 'gemini-3.6') === 0);
check('hub default is not Deep Research', strpos($best, 'research') === false);

$bestImage = AI_Core_Model_Defaults::best_image_model($live);
echo "best_image_model: $bestImage\n";
check('hub image default is an image model', $bestImage === 'imagen-4.0-generate-001' || strpos($bestImage, 'image') !== false);

// Niche-only list still yields something rather than nothing.
$nicheOnly = AI_Core_Model_Defaults::best_text_model(['gemini-3.6-deep-research-pro']);
check('niche-only list still returns the niche model', $nicheOnly === 'gemini-3.6-deep-research-pro');

// --- QA scenario 2026-08-12: the cached live list is sorted on a request
// where discovery never ran, so the unknowns are NOT registered, and the
// list carries month-year previews with unpadded months (12-2025, 10-2025).
$qaLive = [
    'gemini-2.5-flash-native-audio-preview-12-2025',
    'gemini-2.5-computer-use-preview-10-2025',
    'gemini-3.6-flash-unseeded',
    'gemini-3.1-pro-unseeded',
    'gemini-2.5-pro',
    'gemini-2.5-flash',
    'gemini-2.5-flash-image-unseeded',
    'gemma-3-27b-it-unseeded',
];
$sortedQa = ModelRegistry::sortModelsForDisplay($qaLive);
echo "\nUnregistered-cache order:\n  " . implode("\n  ", $sortedQa) . "\n\n";
$pq = array_flip($sortedQa);
check('unseeded 3.6 flash first even unregistered', $sortedQa[0] === 'gemini-3.6-flash-unseeded');
check('native-audio preview never above mainline flagships', $pq['gemini-2.5-flash-native-audio-preview-12-2025'] > $pq['gemini-2.5-pro']);
check('computer-use preview never above mainline flagships', $pq['gemini-2.5-computer-use-preview-10-2025'] > $pq['gemini-2.5-pro']);
check('unregistered image model sorts with images (bottom)', $pq['gemini-2.5-flash-image-unseeded'] > $pq['gemini-2.5-flash']);

$qaPreferred = ModelRegistry::getPreferredModel('gemini', $qaLive);
echo "getPreferredModel (unregistered): $qaPreferred\n";
check('preferred from unregistered cache is the 3.6 mainline', $qaPreferred === 'gemini-3.6-flash-unseeded');

// OpenAI shape: gpt mainline above o-series and dall-e.
$openai = ['dall-e-3', 'o3', 'gpt-4o', 'gpt-5', 'gpt-4.1', 'gpt-5-mini', 'gpt-image-1', 'o4-mini', 'gpt-3.5-turbo'];
$sortedOpenai = ModelRegistry::sortModelsForDisplay($openai);
echo "\nOpenAI order:\n  " . implode("\n  ", $sortedOpenai) . "\n";
$po = array_flip($sortedOpenai);
check('gpt-5 first for OpenAI', $sortedOpenai[0] === 'gpt-5');
check('gpt family above o-series', $po['gpt-3.5-turbo'] < $po['o3']);

exit($fail > 0 ? 1 : 0);
