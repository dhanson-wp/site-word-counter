<?php
/**
 * The inline "Word count" format: a live total inside a sentence.
 *
 * The editor saves the total at insert time inside a marker span, so the
 * sentence still reads fine if the plugin is turned off. On the front end,
 * the marker's text is swapped for the current total.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep in step with CLASS_NAME in src/inline-count/index.js.
const SITE_WORD_COUNTER_INLINE_CLASS = 'site-word-counter-inline';

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

/**
 * Marks inline word counts with a faint dotted underline in the editor, so a
 * live count stands apart from a number typed by hand. Visitors never see it.
 *
 * Styles added on enqueue_block_assets reach the editor's iframe canvas; the
 * is_admin() check keeps them off the front end.
 */
function site_word_counter_enqueue_inline_count_editor_style() {
	if ( ! is_admin() ) {
		return;
	}

	wp_register_style( 'site-word-counter-inline-count-editor', false, array(), SITE_WORD_COUNTER_VERSION );
	wp_enqueue_style( 'site-word-counter-inline-count-editor' );
	wp_add_inline_style(
		'site-word-counter-inline-count-editor',
		'.' . SITE_WORD_COUNTER_INLINE_CLASS . '{text-decoration:underline dotted;text-decoration-thickness:1px;text-underline-offset:0.2em;text-decoration-color:color-mix(in srgb,currentColor 55%,transparent);}'
	);
}
add_action( 'enqueue_block_assets', 'site_word_counter_enqueue_inline_count_editor_style' );

/**
 * Swaps the saved number in each inline word count for the current total.
 *
 * @param string $block_content Rendered block HTML.
 * @return string HTML with current totals.
 */
function site_word_counter_render_inline_counts( $block_content ) {
	if ( ! is_string( $block_content ) || ! str_contains( $block_content, SITE_WORD_COUNTER_INLINE_CLASS ) ) {
		return $block_content;
	}

	// number_format_i18n() can return entities such as &nbsp;, and the HTML API
	// escapes the plain text it's given.
	$number = html_entity_decode(
		site_word_counter_format_number( site_word_counter_get_total(), 'full' ),
		ENT_QUOTES | ENT_HTML5,
		get_bloginfo( 'charset' )
	);

	$processor = new WP_HTML_Tag_Processor( $block_content );

	while ( $processor->next_tag(
		array(
			'tag_name'   => 'SPAN',
			'class_name' => SITE_WORD_COUNTER_INLINE_CLASS,
		)
	) ) {
		// The first text inside the marker gets the number, even inside
		// formatting like bold, and any other text inside it is cleared.
		$depth    = 1;
		$replaced = false;

		while ( $depth > 0 && $processor->next_token() ) {
			$token = $processor->get_token_name();

			if ( 'SPAN' === $token ) {
				$depth += $processor->is_tag_closer() ? -1 : 1;
			} elseif ( '#text' === $token ) {
				$processor->set_modifiable_text( $replaced ? '' : $number );
				$replaced = true;
			}
		}
	}

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'site_word_counter_render_inline_counts' );
