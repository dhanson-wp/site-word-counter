<?php
/**
 * Settings > Word Counter.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SITE_WORD_COUNTER_SETTINGS_SLUG = 'site-word-counter';

/**
 * Sanitizes the counted post types, never allowing an empty list.
 *
 * @param mixed $value Submitted value.
 * @return string[]
 */
function site_word_counter_sanitize_post_types( $value ) {
	$allowed = array_keys( site_word_counter_available_post_types() );
	$types   = array_values( array_intersect( array_map( 'sanitize_key', (array) $value ), $allowed ) );

	return $types ? $types : array( 'post', 'page' );
}

/**
 * Registers the plugin's options, including for the REST settings endpoint.
 */
function site_word_counter_register_settings() {
	register_setting(
		'site_word_counter',
		'site_word_counter_post_types',
		array(
			'type'              => 'array',
			'description'       => __( 'Post types whose words count toward the total.', 'site-word-counter' ),
			'default'           => array( 'post', 'page' ),
			'sanitize_callback' => 'site_word_counter_sanitize_post_types',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		)
	);

	register_setting(
		'site_word_counter',
		'site_word_counter_disable_animation',
		array(
			'type'              => 'boolean',
			'description'       => __( 'Turn off counter animations across the site.', 'site-word-counter' ),
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'site_word_counter_register_settings' );

/**
 * Adds Settings > Word Counter.
 */
function site_word_counter_add_settings_page() {
	add_options_page(
		__( 'Site Word Counter', 'site-word-counter' ),
		__( 'Word Counter', 'site-word-counter' ),
		'manage_options',
		SITE_WORD_COUNTER_SETTINGS_SLUG,
		'site_word_counter_render_settings_page'
	);
}
add_action( 'admin_menu', 'site_word_counter_add_settings_page' );

/**
 * Prints the settings page root. The screen itself is React.
 */
function site_word_counter_render_settings_page() {
	printf(
		'<div id="site-word-counter-settings" class="site-word-counter-settings"><noscript>%s</noscript></div>',
		esc_html__( 'The Site Word Counter settings need JavaScript.', 'site-word-counter' )
	);
}

/**
 * Returns the settings page URL.
 *
 * @return string
 */
function site_word_counter_settings_url() {
	return admin_url( 'options-general.php?page=' . SITE_WORD_COUNTER_SETTINGS_SLUG );
}

/**
 * Loads the settings screen's script and styles, on that screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function site_word_counter_enqueue_settings_assets( $hook_suffix ) {
	if ( 'settings_page_' . SITE_WORD_COUNTER_SETTINGS_SLUG !== $hook_suffix ) {
		return;
	}

	$asset_file = SITE_WORD_COUNTER_DIR . 'compiled/admin/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;

	wp_enqueue_script(
		'site-word-counter-settings',
		SITE_WORD_COUNTER_URL . 'compiled/admin/index.js',
		$asset['dependencies'],
		$asset['version'],
		array( 'in_footer' => true )
	);
	wp_set_script_translations( 'site-word-counter-settings', 'site-word-counter' );

	$post_types = array();
	foreach ( site_word_counter_available_post_types() as $slug => $label ) {
		$post_types[] = array(
			'value' => $slug,
			'label' => $label,
		);
	}

	wp_add_inline_script(
		'site-word-counter-settings',
		'window.siteWordCounterSettings = ' . wp_json_encode(
			array(
				'postTypes' => $post_types,
				'placement' => site_word_counter_placement_targets(),
			)
		) . ';',
		'before'
	);

	// wp-theme holds the Design System's design tokens.
	wp_enqueue_style(
		'site-word-counter-settings',
		SITE_WORD_COUNTER_URL . 'compiled/admin/index.css',
		array( 'wp-components', 'wp-theme' ),
		$asset['version']
	);
}
add_action( 'admin_enqueue_scripts', 'site_word_counter_enqueue_settings_assets' );

/**
 * Adds a Settings link to the plugin's row on the Plugins screen.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function site_word_counter_plugin_action_links( $links ) {
	array_unshift(
		$links,
		sprintf(
			'<a href="%s">%s</a>',
			esc_url( site_word_counter_settings_url() ),
			esc_html__( 'Settings', 'site-word-counter' )
		)
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( SITE_WORD_COUNTER_FILE ), 'site_word_counter_plugin_action_links' );
