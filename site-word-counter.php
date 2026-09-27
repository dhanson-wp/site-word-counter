<?php
/**
 * Plugin Name:       Site Word Counter
 * Description:       A powerful block that calculates and displays the total number of words published across your entire WordPress site.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       site-word-counter-block-wp
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function site_word_counter_block_init() {
	register_block_type( __DIR__ . '/compiled/' );
}
add_action( 'init', 'site_word_counter_block_init' );

/**
 * Calculate total word count for all published posts and pages
 */
if ( ! function_exists( 'get_site_total_word_count' ) ) {
	function get_site_total_word_count() {
		// Check for cached result
		$cached_count = get_transient( 'site_total_word_count' );
		if ( false !== $cached_count ) {
			return $cached_count;
		}

		$args = array(
			'post_type' => array( 'post', 'page' ),
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);

		$posts = get_posts( $args );
		$total_words = 0;

		foreach ( $posts as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				// Get content and strip HTML tags
				$content = wp_strip_all_tags( $post->post_content );
				// Remove extra whitespace
				$content = preg_replace( '/\s+/', ' ', $content );
				// Count words
				$word_count = str_word_count( $content );
				$total_words += $word_count;
			}
		}

		// Cache for 1 hour
		set_transient( 'site_total_word_count', $total_words, HOUR_IN_SECONDS );

		return $total_words;
	}
}

/**
 * Clear word count cache when posts are updated
 */
if ( ! function_exists( 'clear_site_word_count_cache' ) ) {
	function clear_site_word_count_cache() {
		delete_transient( 'site_total_word_count' );
	}
}

// Clear cache when content is updated
add_action( 'save_post', 'clear_site_word_count_cache' );
add_action( 'delete_post', 'clear_site_word_count_cache' );
add_action( 'wp_trash_post', 'clear_site_word_count_cache' );
add_action( 'untrashed_post', 'clear_site_word_count_cache' );
	