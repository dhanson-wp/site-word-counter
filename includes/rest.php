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

	register_rest_route(
		SITE_WORD_COUNTER_REST_NAMESPACE,
		'/status',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'site_word_counter_rest_status',
			'permission_callback' => 'site_word_counter_rest_can_manage',
		)
	);

	register_rest_route(
		SITE_WORD_COUNTER_REST_NAMESPACE,
		'/recount',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'site_word_counter_rest_recount',
			'permission_callback' => 'site_word_counter_rest_can_manage',
			'args'                => array(
				'offset' => array(
					'type'              => 'integer',
					'default'           => 0,
					'minimum'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'site_word_counter_register_rest_routes' );

/**
 * Whether the current user can manage the plugin's settings.
 *
 * @return bool
 */
function site_word_counter_rest_can_manage() {
	return current_user_can( 'manage_options' );
}

/**
 * Returns the total for the editor preview.
 *
 * @return WP_REST_Response
 */
function site_word_counter_rest_total() {
	$total = site_word_counter_get_total();

	return rest_ensure_response(
		array(
			'total'              => $total,
			'formatted'          => array(
				'full'    => site_word_counter_format_number( $total, 'full' ),
				'compact' => site_word_counter_format_number( $total, 'compact' ),
			),
			'backfill_complete'  => 0 === site_word_counter_count_posts( true ),
			'animation_disabled' => site_word_counter_animation_disabled(),
			'settings_url'       => current_user_can( 'manage_options' ) ? site_word_counter_settings_url() : null,
		)
	);
}

/**
 * Returns counting status for the settings page.
 *
 * @return array
 */
function site_word_counter_status() {
	$total    = site_word_counter_get_total();
	$to_count = site_word_counter_count_posts( false );
	$missing  = site_word_counter_count_posts( true );
	$last     = (int) get_option( 'site_word_counter_last_recount', 0 );

	return array(
		'total'            => $total,
		'formatted'        => site_word_counter_format_number( $total, 'full' ),
		'counted'          => max( 0, $to_count - $missing ),
		'to_count'         => $to_count,
		'backfill_running' => $missing > 0,
		'last_recount'     => $last ? gmdate( 'c', $last ) : null,
	);
}

/**
 * REST: counting status.
 *
 * @return WP_REST_Response
 */
function site_word_counter_rest_status() {
	return rest_ensure_response( site_word_counter_status() );
}

/**
 * REST: recounts one batch of posts. Call again with next_offset until done.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function site_word_counter_rest_recount( WP_REST_Request $request ) {
	$result = site_word_counter_process_batch( true, (int) $request['offset'] );

	return rest_ensure_response(
		array_merge(
			$result,
			array( 'status' => site_word_counter_status() )
		)
	);
}
