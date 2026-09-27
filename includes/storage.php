<?php
/**
 * Per-post word counts and the site total.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SITE_WORD_COUNTER_META_KEY  = '_site_word_counter_words';
const SITE_WORD_COUNTER_TOTAL_KEY = 'site_word_counter_total';

/**
 * Registers the per-post word count meta.
 */
function site_word_counter_register_meta() {
	register_meta(
		'post',
		SITE_WORD_COUNTER_META_KEY,
		array(
			'type'              => 'integer',
			'description'       => __( 'Number of words in the post content.', 'site-word-counter' ),
			'single'            => true,
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'site_word_counter_register_meta' );

/**
 * Returns the post types that count toward the total.
 *
 * @return string[] Post type slugs.
 */
function site_word_counter_get_post_types() {
	$public = get_post_types( array( 'public' => true ) );
	unset( $public['attachment'] );

	$saved = get_option( 'site_word_counter_post_types', array( 'post', 'page' ) );
	$types = array_values( array_intersect( (array) $saved, $public ) );

	if ( empty( $types ) ) {
		$types = array_values( array_intersect( array( 'post', 'page' ), $public ) );
	}

	/**
	 * Filters the post types that count toward the total.
	 *
	 * @param string[] $types Post type slugs.
	 */
	$types = (array) apply_filters( 'site_word_counter_post_types', $types );

	return array_values( array_unique( array_filter( array_map( 'sanitize_key', $types ) ) ) );
}

/**
 * Whether a post type counts toward the total.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function site_word_counter_is_counted_type( $post_type ) {
	return in_array( $post_type, site_word_counter_get_post_types(), true );
}

/**
 * Counts a post's words and stores the count in post meta.
 *
 * @param int|WP_Post $post Post ID or object.
 * @return int|null The count, or null if the post doesn't exist.
 */
function site_word_counter_update_post_count( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$count = site_word_counter_count_text( $post->post_content );
	update_post_meta( $post->ID, SITE_WORD_COUNTER_META_KEY, $count );

	return $count;
}

/**
 * Stores the word count when a post is saved.
 *
 * @param int     $post_id     Post ID.
 * @param WP_Post $post        Post object.
 * @param bool    $update      Whether this is an update.
 * @param WP_Post $post_before Post before the update, or null.
 */
function site_word_counter_on_save( $post_id, $post, $update, $post_before ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( ! site_word_counter_is_counted_type( $post->post_type ) ) {
		return;
	}

	site_word_counter_update_post_count( $post );

	$was_published = $post_before instanceof WP_Post && 'publish' === $post_before->post_status;
	if ( 'publish' === $post->post_status || $was_published ) {
		site_word_counter_clear_total();
	}
}
add_action( 'wp_after_insert_post', 'site_word_counter_on_save', 10, 4 );

/**
 * Clears the cached total when a post moves into or out of publish.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post object.
 */
function site_word_counter_on_transition( $new_status, $old_status, $post ) {
	if ( $new_status === $old_status || ( 'publish' !== $new_status && 'publish' !== $old_status ) ) {
		return;
	}
	if ( site_word_counter_is_counted_type( $post->post_type ) ) {
		site_word_counter_clear_total();
	}
}
add_action( 'transition_post_status', 'site_word_counter_on_transition', 10, 3 );

/**
 * Clears the cached total when a counted post is deleted.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function site_word_counter_on_delete( $post_id, $post ) {
	if ( $post instanceof WP_Post && site_word_counter_is_counted_type( $post->post_type ) ) {
		site_word_counter_clear_total();
	}
}
add_action( 'deleted_post', 'site_word_counter_on_delete', 10, 2 );

/**
 * Clears the cached total when a stored count changes.
 *
 * @param int|int[] $meta_ids  Meta ID or IDs.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function site_word_counter_on_meta_change( $meta_ids, $object_id, $meta_key ) {
	if ( SITE_WORD_COUNTER_META_KEY === $meta_key ) {
		site_word_counter_clear_total();
	}
}
add_action( 'added_post_meta', 'site_word_counter_on_meta_change', 10, 3 );
add_action( 'updated_post_meta', 'site_word_counter_on_meta_change', 10, 3 );
add_action( 'deleted_post_meta', 'site_word_counter_on_meta_change', 10, 3 );
add_action( 'update_option_site_word_counter_post_types', 'site_word_counter_clear_total' );

/**
 * Clears the cached total.
 */
function site_word_counter_clear_total() {
	delete_transient( SITE_WORD_COUNTER_TOTAL_KEY );
}

/**
 * Returns the total words across published posts of the counted types.
 *
 * @return int Total words.
 */
function site_word_counter_get_total() {
	global $wpdb;

	$types  = site_word_counter_get_post_types();
	$hash   = md5( implode( ',', $types ) );
	$cached = get_transient( SITE_WORD_COUNTER_TOTAL_KEY );

	if ( is_array( $cached ) && isset( $cached['types'], $cached['total'] ) && $hash === $cached['types'] ) {
		$total = (int) $cached['total'];
	} else {
		$total = 0;
		if ( $types ) {
			$placeholders = implode( ', ', array_fill( 0, count( $types ), '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A single SUM over post meta, cached in a transient below.
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is only %s placeholders.
					"SELECT COALESCE( SUM( CAST( pm.meta_value AS UNSIGNED ) ), 0 ) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_status = 'publish' AND p.post_type IN ( {$placeholders} )",
					array_merge( array( SITE_WORD_COUNTER_META_KEY ), $types )
				)
			);
		}
		set_transient(
			SITE_WORD_COUNTER_TOTAL_KEY,
			array(
				'types' => $hash,
				'total' => $total,
			)
		);
	}

	/**
	 * Filters the site's total word count before it's displayed.
	 *
	 * @param int $total Total words.
	 */
	return max( 0, (int) apply_filters( 'site_word_counter_total', $total ) );
}
