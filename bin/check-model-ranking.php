<?php
/** Lightweight release checks for dynamic model ranking. */

define( 'ABSPATH', '/tmp/' );

require dirname( __DIR__ ) . '/lib/autoload.php';
require dirname( __DIR__ ) . '/includes/class-ai-core-model-defaults.php';

use AICore\Registry\ModelRegistry;

$failures = 0;

function ai_core_release_check( $label, $condition ) {
	global $failures;
	echo ( $condition ? 'PASS' : 'FAIL' ) . "  {$label}\n";
	if ( ! $condition ) {
		++$failures;
	}
}

$gemini = array(
	'gemini-2.5-flash-lite',
	'gemini-3.6-deep-research-pro',
	'gemini-2.5-pro',
	'gemini-3.6-flash',
	'gemini-3.1-pro',
	'gemini-2.5-flash-image',
);
$sorted = ModelRegistry::sortModelsForDisplay( $gemini );
$positions = array_flip( $sorted );

ai_core_release_check( 'Gemini 3.6 Flash ranks first', 'gemini-3.6-flash' === $sorted[0] );
ai_core_release_check( 'Mainline text ranks above image', $positions['gemini-2.5-pro'] < $positions['gemini-2.5-flash-image'] );
ai_core_release_check( 'Research model is not the preferred writing model', 'gemini-3.6-deep-research-pro' !== ModelRegistry::getPreferredModel( 'gemini', $gemini ) );
ai_core_release_check( 'Hub chooses a current Gemini mainline model', 0 === strpos( AI_Core_Model_Defaults::best_text_model( $gemini ), 'gemini-3.' ) );

$openai = array( 'dall-e-3', 'o3', 'gpt-4o', 'gpt-5', 'gpt-image-1' );
$openai_sorted = ModelRegistry::sortModelsForDisplay( $openai );
ai_core_release_check( 'OpenAI GPT-5 ranks first', 'gpt-5' === $openai_sorted[0] );

exit( $failures > 0 ? 1 : 0 );
