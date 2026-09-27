<?php
/**
 * Block rendering and number formatting.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formats a word count for display.
 *
 * @param int    $number Word count.
 * @param string $format "full" (33,895) or "compact" (33.9K).
 * @return string Formatted number.
 */
function site_word_counter_format_number( $number, $format = 'full' ) {
	$number = max( 0, (int) $number );

	if ( 'compact' !== $format || $number < 1000 ) {
		return number_format_i18n( $number );
	}

	$units = array(
		/* translators: %s: A number of billions, such as 1.2 */
		array( 1000000000, _x( '%sB', 'compact number, billions', 'site-word-counter' ) ),
		/* translators: %s: A number of millions, such as 1.2 */
		array( 1000000, _x( '%sM', 'compact number, millions', 'site-word-counter' ) ),
		/* translators: %s: A number of thousands, such as 33.9 */
		array( 1000, _x( '%sK', 'compact number, thousands', 'site-word-counter' ) ),
	);

	foreach ( $units as $index => $unit ) {
		list( $divisor, $template ) = $unit;
		if ( $number < $divisor ) {
			continue;
		}

		$value = round( $number / $divisor, 1 );

		// 999,950 rounds to 1,000.0K, so move up to the next unit.
		if ( $value >= 1000 && $index > 0 ) {
			list( $divisor, $template ) = $units[ $index - 1 ];
			$value                      = round( $number / $divisor, 1 );
		}

		$decimals = floor( $value ) === $value ? 0 : 1;

		return sprintf( $template, number_format_i18n( $value, $decimals ) );
	}

	return number_format_i18n( $number );
}

/**
 * Registers the front-end view module. It's enqueued only by counters that animate.
 */
function site_word_counter_register_view_module() {
	$asset_file = SITE_WORD_COUNTER_DIR . 'compiled/view.asset.php';
	$asset      = file_exists( $asset_file ) ? require $asset_file : array(
		'dependencies' => array(),
		'version'      => SITE_WORD_COUNTER_VERSION,
	);

	wp_register_script_module(
		'site-word-counter/view',
		SITE_WORD_COUNTER_URL . 'compiled/view.js',
		$asset['dependencies'],
		$asset['version']
	);
}
add_action( 'init', 'site_word_counter_register_view_module' );

/**
 * Whether counter animations are turned off for the whole site.
 *
 * @return bool
 */
function site_word_counter_animation_disabled() {
	return (bool) get_option( 'site_word_counter_disable_animation', false );
}

/**
 * Builds the block's front-end markup.
 *
 * @param array $attributes Block attributes.
 * @return string HTML.
 */
function site_word_counter_render_counter( $attributes ) {
	$total     = site_word_counter_get_total();
	$format    = isset( $attributes['format'] ) && 'compact' === $attributes['format'] ? 'compact' : 'full';
	$formatted = site_word_counter_format_number( $total, $format );
	$animate   = ( ! isset( $attributes['enableAnimation'] ) || $attributes['enableAnimation'] ) && ! site_word_counter_animation_disabled();

	$extra = array(
		'data-count'  => (string) $total,
		'data-format' => $format,
	);

	if ( $animate ) {
		$extra['data-animate'] = 'true';
		wp_enqueue_script_module( 'site-word-counter/view' );

		// The screen reader copy never animates; the visible copy is decorative.
		$number = sprintf(
			'<span class="screen-reader-text">%1$s</span><span class="wp-block-site-word-counter-site-word-counter__number" aria-hidden="true">%1$s</span>',
			esc_html( $formatted )
		);
	} else {
		$number = sprintf(
			'<span class="wp-block-site-word-counter-site-word-counter__number">%s</span>',
			esc_html( $formatted )
		);
	}

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( $extra ),
		$number
	);
}
