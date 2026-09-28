<?php
/**
 * Plugin Name:       Site Word Counter
 * Plugin URI:        https://github.com/dhanson-wp/site-word-counter
 * Description:       A block that shows the total number of words published across your site.
 * Version:           1.1.1
 * Requires at least: 7.1
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

define( 'SITE_WORD_COUNTER_VERSION', '1.1.1' );
define( 'SITE_WORD_COUNTER_FILE', __FILE__ );
define( 'SITE_WORD_COUNTER_DIR', plugin_dir_path( __FILE__ ) );
define( 'SITE_WORD_COUNTER_URL', plugin_dir_url( __FILE__ ) );

require_once SITE_WORD_COUNTER_DIR . 'includes/counting.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/storage.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/backfill.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/render.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/legacy.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/rest.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/settings.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/placement.php';
require_once SITE_WORD_COUNTER_DIR . 'includes/inline-count.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once SITE_WORD_COUNTER_DIR . 'includes/class-site-word-counter-cli.php';
}

register_activation_hook( __FILE__, 'site_word_counter_activate' );
register_deactivation_hook( __FILE__, 'site_word_counter_deactivate' );

/**
 * Registers the block from its compiled metadata.
 */
function site_word_counter_register_block() {
	register_block_type( SITE_WORD_COUNTER_DIR . 'compiled/' );
}
add_action( 'init', 'site_word_counter_register_block' );
