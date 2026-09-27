<?php
/**
 * Renders the Site Word Counter block.
 *
 * @package SiteWordCounter
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo site_word_counter_render_counter( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in site_word_counter_render_counter().
