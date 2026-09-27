<?php
/**
 * Counting posts that don't have a stored count yet.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SITE_WORD_COUNTER_BACKFILL_HOOK = 'site_word_counter_backfill';
const SITE_WORD_COUNTER_BATCH_SIZE    = 200;

/**
 * Post statuses that get a stored count.
 *
 * @return string[]
 */
function site_word_counter_countable_statuses() {
	return array( 'publish', 'future', 'draft', 'pending', 'private' );
}

/**
 * Builds query arguments for posts of the counted types.
 *
 * @param bool $missing_only Only posts without a stored count.
 * @return array
 */
function site_word_counter_posts_query_args( $missing_only ) {
	$args = array(
		'post_type'              => site_word_counter_get_post_types(),
		'post_status'            => site_word_counter_countable_statuses(),
		'fields'                 => 'ids',
		'orderby'                => 'ID',
		'order'                  => 'ASC',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	if ( $missing_only ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Only runs in batches during a backfill.
		$args['meta_query'] = array(
			array(
				'key'     => SITE_WORD_COUNTER_META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);
	}

	return $args;
}

/**
 * Counts posts of the counted types.
 *
 * @param bool $missing_only Only posts without a stored count.
 * @return int
 */
function site_word_counter_count_posts( $missing_only = false ) {
	$query = new WP_Query(
		array_merge(
			site_word_counter_posts_query_args( $missing_only ),
			array(
				'posts_per_page' => 1,
				'no_found_rows'  => false,
			)
		)
	);

	return (int) $query->found_posts;
}

/**
 * Counts and stores one batch of posts.
 *
 * @param bool $all        Recount every post, not just posts without a count.
 * @param int  $offset     Offset for a full recount. Ignored otherwise.
 * @param int  $batch_size Posts per batch.
 * @return array {
 *     @type int  $processed   Posts counted in this batch.
 *     @type int  $next_offset Offset for the next batch.
 *     @type bool $done        Whether nothing is left.
 * }
 */
function site_word_counter_process_batch( $all = false, $offset = 0, $batch_size = SITE_WORD_COUNTER_BATCH_SIZE ) {
	$batch_size = max( 1, (int) $batch_size );
	$args       = array_merge(
		site_word_counter_posts_query_args( ! $all ),
		array(
			'posts_per_page' => $batch_size,
			'offset'         => $all ? max( 0, (int) $offset ) : 0,
			'no_found_rows'  => true,
		)
	);

	$ids = get_posts( $args );
	foreach ( $ids as $id ) {
		site_word_counter_update_post_count( $id );
	}

	$processed = count( $ids );
	$done      = $processed < $batch_size;

	if ( $done && $all ) {
		update_option( 'site_word_counter_last_recount', time(), false );
	}

	return array(
		'processed'   => $processed,
		'next_offset' => $all ? (int) $offset + $processed : 0,
		'done'        => $done,
	);
}

/**
 * Schedules the background backfill if it isn't already scheduled.
 */
function site_word_counter_schedule_backfill() {
	if ( ! wp_next_scheduled( SITE_WORD_COUNTER_BACKFILL_HOOK ) ) {
		wp_schedule_single_event( time(), SITE_WORD_COUNTER_BACKFILL_HOOK );
	}
}
// The first save creates the option, so listen for both.
add_action( 'update_option_site_word_counter_post_types', 'site_word_counter_schedule_backfill' );
add_action( 'add_option_site_word_counter_post_types', 'site_word_counter_schedule_backfill' );

/**
 * Runs one backfill batch, and schedules the next one if posts are left.
 */
function site_word_counter_run_backfill() {
	$result = site_word_counter_process_batch();
	if ( ! $result['done'] ) {
		wp_schedule_single_event( time() + 10, SITE_WORD_COUNTER_BACKFILL_HOOK );
	}
}
add_action( SITE_WORD_COUNTER_BACKFILL_HOOK, 'site_word_counter_run_backfill' );

/**
 * Whether the background backfill is scheduled or running.
 *
 * @return bool
 */
function site_word_counter_backfill_running() {
	return (bool) wp_next_scheduled( SITE_WORD_COUNTER_BACKFILL_HOOK ) || site_word_counter_count_posts( true ) > 0;
}

/**
 * Starts the backfill on activation.
 */
function site_word_counter_activate() {
	site_word_counter_schedule_backfill();
}

/**
 * Stops the backfill on deactivation.
 */
function site_word_counter_deactivate() {
	wp_clear_scheduled_hook( SITE_WORD_COUNTER_BACKFILL_HOOK );
}
