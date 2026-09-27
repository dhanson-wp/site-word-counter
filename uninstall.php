<?php
/**
 * Removes everything Site Word Counter stored when the plugin is deleted.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_post_meta_by_key( '_site_word_counter_words' );

delete_option( 'site_word_counter_post_types' );
delete_option( 'site_word_counter_disable_animation' );
delete_option( 'site_word_counter_last_recount' );
delete_transient( 'site_word_counter_total' );

wp_clear_scheduled_hook( 'site_word_counter_backfill' );
