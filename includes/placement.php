<?php
/**
 * Adding the word count to the theme's footer and single post template, and
 * the block patterns that share its markup.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SITE_WORD_COUNTER_BLOCK_NAME = 'site-word-counter/site-word-counter';
const SITE_WORD_COUNTER_PLACEMENTS = array( 'footer', 'after_posts' );

/**
 * Registers the placements option.
 */
function site_word_counter_register_placement_setting() {
	register_setting(
		'site_word_counter',
		'site_word_counter_placements',
		array(
			'type'              => 'array',
			'description'       => __( 'Where the word count is added to the theme.', 'site-word-counter' ),
			'default'           => array(),
			'sanitize_callback' => 'site_word_counter_sanitize_placements',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
						'enum' => SITE_WORD_COUNTER_PLACEMENTS,
					),
				),
			),
		)
	);
}
add_action( 'init', 'site_word_counter_register_placement_setting' );

/**
 * Keeps only known placements.
 *
 * @param mixed $value Submitted value.
 * @return string[]
 */
function site_word_counter_sanitize_placements( $value ) {
	return array_values( array_intersect( SITE_WORD_COUNTER_PLACEMENTS, array_map( 'sanitize_key', (array) $value ) ) );
}

/**
 * Whether a placement is turned on.
 *
 * @param string $placement "footer" or "after_posts".
 * @return bool
 */
function site_word_counter_placement_enabled( $placement ) {
	return in_array( $placement, (array) get_option( 'site_word_counter_placements', array() ), true );
}

/**
 * Returns the year of the earliest published post of the counted types.
 *
 * @return string Four-digit year.
 */
function site_word_counter_first_year() {
	$cached = get_transient( 'site_word_counter_first_year' );
	if ( false !== $cached ) {
		return (string) $cached;
	}

	$ids  = get_posts(
		array(
			'post_type'      => site_word_counter_get_post_types(),
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'ASC',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$year = $ids ? get_the_date( 'Y', $ids[0] ) : wp_date( 'Y' );

	set_transient( 'site_word_counter_first_year', $year );

	return (string) $year;
}

/**
 * Clears the cached first year along with the total.
 */
function site_word_counter_clear_first_year() {
	delete_transient( 'site_word_counter_first_year' );
}
add_action( 'site_word_counter_total_cleared', 'site_word_counter_clear_first_year' );

/**
 * Markup for "33,895 words published since 2019." as a Row.
 *
 * @param string $align Block alignment for the Row, such as "wide", or empty.
 * @return string Block markup.
 */
function site_word_counter_line_markup( $align = '' ) {
	$text = sprintf(
		/* translators: %s: the year of the site's first published post. */
		__( 'words published since %s.', 'site-word-counter' ),
		site_word_counter_first_year()
	);

	$align_attr  = $align ? '"align":"' . esc_attr( $align ) . '",' : '';
	$align_class = $align ? ' align' . sanitize_html_class( $align ) : '';

	return '<!-- wp:group {"metadata":{"name":"' . esc_attr__( 'Words published since', 'site-word-counter' ) . '"},' . $align_attr . '"className":"site-word-counter-line","style":{"spacing":{"blockGap":"0.3em"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"baseline"}} -->'
		. '<div class="wp-block-group' . $align_class . ' site-word-counter-line">'
		. '<!-- wp:' . SITE_WORD_COUNTER_BLOCK_NAME . ' /-->'
		. '<!-- wp:paragraph --><p>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->'
		. '</div><!-- /wp:group -->';
}

/**
 * The footer version of the line: a full-width group that takes the theme's
 * page padding, so the line lines up with the footer content above it.
 *
 * @return string Block markup.
 */
function site_word_counter_footer_line_markup() {
	return '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"bottom":"1.5rem"}}},"layout":{"type":"constrained"}} -->'
		. '<div class="wp-block-group alignfull" style="padding-bottom:1.5rem">'
		. site_word_counter_line_markup( 'wide' )
		. '</div><!-- /wp:group -->';
}

/**
 * Markup for a large count over a small label.
 *
 * @return string Block markup.
 */
function site_word_counter_stat_markup() {
	return '<!-- wp:group {"metadata":{"name":"' . esc_attr__( 'Word count stat', 'site-word-counter' ) . '"},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->'
		. '<div class="wp-block-group">'
		. '<!-- wp:' . SITE_WORD_COUNTER_BLOCK_NAME . ' {"style":{"typography":{"fontSize":"clamp(2.5rem, 6vw, 4rem)","lineHeight":"1.1","fontWeight":"600","textAlign":"center"}}} /-->'
		. '<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">' . esc_html__( 'words published', 'site-word-counter' ) . '</p><!-- /wp:paragraph -->'
		. '</div><!-- /wp:group -->';
}

/**
 * Registers the pattern category and patterns.
 */
function site_word_counter_register_patterns() {
	register_block_pattern_category(
		'site-word-counter',
		array( 'label' => __( 'Site Word Counter', 'site-word-counter' ) )
	);

	register_block_pattern(
		'site-word-counter/words-published-since',
		array(
			'title'       => __( 'Words published since', 'site-word-counter' ),
			'description' => __( 'Your total words and the year you started, in one line. Made for footers.', 'site-word-counter' ),
			'categories'  => array( 'site-word-counter', 'footer' ),
			'blockTypes'  => array( 'core/template-part/footer' ),
			'content'     => site_word_counter_line_markup(),
		)
	);

	register_block_pattern(
		'site-word-counter/word-count-stat',
		array(
			'title'       => __( 'Word count stat', 'site-word-counter' ),
			'description' => __( 'A large word count over a small label. Made for About pages.', 'site-word-counter' ),
			'categories'  => array( 'site-word-counter' ),
			'content'     => site_word_counter_stat_markup(),
		)
	);
}
add_action( 'init', 'site_word_counter_register_patterns' );

/**
 * Whether block markup already has a Site Word Counter block.
 *
 * @param string $content Block markup.
 * @return bool
 */
function site_word_counter_content_has_counter( $content ) {
	return false !== strpos( (string) $content, '<!-- wp:' . SITE_WORD_COUNTER_BLOCK_NAME )
		|| false !== strpos( (string) $content, '<!-- wp:' . SITE_WORD_COUNTER_LEGACY_BLOCK );
}

/**
 * Works out which placement, if any, a Block Hooks anchor is.
 *
 * @param string                          $relative_position Relative position.
 * @param string                          $anchor_block_type Anchor block type.
 * @param WP_Block_Template|WP_Post|array $context           Template, part, post, or pattern.
 * @return string|null "footer", "after_posts", or null.
 */
function site_word_counter_match_placement( $relative_position, $anchor_block_type, $context ) {
	if ( ! $context instanceof WP_Block_Template ) {
		return null;
	}

	if (
		'last_child' === $relative_position
		&& 'core/template-part' === $anchor_block_type
		&& 'wp_template_part' === $context->type
		&& 'footer' === $context->area
	) {
		return 'footer';
	}

	if (
		'after' === $relative_position
		&& 'core/post-content' === $anchor_block_type
		&& 'wp_template' === $context->type
		&& in_array( $context->slug, array( 'single-post', 'single' ), true )
	) {
		return 'after_posts';
	}

	return null;
}

/**
 * Hooks the counter into the footer and single post template when turned on.
 *
 * @param string[]                        $hooked_block_types Hooked block types.
 * @param string                          $relative_position  Relative position.
 * @param string                          $anchor_block_type  Anchor block type.
 * @param WP_Block_Template|WP_Post|array $context            Template, part, post, or pattern.
 * @return string[]
 */
function site_word_counter_hooked_block_types( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
	$placement = site_word_counter_match_placement( $relative_position, $anchor_block_type, $context );

	if (
		$placement
		&& site_word_counter_placement_enabled( $placement )
		&& ! site_word_counter_content_has_counter( $context->content )
	) {
		$hooked_block_types[] = SITE_WORD_COUNTER_BLOCK_NAME;
	}

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'site_word_counter_hooked_block_types', 10, 4 );

/**
 * Swaps the bare counter for the "words published since" line.
 *
 * WordPress still records the original block name in ignoredHookedBlocks, so
 * a line deleted in the Site Editor stays deleted.
 *
 * @param array|null                      $parsed_hooked_block Parsed hooked block.
 * @param string                          $hooked_block_type   Hooked block type.
 * @param string                          $relative_position   Relative position.
 * @param array                           $parsed_anchor_block Parsed anchor block.
 * @param WP_Block_Template|WP_Post|array $context             Template, part, post, or pattern.
 * @return array|null
 */
function site_word_counter_hooked_block( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
	if ( null === $parsed_hooked_block ) {
		return null;
	}

	$placement = site_word_counter_match_placement( $relative_position, $parsed_anchor_block['blockName'] ?? '', $context );
	if ( ! $placement ) {
		return $parsed_hooked_block;
	}

	$blocks = parse_blocks( 'footer' === $placement ? site_word_counter_footer_line_markup() : site_word_counter_line_markup() );

	return $blocks[0] ?? $parsed_hooked_block;
}
add_filter( 'hooked_block_' . SITE_WORD_COUNTER_BLOCK_NAME, 'site_word_counter_hooked_block', 10, 5 );

/**
 * Finds the footer template part the site actually uses.
 *
 * Themes can ship several footer-area parts (Twenty Twenty-Five has three),
 * so look for the one the index template includes, then one named "footer".
 *
 * @return WP_Block_Template|null
 */
function site_word_counter_active_footer_part() {
	$parts = get_block_templates( array( 'area' => 'footer' ), 'wp_template_part' );
	if ( ! $parts ) {
		return null;
	}

	$by_slug = array();
	foreach ( $parts as $part ) {
		$by_slug[ $part->slug ] = $part;
	}

	$index = get_block_template( get_stylesheet() . '//index' );
	if ( $index ) {
		$stack = parse_blocks( $index->content );
		while ( $stack ) {
			$block = array_shift( $stack );
			$slug  = $block['attrs']['slug'] ?? '';
			if ( 'core/template-part' === $block['blockName'] && isset( $by_slug[ $slug ] ) ) {
				return $by_slug[ $slug ];
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				array_push( $stack, ...$block['innerBlocks'] );
			}
		}
	}

	return $by_slug['footer'] ?? $parts[0];
}

/**
 * Whether someone deleted the added line from a template in the Site Editor.
 *
 * WordPress records deleted hooked blocks in the saved template's
 * _wp_ignored_hooked_blocks meta, and won't add them again.
 *
 * @param string $template_id Template ID, such as "twentytwentyfive//footer".
 * @return bool
 */
function site_word_counter_removed_in_editor( $template_id ) {
	$template = get_block_template( $template_id, str_contains( $template_id, '//single' ) ? 'wp_template' : 'wp_template_part' );
	if ( ! $template || empty( $template->wp_id ) ) {
		return false;
	}

	$ignored = json_decode( (string) get_post_meta( $template->wp_id, '_wp_ignored_hooked_blocks', true ), true );

	return is_array( $ignored ) && in_array( SITE_WORD_COUNTER_BLOCK_NAME, $ignored, true );
}

/**
 * Returns the Site Editor link and availability for each placement.
 *
 * @return array[]
 */
function site_word_counter_placement_targets() {
	$is_block_theme = wp_is_block_theme();
	$targets        = array(
		'footer'      => null,
		'after_posts' => null,
	);

	if ( $is_block_theme ) {
		$footer = site_word_counter_active_footer_part();
		if ( $footer ) {
			$targets['footer'] = array( 'wp_template_part', $footer->id );
		}

		foreach ( array( 'single-post', 'single' ) as $slug ) {
			$template = get_block_template( get_stylesheet() . '//' . $slug );
			if ( $template ) {
				$targets['after_posts'] = array( 'wp_template', $template->id );
				break;
			}
		}
	}

	$result = array();
	foreach ( $targets as $placement => $target ) {
		$result[] = array(
			'id'              => $placement,
			'enabled'         => (bool) $target,
			'removedInEditor' => $target ? site_word_counter_removed_in_editor( $target[1] ) : false,
			'editUrl'         => $target
				? add_query_arg(
					array(
						'postType' => $target[0],
						'postId'   => rawurlencode( $target[1] ),
						'canvas'   => 'edit',
					),
					admin_url( 'site-editor.php' )
				)
				: null,
		);
	}

	return array(
		'isBlockTheme' => $is_block_theme,
		'placements'   => $result,
		'firstYear'    => site_word_counter_first_year(),
	);
}
