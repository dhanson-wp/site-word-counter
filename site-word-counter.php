<?php
/**
 * Plugin Name:       Site Word Counter
 * Plugin URI:        https://github.com/dhanson-wp/site-word-counter
 * Description:       A block that shows the total number of words published across your site.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            Derek Hanson
 * Author URI:        https://derekhanson.blog
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       site-word-counter
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SITE_WORD_COUNTER_VERSION', '0.1.0' );
define( 'SITE_WORD_COUNTER_FILE', __FILE__ );
define( 'SITE_WORD_COUNTER_DIR', plugin_dir_path( __FILE__ ) );
define( 'SITE_WORD_COUNTER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Registers the block from its compiled metadata.
 */
function site_word_counter_register_block() {
	register_block_type( SITE_WORD_COUNTER_DIR . 'compiled/' );
}
add_action( 'init', 'site_word_counter_register_block' );

/**
 * Returns the total word count for published posts and pages.
 *
 * @return int Total words.
 */
function site_word_counter_get_total() {
	$cached = get_transient( 'site_word_counter_total' );
	if ( false !== $cached ) {
		return (int) $cached;
	}

	$post_ids = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$total = 0;
	foreach ( $post_ids as $post_id ) {
		$content = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
		$content = preg_replace( '/\s+/', ' ', $content );
		$total  += str_word_count( $content );
	}

	set_transient( 'site_word_counter_total', $total, HOUR_IN_SECONDS );

	return $total;
}

/**
 * Clears the cached total.
 */
function site_word_counter_clear_cache() {
	delete_transient( 'site_word_counter_total' );
}
add_action( 'save_post', 'site_word_counter_clear_cache' );
add_action( 'delete_post', 'site_word_counter_clear_cache' );
add_action( 'wp_trash_post', 'site_word_counter_clear_cache' );
add_action( 'untrashed_post', 'site_word_counter_clear_cache' );
