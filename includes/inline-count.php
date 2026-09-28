<?php
/**
 * The inline "Word count" format: a live total inside a sentence.
 *
 * The editor saves the total at insert time inside a marker span, so the
 * sentence still reads fine if the plugin is turned off.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the format in every block editor (posts, the Site Editor, widgets),
 * whether or not a counter block is on the page.
 */
function site_word_counter_enqueue_inline_count_format() {
	$asset_file = SITE_WORD_COUNTER_DIR . 'compiled/inline-count/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;

	wp_enqueue_script(
		'site-word-counter-inline-count',
		SITE_WORD_COUNTER_URL . 'compiled/inline-count/index.js',
		$asset['dependencies'],
		$asset['version'],
		array( 'in_footer' => true )
	);
	wp_set_script_translations( 'site-word-counter-inline-count', 'site-word-counter' );
}
add_action( 'enqueue_block_editor_assets', 'site_word_counter_enqueue_inline_count_format' );
