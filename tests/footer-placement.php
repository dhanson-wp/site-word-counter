<?php
/**
 * Checks for the footer placement. Not part of the plugin zip.
 *
 * Run it on a test site with a block theme active:
 *
 *     wp eval-file wp-content/plugins/site-word-counter/tests/footer-placement.php
 *
 * It builds footer template parts in memory and runs them through Block
 * Hooks the way WordPress does, so nothing on the site changes.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs Block Hooks on a footer part the way WordPress builds one from a file.
 *
 * @param string $content     The part's markup, which Block Hooks walks.
 * @param string $context     The markup the part says it has, when it should
 *                            differ from what Block Hooks walks. Defaults to
 *                            $content.
 * @param array  $part_attrs  Attributes for the template part wrapper, like
 *                            ignoredHookedBlocks from the part's meta.
 * @return string The part's markup after Block Hooks.
 */
function site_word_counter_test_hook_part( $content, $context = null, $part_attrs = array() ) {
	$template          = new WP_Block_Template();
	$template->id      = get_stylesheet() . '//swc-test-footer';
	$template->theme   = get_stylesheet();
	$template->slug    = 'swc-test-footer';
	$template->type    = 'wp_template_part';
	$template->area    = 'footer';
	$template->content = null === $context ? $content : $context;

	$wrapped = get_comment_delimited_block_content( 'core/template-part', $part_attrs, $content );

	return remove_serialized_parent_block(
		apply_block_hooks_to_content( $wrapped, $template, 'insert_hooked_blocks_and_set_ignored_hooked_blocks_metadata' )
	);
}

/**
 * Where the counter ended up in a part's markup.
 *
 * @param string $markup The part's markup after Block Hooks.
 * @param string $before The part's markup before.
 * @return array{count:int, where:string} How many counters there are, and
 *                                        "part" when the line was added after
 *                                        the part's content or "group" when
 *                                        it went inside it.
 */
function site_word_counter_test_find( $markup, $before ) {
	$top_level = static function ( $content ) {
		return count( array_filter( parse_blocks( $content ), static fn( $block ) => ! empty( $block['blockName'] ) ) );
	};

	$count = preg_match_all( '/<!-- wp:' . preg_quote( SITE_WORD_COUNTER_BLOCK_NAME, '/' ) . '[ \/]/', $markup );
	$added = $count - preg_match_all( '/<!-- wp:' . preg_quote( SITE_WORD_COUNTER_BLOCK_NAME, '/' ) . '[ \/]/', $before );

	return array(
		'count' => $count,
		'where' => $added ? ( $top_level( $markup ) > $top_level( $before ) ? 'part' : 'group' ) : '',
	);
}

/**
 * Hooks a footer part and reports where the counter went.
 *
 * @param string      $content    The part's markup.
 * @param string|null $context    See site_word_counter_test_hook_part().
 * @param array       $part_attrs See site_word_counter_test_hook_part().
 * @return array See site_word_counter_test_find().
 */
function site_word_counter_test_run( $content, $context = null, $part_attrs = array() ) {
	return site_word_counter_test_find( site_word_counter_test_hook_part( $content, $context, $part_attrs ), $content );
}

/**
 * Runs the checks.
 *
 * @return int Number of failures.
 */
function site_word_counter_test_footer_placement() {
	add_filter( 'pre_option_site_word_counter_placements', static fn() => array( 'footer' ) );

	$failures = 0;
	$check    = static function ( $name, $pass, $detail = '' ) use ( &$failures ) {
		if ( ! $pass ) {
			++$failures;
		}
		WP_CLI::log( ( $pass ? 'PASS ' : 'FAIL ' ) . $name . ( $detail ? " ({$detail})" : '' ) );
	};

	$para  = '<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Made with WordPress</p><!-- /wp:paragraph -->';
	$group = static function ( $inner, $attrs = '{"layout":{"type":"constrained"}}' ) {
		return '<!-- wp:group ' . $attrs . ' --><div class="wp-block-group">' . $inner . '</div><!-- /wp:group -->';
	};

	// A single group, with a group inside it: the line goes in the outer one.
	$result = site_word_counter_test_run(
		$group( $group( $para, '{"layout":{"type":"flex"}}' ) . $para, '{"style":{"spacing":{"padding":{"top":"1rem"}}},"layout":{"type":"constrained"}}' )
	);
	$check( 'single group takes the line inside it', 1 === $result['count'] && 'group' === $result['where'], wp_json_encode( $result ) );

	// Several top-level blocks: the line goes after the part's content.
	$result = site_word_counter_test_run( $group( $para ) . $para );
	$check( 'several top-level blocks fall back to the part', 1 === $result['count'] && 'part' === $result['where'], wp_json_encode( $result ) );

	// A group with a lookalike group inside it could match twice, so the part takes it.
	$result = site_word_counter_test_run( $group( $group( $para ) ) );
	$check( 'lookalike nested group falls back to the part, once', 1 === $result['count'] && 'part' === $result['where'], wp_json_encode( $result ) );

	// An empty group never gets a last child from Block Hooks.
	$result = site_word_counter_test_run( '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"></div><!-- /wp:group -->' );
	$check( 'empty group falls back to the part', 1 === $result['count'] && 'part' === $result['where'], wp_json_encode( $result ) );

	// A failed match: the group Block Hooks walks isn't the one the part's
	// markup describes, so it can't be recognized. The line still shows, once.
	$result = site_word_counter_test_run(
		$group( $para, '{"style":{"spacing":{"padding":{"top":"2rem"}}},"layout":{"type":"constrained"}}' ),
		$group( $para, '{"style":{"spacing":{"padding":{"top":"1rem"}}},"layout":{"type":"constrained"}}' )
	);
	$check( 'failed group match falls back to the part', 1 === $result['count'] && 'part' === $result['where'], wp_json_encode( $result ) );

	// ignoredHookedBlocks from other plugins, reordered attributes, and
	// whitespace don't stop the group from matching.
	$result = site_word_counter_test_run(
		$group( "\n" . $para . "\n", '{"layout":{"type":"constrained"},"metadata":{"name":"Footer","ignoredHookedBlocks":["core/loginout"]},"style":{"spacing":{"padding":{"top":"1rem"}}}}' ),
		$group( $para, '{"style":{"spacing":{"padding":{"top":"1rem"}}},"metadata":{"name":"Footer"},"layout":{"type":"constrained"}}' )
	);
	$check( 'group still matches with metadata, order, and whitespace changes', 1 === $result['count'] && 'group' === $result['where'], wp_json_encode( $result ) );

	// Deleted from the group in the Site Editor: stays deleted, with no fallback.
	$ignored = '{"metadata":{"ignoredHookedBlocks":["' . SITE_WORD_COUNTER_BLOCK_NAME . '"]},"layout":{"type":"constrained"}}';
	$result  = site_word_counter_test_run( $group( $para, $ignored ) );
	$check( 'line deleted from the group stays deleted', 0 === $result['count'], wp_json_encode( $result ) );

	// Deleted from the group, and the group no longer matches: still no line.
	$result = site_word_counter_test_run( $group( $group( $para ), $ignored ) . $para );
	$check( 'line deleted from a group that no longer matches stays deleted', 0 === $result['count'], wp_json_encode( $result ) );

	// Deleted from the part (the fallback), recorded in the part's meta.
	$result = site_word_counter_test_run(
		$group( $para ) . $para,
		null,
		array( 'metadata' => array( 'ignoredHookedBlocks' => array( SITE_WORD_COUNTER_BLOCK_NAME ) ) )
	);
	$check( 'line deleted from the part stays deleted', 0 === $result['count'], wp_json_encode( $result ) );

	// A footer that already has a counter gets no second one.
	$existing = $group( $para . '<!-- wp:' . SITE_WORD_COUNTER_BLOCK_NAME . ' /-->' );
	$result   = site_word_counter_test_run( $existing );
	$check( 'footer with a counter gets no second one', 1 === $result['count'], wp_json_encode( $result ) );

	// The fallback wrapper uses a spacing preset, not a fixed size, and is valid.
	$markup  = site_word_counter_footer_line_markup( array(), '' );
	$spacing = site_word_counter_footer_spacing();
	$check( 'fallback wrapper has no fixed padding', ! str_contains( $markup, '1.5rem' ), $spacing ? "preset {$spacing}" : 'no preset' );
	if ( $spacing ) {
		$check( 'fallback wrapper uses the spacing preset', str_contains( $markup, 'var(--wp--preset--spacing--' . $spacing . ')' ) && str_contains( $markup, 'var:preset|spacing|' . $spacing ) );
	}
	$check( 'fallback wrapper serializes the same way it parses', serialize_blocks( parse_blocks( $markup ) ) === $markup );

	// The small font size is only used when the theme has one.
	$has_small = isset( site_word_counter_presets( array( 'typography', 'fontSizes' ), array( 'typography', 'defaultFontSizes' ) )['small'] );
	$check( 'no text block uses small only when it exists', ( $has_small ? array( 'fontSize' => 'small' ) : array() ) === site_word_counter_text_attrs( array() ) );

	$without_small = static function ( $theme_json ) {
		$data  = $theme_json->get_data();
		$sizes = array_filter(
			$data['settings']['typography']['fontSizes']['theme'] ?? array(),
			static fn( $size ) => 'small' !== ( $size['slug'] ?? '' )
		);

		return $theme_json->update_with(
			array(
				'version'  => WP_Theme_JSON::LATEST_SCHEMA,
				'settings' => array(
					'typography' => array(
						'defaultFontSizes' => false,
						'fontSizes'        => array_values( $sizes ),
					),
				),
			)
		);
	};
	add_filter( 'wp_theme_json_data_theme', $without_small );
	wp_clean_theme_json_cache();
	$check( 'no text block and no small size leaves the size to the theme', array() === site_word_counter_text_attrs( array() ) );
	remove_filter( 'wp_theme_json_data_theme', $without_small );
	wp_clean_theme_json_cache();

	WP_CLI::log( $failures ? "{$failures} failed." : 'All passed.' );

	return $failures;
}

if ( site_word_counter_test_footer_placement() ) {
	exit( 1 );
}
