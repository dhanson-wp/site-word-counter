<?php
/**
 * Keeps blocks from the Telex version (telex/block-site-word-counter) working.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SITE_WORD_COUNTER_LEGACY_BLOCK = 'telex/block-site-word-counter';

/**
 * Registers the Telex block name, hidden from the inserter.
 */
function site_word_counter_register_legacy_block() {
	register_block_type(
		SITE_WORD_COUNTER_LEGACY_BLOCK,
		array(
			'api_version'     => 3,
			'title'           => __( 'Site Word Counter (legacy)', 'site-word-counter' ),
			'category'        => 'widgets',
			'attributes'      => array(
				'enableAnimation' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'textAlignment'   => array(
					'type'    => 'string',
					'default' => 'left',
				),
			),
			'supports'        => array(
				'inserter'   => false,
				'html'       => false,
				'color'      => array(
					'text'       => true,
					'background' => false,
				),
				'typography' => array( 'fontSize' => true ),
				'spacing'    => array(
					'margin'  => true,
					'padding' => true,
				),
			),
			'render_callback' => 'site_word_counter_render_legacy_block',
		)
	);
}
add_action( 'init', 'site_word_counter_register_legacy_block' );

/**
 * Maps a legacy block's attributes to the current block's.
 *
 * @param array $attributes Legacy attributes.
 * @return array Current attributes.
 */
function site_word_counter_map_legacy_attributes( $attributes ) {
	$alignment = isset( $attributes['textAlignment'] ) ? $attributes['textAlignment'] : '';
	unset( $attributes['textAlignment'] );

	if ( in_array( $alignment, array( 'center', 'right' ), true ) ) {
		$attributes['style']['typography']['textAlign'] = $alignment;
	}

	return $attributes;
}

/**
 * Renders a legacy block as the current block.
 *
 * @param array    $attributes Prepared attributes.
 * @param string   $content    Inner content (unused).
 * @param WP_Block $block      Block instance.
 * @return string HTML.
 */
function site_word_counter_render_legacy_block( $attributes, $content, $block ) {
	$raw = isset( $block->parsed_block['attrs'] ) ? (array) $block->parsed_block['attrs'] : array();

	return render_block(
		array(
			'blockName'    => 'site-word-counter/site-word-counter',
			'attrs'        => site_word_counter_map_legacy_attributes( $raw ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}
