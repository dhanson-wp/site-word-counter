<?php
/**
 * REST routes.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SITE_WORD_COUNTER_REST_NAMESPACE = 'site-word-counter/v1';

/**
 * Registers the plugin's REST routes.
 */
function site_word_counter_register_rest_routes() {
	register_rest_route(
		SITE_WORD_COUNTER_REST_NAMESPACE,
		'/total',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'site_word_counter_rest_total',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'rest_api_init', 'site_word_counter_register_rest_routes' );

/**
 * Returns the total for the editor preview.
 *
 * @return WP_REST_Response
 */
function site_word_counter_rest_total() {
	$total = site_word_counter_get_total();

	return rest_ensure_response(
		array(
			'total'             => $total,
			'formatted'         => array(
				'full'    => site_word_counter_format_number( $total, 'full' ),
				'compact' => site_word_counter_format_number( $total, 'compact' ),
			),
			'backfill_complete' => 0 === site_word_counter_count_posts( true ),
		)
	);
}
