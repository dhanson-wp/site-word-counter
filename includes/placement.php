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
 * @param string $align      Block alignment for the Row, such as "wide", or empty.
 * @param array  $text_attrs Text attributes for the count and the words, from
 *                           site_word_counter_text_attrs(). Empty uses the theme's defaults.
 * @return string Block markup.
 */
function site_word_counter_line_markup( $align = '', $text_attrs = array() ) {
	$text = sprintf(
		/* translators: %s: the year of the site's first published post. */
		__( 'words published since %s.', 'site-word-counter' ),
		site_word_counter_first_year()
	);

	$row_attrs = array(
		'metadata'  => array( 'name' => __( 'Words published since', 'site-word-counter' ) ),
		'className' => 'site-word-counter-line',
		'style'     => array( 'spacing' => array( 'blockGap' => '0.3em' ) ),
		'layout'    => array(
			'type'              => 'flex',
			'flexWrap'          => 'wrap',
			'verticalAlignment' => 'baseline',
		),
	);
	$row_class = 'wp-block-group site-word-counter-line';

	if ( $align ) {
		$row_attrs = array( 'align' => $align ) + $row_attrs;
		$row_class = 'wp-block-group align' . sanitize_html_class( $align ) . ' site-word-counter-line';
	}

	// Centered or right-aligned footer text moves the whole Row with it.
	$text_align = $text_attrs['style']['typography']['textAlign'] ?? '';
	if ( in_array( $text_align, array( 'center', 'right' ), true ) ) {
		$row_attrs['layout']['justifyContent'] = $text_align;
	}

	// The Row takes the text's size too, so the gap between the count and the
	// words scales with it.
	$row_style = '';
	if ( ! empty( $text_attrs['fontSize'] ) ) {
		$row_attrs['fontSize'] = $text_attrs['fontSize'];
		$row_class            .= ' has-' . _wp_to_kebab_case( $text_attrs['fontSize'] ) . '-font-size';
	} elseif ( ! empty( $text_attrs['style']['typography']['fontSize'] ) ) {
		$row_attrs['style']['typography']['fontSize'] = $text_attrs['style']['typography']['fontSize'];

		$styles    = wp_style_engine_get_styles( array( 'typography' => array( 'fontSize' => $text_attrs['style']['typography']['fontSize'] ) ) );
		$row_style = $styles['css'] ?? '';
	}

	return get_comment_delimited_block_content(
		'core/group',
		$row_attrs,
		'<div class="' . esc_attr( $row_class ) . '"' . ( $row_style ? ' style="' . esc_attr( $row_style ) . '"' : '' ) . '>'
			. get_comment_delimited_block_content( SITE_WORD_COUNTER_BLOCK_NAME, site_word_counter_counter_text_attrs( $text_attrs ), '' )
			. site_word_counter_paragraph_markup( $text, $text_attrs )
			. '</div>'
	);
}

/**
 * Markup for a Paragraph block with text attributes, saved the way the
 * editor saves it so the block stays valid.
 *
 * @param string $text       Plain text.
 * @param array  $text_attrs Text attributes, from site_word_counter_text_attrs().
 * @return string Block markup.
 */
function site_word_counter_paragraph_markup( $text, $text_attrs = array() ) {
	$classes    = array();
	$typography = $text_attrs['style']['typography'] ?? array();

	if ( ! empty( $typography['textAlign'] ) ) {
		$classes[] = 'has-text-align-' . sanitize_html_class( $typography['textAlign'] );
		unset( $typography['textAlign'] );
	}
	if ( ! empty( $text_attrs['textColor'] ) ) {
		$classes[] = 'has-' . _wp_to_kebab_case( $text_attrs['textColor'] ) . '-color';
	}
	if ( ! empty( $text_attrs['textColor'] ) || ! empty( $text_attrs['style']['color']['text'] ) ) {
		$classes[] = 'has-text-color';
	}
	if ( ! empty( $text_attrs['fontFamily'] ) ) {
		$classes[] = 'has-' . _wp_to_kebab_case( $text_attrs['fontFamily'] ) . '-font-family';
	}
	if ( ! empty( $text_attrs['fontSize'] ) ) {
		$classes[] = 'has-' . _wp_to_kebab_case( $text_attrs['fontSize'] ) . '-font-size';
	}

	$styles = wp_style_engine_get_styles(
		array(
			'typography' => $typography,
			'color'      => array_intersect_key( $text_attrs['style']['color'] ?? array(), array( 'text' => true ) ),
		)
	);
	if ( ! empty( $styles['classnames'] ) ) {
		$classes = array_merge( $classes, explode( ' ', $styles['classnames'] ) );
	}

	$class_attr = $classes ? ' class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '"' : '';
	$style_attr = ! empty( $styles['css'] ) ? ' style="' . esc_attr( $styles['css'] ) . '"' : '';

	return get_comment_delimited_block_content(
		'core/paragraph',
		$text_attrs,
		'<p' . $class_attr . $style_attr . '>' . esc_html( $text ) . '</p>'
	);
}

/**
 * Narrows text attributes to what the counter block supports.
 *
 * @param array $text_attrs Text attributes, from site_word_counter_text_attrs().
 * @return array Counter block attributes.
 */
function site_word_counter_counter_text_attrs( $text_attrs ) {
	$attrs = array_intersect_key(
		$text_attrs,
		array(
			'fontSize'   => true,
			'textColor'  => true,
			'fontFamily' => true,
		)
	);

	$typography = array_intersect_key(
		$text_attrs['style']['typography'] ?? array(),
		array(
			'fontSize'      => true,
			'lineHeight'    => true,
			'fontStyle'     => true,
			'fontWeight'    => true,
			'letterSpacing' => true,
			'textAlign'     => true,
		)
	);
	if ( $typography ) {
		$attrs['style']['typography'] = $typography;
	}
	if ( ! empty( $text_attrs['style']['color']['text'] ) ) {
		$attrs['style']['color']['text'] = $text_attrs['style']['color']['text'];
	}

	return $attrs;
}

/**
 * Finds the text attributes of the last text block, so the line can match
 * the footer's own small print.
 *
 * Walks into inner blocks and patterns, carries down what a group sets for
 * the text inside it, and skips a line that's already there.
 *
 * @param array[] $blocks Parsed blocks.
 * @return array Text attributes: fontSize, textColor, fontFamily, and
 *               style.typography and style.color.text. Falls back to the
 *               small font size when there's no text block and the theme
 *               has one, and to the theme's default text otherwise.
 */
function site_word_counter_text_attrs( $blocks ) {
	$attrs = site_word_counter_find_last_text_attrs( $blocks );
	if ( null !== $attrs ) {
		return $attrs;
	}

	$font_sizes = site_word_counter_presets( array( 'typography', 'fontSizes' ), array( 'typography', 'defaultFontSizes' ) );

	return isset( $font_sizes['small'] ) ? array( 'fontSize' => 'small' ) : array();
}

/**
 * Returns the presets the site can use for a setting, by slug, from core's
 * defaults (unless the theme turns them off), the theme, and Global Styles.
 *
 * @param string[] $path         Path to the presets in the settings, such as
 *                               array( 'typography', 'fontSizes' ).
 * @param string[] $default_path Path to the setting that turns core's
 *                               defaults on or off.
 * @return array[] Presets, keyed by slug.
 */
function site_word_counter_presets( $path, $default_path ) {
	$by_origin = wp_get_global_settings( $path );
	if ( ! is_array( $by_origin ) ) {
		return array();
	}
	if ( wp_is_numeric_array( $by_origin ) ) {
		$by_origin = array( 'theme' => $by_origin );
	}

	$use_defaults = false !== wp_get_global_settings( $default_path );
	$presets      = array();

	foreach ( array( 'default', 'theme', 'custom' ) as $origin ) {
		if ( 'default' === $origin && ! $use_defaults ) {
			continue;
		}
		foreach ( (array) ( $by_origin[ $origin ] ?? array() ) as $preset ) {
			if ( ! empty( $preset['slug'] ) ) {
				$presets[ $preset['slug'] ] = $preset;
			}
		}
	}

	return $presets;
}

/**
 * Returns the text attributes of the last Paragraph or Site Tagline block,
 * in reading order, merged over what its parent groups set.
 *
 * @param array[] $blocks    Parsed blocks.
 * @param array   $inherited Text attributes set by parent blocks.
 * @return array|null Text attributes, or null when there's no text block.
 */
function site_word_counter_find_last_text_attrs( $blocks, $inherited = array() ) {
	$found = null;

	foreach ( $blocks as $block ) {
		$name = $block['blockName'] ?? '';
		if ( ! $name || SITE_WORD_COUNTER_BLOCK_NAME === $name ) {
			continue;
		}

		if ( str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'site-word-counter-line' ) ) {
			continue;
		}

		$own = site_word_counter_block_text_attrs( $block );

		if ( in_array( $name, array( 'core/paragraph', 'core/site-tagline' ), true ) ) {
			$found = site_word_counter_merge_text_attrs( $inherited, $own );
			continue;
		}

		if ( 'core/pattern' === $name ) {
			$inner = site_word_counter_expand_patterns( array( $block ) );
			if ( array( $block ) === $inner ) {
				continue;
			}
		} else {
			$inner = $block['innerBlocks'] ?? array();
		}

		$last = $inner ? site_word_counter_find_last_text_attrs( $inner, site_word_counter_merge_text_attrs( $inherited, $own ) ) : null;
		if ( null !== $last ) {
			$found = $last;
		}
	}

	return $found;
}

/**
 * Picks the attributes that style text out of a block's attributes.
 *
 * @param array $block Parsed block.
 * @return array Text attributes.
 */
function site_word_counter_block_text_attrs( $block ) {
	$source = $block['attrs'] ?? array();
	$attrs  = array_intersect_key(
		$source,
		array(
			'fontSize'   => true,
			'textColor'  => true,
			'fontFamily' => true,
		)
	);

	// Only the properties that style text, not layout ones like text columns.
	$typography = array_intersect_key(
		$source['style']['typography'] ?? array(),
		array(
			'fontSize'       => true,
			'lineHeight'     => true,
			'fontStyle'      => true,
			'fontWeight'     => true,
			'letterSpacing'  => true,
			'textTransform'  => true,
			'textDecoration' => true,
			'textAlign'      => true,
		)
	);

	// Paragraphs saved before text alignment became a block support use "align",
	// and some blocks, like Site Tagline, still have a textAlign attribute.
	if ( empty( $typography['textAlign'] ) ) {
		$typography['textAlign'] = $source['textAlign'] ?? ( 'core/paragraph' === $block['blockName'] ? ( $source['align'] ?? '' ) : '' );
	}
	if ( ! in_array( $typography['textAlign'], array( 'left', 'center', 'right' ), true ) ) {
		unset( $typography['textAlign'] );
	}

	if ( $typography ) {
		$attrs['style']['typography'] = $typography;
	}

	// A preset in the custom color becomes the text color attribute, which the editor saves as classes.
	$color = $source['style']['color']['text'] ?? '';
	if ( is_string( $color ) && str_starts_with( $color, 'var:preset|color|' ) ) {
		$attrs['textColor'] = substr( $color, strlen( 'var:preset|color|' ) );
	} elseif ( is_string( $color ) && '' !== $color && empty( $attrs['textColor'] ) ) {
		$attrs['style']['color']['text'] = $color;
	}

	return $attrs;
}

/**
 * Merges a block's text attributes over its parents', where a preset and a
 * custom value for the same property replace each other.
 *
 * @param array $inherited Text attributes from parent blocks.
 * @param array $own       The block's own text attributes.
 * @return array
 */
function site_word_counter_merge_text_attrs( $inherited, $own ) {
	if ( isset( $own['style']['typography']['fontSize'] ) ) {
		unset( $inherited['fontSize'] );
	}
	if ( isset( $own['fontSize'] ) ) {
		unset( $inherited['style']['typography']['fontSize'] );
	}
	if ( isset( $own['style']['color']['text'] ) ) {
		unset( $inherited['textColor'] );
	}
	if ( isset( $own['textColor'] ) ) {
		unset( $inherited['style']['color'] );
	}

	$merged = array_replace_recursive( $inherited, $own );

	// Leave out anything the unsets above emptied.
	if ( empty( $merged['style']['typography'] ) ) {
		unset( $merged['style']['typography'] );
	}
	if ( empty( $merged['style']['color'] ) ) {
		unset( $merged['style']['color'] );
	}
	if ( empty( $merged['style'] ) ) {
		unset( $merged['style'] );
	}

	return $merged;
}

/**
 * The footer version of the line, for when it goes after the footer part's
 * content instead of inside its outermost group: a full-width group that
 * takes the theme's page padding, so the line lines up with the footer
 * content above it.
 *
 * @param array  $text_attrs Text attributes, from site_word_counter_text_attrs().
 * @param string $align      Block alignment for the Row, from
 *                           site_word_counter_blocks_alignment().
 * @return string Block markup.
 */
function site_word_counter_footer_line_markup( $text_attrs = array(), $align = '' ) {
	$attrs = array( 'align' => 'full' );
	$style = '';

	$spacing = site_word_counter_footer_spacing();
	if ( $spacing ) {
		$padding                              = array( 'bottom' => 'var:preset|spacing|' . $spacing );
		$attrs['style']['spacing']['padding'] = $padding;
		$styles                               = wp_style_engine_get_styles( array( 'spacing' => array( 'padding' => $padding ) ) );
		$style                                = $styles['css'] ?? '';
	}

	$attrs['layout'] = array( 'type' => 'constrained' );

	return get_comment_delimited_block_content(
		'core/group',
		$attrs,
		'<div class="wp-block-group alignfull"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>'
			. site_word_counter_line_markup( $align, $text_attrs )
			. '</div>'
	);
}

/**
 * Picks the theme's spacing preset closest to 1.5rem, the room the line
 * leaves below itself when it goes after the footer's content. Sizes are
 * compared by their first length, so "clamp(1.5rem, 4vw, 2rem)" counts as
 * 1.5rem.
 *
 * @return string Spacing preset slug, or an empty string when the site has
 *                no spacing presets to pick from.
 */
function site_word_counter_footer_spacing() {
	$best      = '';
	$best_diff = null;

	foreach ( site_word_counter_presets( array( 'spacing', 'spacingSizes' ), array( 'spacing', 'defaultSpacingSizes' ) ) as $slug => $preset ) {
		if ( ! is_string( $preset['size'] ?? null ) || ! preg_match( '/(\d*\.?\d+)(px|rem|em)\b/', $preset['size'], $match ) ) {
			continue;
		}

		$pixels = 'px' === $match[2] ? (float) $match[1] : (float) $match[1] * 16;
		$diff   = abs( $pixels - 24 );
		if ( null === $best_diff || $diff < $best_diff ) {
			$best      = (string) $slug;
			$best_diff = $diff;
		}
	}

	return $best;
}

/**
 * Matches the footer's own content width: "wide" when the footer lays its
 * content out wide (Twenty Twenty-Five), otherwise the content column (Ipsum).
 *
 * @param array[] $blocks The footer part's parsed blocks.
 * @return string "wide" or "".
 */
function site_word_counter_blocks_alignment( $blocks ) {
	foreach ( site_word_counter_expand_patterns( $blocks ) as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			return site_word_counter_children_alignment( $block );
		}
	}

	return '';
}

/**
 * Whether a group lays out any of its children wide.
 *
 * @param array $block Parsed block.
 * @return string "wide" or "".
 */
function site_word_counter_children_alignment( $block ) {
	foreach ( $block['innerBlocks'] ?? array() as $child ) {
		if ( in_array( $child['attrs']['align'] ?? '', array( 'wide', 'full' ), true ) ) {
			return 'wide';
		}
	}

	return '';
}

/**
 * Whether the placement filters are paused while the plugin reads a template
 * or pattern itself, so reading one doesn't add the line to it.
 *
 * @param bool|null $pause True to pause, false to resume, null to check.
 * @return bool
 */
function site_word_counter_hooks_paused( $pause = null ) {
	static $paused = false;
	if ( null !== $pause ) {
		$paused = (bool) $pause;
	}

	return $paused;
}

/**
 * Returns a registered pattern's content without the line added to it.
 *
 * @param string $slug Pattern slug.
 * @return string Block markup, or an empty string.
 */
function site_word_counter_pattern_content( $slug ) {
	$registry = WP_Block_Patterns_Registry::get_instance();
	if ( ! $slug || ! $registry->is_registered( $slug ) ) {
		return '';
	}

	$was_paused = site_word_counter_hooks_paused();
	site_word_counter_hooks_paused( true );
	$pattern = $registry->get_registered( $slug );
	site_word_counter_hooks_paused( $was_paused );

	return (string) ( $pattern['content'] ?? '' );
}

/**
 * Replaces top-level Pattern blocks with the registered pattern's blocks.
 *
 * @param array[] $blocks Parsed blocks.
 * @return array[]
 */
function site_word_counter_expand_patterns( $blocks ) {
	$expanded = array();

	foreach ( $blocks as $block ) {
		$content = 'core/pattern' === $block['blockName'] ? site_word_counter_pattern_content( $block['attrs']['slug'] ?? '' ) : '';
		if ( '' !== $content ) {
			$expanded = array_merge( $expanded, parse_blocks( $content ) );
		} else {
			$expanded[] = $block;
		}
	}

	return $expanded;
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
 * The footer line goes inside the footer's outermost group, as its last
 * child, so it takes the footer's layout, padding, and spacing. Block Hooks
 * can't tell that group from the ones inside it, so this accepts the last
 * child of any group in the footer, and site_word_counter_hooked_block()
 * drops the ones that aren't the outermost. It also accepts the last child
 * of the footer part itself, where site_word_counter_hooked_block() adds the
 * line only when no group will, such as a footer that isn't a single group.
 *
 * @param string                          $relative_position Relative position.
 * @param string                          $anchor_block_type Anchor block type.
 * @param WP_Block_Template|WP_Post|array $context           Template, part, post, or pattern.
 * @return string|null "footer", "after_posts", or null.
 */
function site_word_counter_match_placement( $relative_position, $anchor_block_type, $context ) {
	if ( is_array( $context ) ) {
		$slug = $context['name'] ?? ( $context['slug'] ?? '' );

		// Themes like Ipsum and Twenty Twenty-Five build the footer part from a
		// pattern, and Block Hooks then run with the pattern as the context.
		if (
			'last_child' === $relative_position
			&& 'core/group' === $anchor_block_type
			&& in_array( $slug, site_word_counter_footer_patterns(), true )
		) {
			return 'footer';
		}

		// Themes like Ipsum build the single template from a pattern, too.
		if (
			'after' === $relative_position
			&& 'core/post-content' === $anchor_block_type
			&& in_array( $slug, site_word_counter_single_template_patterns(), true )
		) {
			return 'after_posts';
		}

		return null;
	}

	if ( ! $context instanceof WP_Block_Template ) {
		return null;
	}

	if (
		'last_child' === $relative_position
		&& 'wp_template_part' === $context->type
		&& 'footer' === $context->area
	) {
		if (
			'core/template-part' === $anchor_block_type
			|| ( 'core/group' === $anchor_block_type && '' !== site_word_counter_footer_group_signature( $context->content ) )
		) {
			return 'footer';
		}

		return null;
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
 * Returns the footer's outermost group, when the footer is a single group.
 *
 * @param string $content Block markup of a footer part or pattern.
 * @return array|null Parsed block.
 */
function site_word_counter_footer_group( $content ) {
	$named = array();
	foreach ( parse_blocks( (string) $content ) as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			$named[] = $block;
		}
	}

	return 1 === count( $named ) && 'core/group' === $named[0]['blockName'] ? $named[0] : null;
}

/**
 * Returns the pattern a footer part is built from, when the part is a single
 * Pattern block.
 *
 * @param string $content Block markup of the footer part.
 * @return string Pattern slug, or an empty string.
 */
function site_word_counter_footer_pattern_slug( $content ) {
	$named = array();
	foreach ( parse_blocks( (string) $content ) as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			$named[] = $block;
		}
	}

	if ( 1 === count( $named ) && 'core/pattern' === $named[0]['blockName'] ) {
		return (string) ( $named[0]['attrs']['slug'] ?? '' );
	}

	return '';
}

/**
 * Returns the slug of the pattern the active footer part is built from, when
 * the line goes in that pattern's outermost group.
 *
 * @return string[]
 */
function site_word_counter_footer_patterns() {
	static $slugs = null;
	if ( null !== $slugs ) {
		return $slugs;
	}

	$slugs = array();

	$was_paused = site_word_counter_hooks_paused();
	site_word_counter_hooks_paused( true );
	$part = site_word_counter_active_footer_part();
	site_word_counter_hooks_paused( $was_paused );

	$slug = $part ? site_word_counter_footer_pattern_slug( $part->content ) : '';
	if ( $slug && '' !== site_word_counter_footer_group_signature( site_word_counter_pattern_content( $slug ) ) ) {
		$slugs[] = $slug;
	}

	return $slugs;
}

/**
 * Returns the markup Block Hooks is working on, for a template, part, post,
 * or pattern.
 *
 * @param WP_Block_Template|WP_Post|array $context Template, part, post, or pattern.
 * @return string Block markup.
 */
function site_word_counter_context_content( $context ) {
	if ( is_array( $context ) ) {
		if ( isset( $context['content'] ) ) {
			return (string) $context['content'];
		}

		// Theme patterns load their content the first time they're used.
		return site_word_counter_pattern_content( $context['name'] ?? ( $context['slug'] ?? '' ) );
	}

	if ( $context instanceof WP_Post ) {
		return (string) $context->post_content;
	}

	return (string) ( $context->content ?? '' );
}

/**
 * A fingerprint of a block that ignores what can change between two parses
 * of the same markup: the metadata WordPress adds while it works, such as
 * ignoredHookedBlocks, the order of attributes, and whitespace.
 *
 * @param array $block Parsed block.
 * @return string
 */
function site_word_counter_block_signature( $block ) {
	$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
	unset( $attrs['metadata'] );

	return (string) wp_json_encode(
		array(
			(string) ( $block['blockName'] ?? '' ),
			site_word_counter_sort_keys( $attrs ),
			trim( (string) preg_replace( array( '/>\s+</', '/\s+/' ), array( '><', ' ' ), (string) ( $block['innerHTML'] ?? '' ) ) ),
		)
	);
}

/**
 * Sorts an array's keys, all the way down, leaving lists in order.
 *
 * @param mixed $value Value.
 * @return mixed
 */
function site_word_counter_sort_keys( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	if ( ! wp_is_numeric_array( $value ) ) {
		ksort( $value );
	}

	return array_map( 'site_word_counter_sort_keys', $value );
}

/**
 * Returns the fingerprint of the footer's outermost group, when that group
 * can take the line.
 *
 * It can when the footer is a single group with blocks inside it (Block
 * Hooks only offers the last child of a block that has children), and no
 * group inside it has the same fingerprint, so it's the only group that can
 * match. Otherwise the line goes after the footer part's content.
 *
 * @param string $content Block markup of a footer part or pattern.
 * @return string Fingerprint, or an empty string.
 */
function site_word_counter_footer_group_signature( $content ) {
	static $cache = array();

	$key = md5( (string) $content );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$signature = '';
	$group     = site_word_counter_footer_group( $content );
	if ( $group && ! empty( $group['innerBlocks'] ) ) {
		$signature = site_word_counter_block_signature( $group );

		$stack = $group['innerBlocks'];
		while ( $stack ) {
			$block = array_shift( $stack );
			if ( site_word_counter_block_signature( $block ) === $signature ) {
				$signature = '';
				break;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				array_push( $stack, ...$block['innerBlocks'] );
			}
		}
	}

	$cache[ $key ] = $signature;

	return $signature;
}

/**
 * Whether a Block Hooks anchor is the footer's outermost group.
 *
 * @param array  $anchor  Parsed anchor block.
 * @param string $content Block markup of the footer part or pattern.
 * @return bool
 */
function site_word_counter_is_footer_group( $anchor, $content ) {
	if ( 'core/group' !== ( $anchor['blockName'] ?? '' ) || empty( $anchor['innerBlocks'] ) ) {
		return false;
	}

	$signature = site_word_counter_footer_group_signature( $content );

	return '' !== $signature && site_word_counter_block_signature( $anchor ) === $signature;
}

/**
 * Whether the footer's outermost group, or the outermost group of the
 * pattern the footer is built from, takes the line.
 *
 * Block Hooks offers the footer part's last child before the last child of
 * any group inside it, so the part asks this first. It runs the same check
 * on the same blocks the group is checked with later, so exactly one of
 * them adds the line: the group when it matches, and the part when it
 * doesn't.
 *
 * @param array[] $blocks  The footer part's parsed blocks.
 * @param string  $content Block markup of the footer part.
 * @return bool
 */
function site_word_counter_group_takes_footer_line( $blocks, $content ) {
	$named = array();
	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			$named[] = $block;
		}
	}
	if ( 1 !== count( $named ) ) {
		return false;
	}

	if ( 'core/group' === $named[0]['blockName'] ) {
		return site_word_counter_is_footer_group( $named[0], $content );
	}

	$slug = (string) ( $named[0]['attrs']['slug'] ?? '' );
	if ( 'core/pattern' !== $named[0]['blockName'] || ! in_array( $slug, site_word_counter_footer_patterns(), true ) ) {
		return false;
	}

	$pattern = site_word_counter_pattern_content( $slug );
	$group   = site_word_counter_footer_group( $pattern );

	return $group && site_word_counter_is_footer_group( $group, $pattern );
}

/**
 * Whether any block has the counter in its ignoredHookedBlocks, which is
 * how WordPress remembers a line someone deleted in the Site Editor.
 *
 * @param array[] $blocks Parsed blocks.
 * @return bool
 */
function site_word_counter_blocks_ignore_counter( $blocks ) {
	$stack = $blocks;
	while ( $stack ) {
		$block   = array_shift( $stack );
		$ignored = $block['attrs']['metadata']['ignoredHookedBlocks'] ?? array();
		if ( is_array( $ignored ) && in_array( SITE_WORD_COUNTER_BLOCK_NAME, $ignored, true ) ) {
			return true;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			array_push( $stack, ...$block['innerBlocks'] );
		}
	}

	return false;
}

/**
 * Returns the slugs of patterns the active single post template is built from.
 *
 * @return string[]
 */
function site_word_counter_single_template_patterns() {
	static $slugs = null;
	if ( null !== $slugs ) {
		return $slugs;
	}

	$slugs = array();
	foreach ( array( 'single-post', 'single' ) as $template_slug ) {
		$template = get_block_template( get_stylesheet() . '//' . $template_slug );
		if ( ! $template ) {
			continue;
		}

		$stack = parse_blocks( $template->content );
		while ( $stack ) {
			$block = array_shift( $stack );
			if ( 'core/pattern' === $block['blockName'] && ! empty( $block['attrs']['slug'] ) ) {
				$slugs[] = $block['attrs']['slug'];
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				array_push( $stack, ...$block['innerBlocks'] );
			}
		}
		break;
	}

	return $slugs;
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
	if ( site_word_counter_hooks_paused() || ! get_option( 'site_word_counter_placements' ) ) {
		return $hooked_block_types;
	}

	$placement = site_word_counter_match_placement( $relative_position, $anchor_block_type, $context );

	if (
		$placement
		&& site_word_counter_placement_enabled( $placement )
		&& ! site_word_counter_content_has_counter( site_word_counter_context_content( $context ) )
	) {
		$hooked_block_types[] = SITE_WORD_COUNTER_BLOCK_NAME;
	}

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'site_word_counter_hooked_block_types', 10, 4 );

/**
 * Swaps the bare counter for the "words published since" line.
 *
 * In the footer, the line takes the text style of the footer's last line.
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
	if ( null === $parsed_hooked_block || site_word_counter_hooks_paused() ) {
		return $parsed_hooked_block;
	}

	$placement = site_word_counter_match_placement( $relative_position, $parsed_anchor_block['blockName'] ?? '', $context );
	if ( ! $placement ) {
		return $parsed_hooked_block;
	}

	if ( 'footer' === $placement && 'core/group' === $parsed_anchor_block['blockName'] ) {
		// Only the footer's outermost group takes the line. Returning null also
		// keeps WordPress from marking the other groups as having ignored it.
		if ( ! site_word_counter_is_footer_group( $parsed_anchor_block, site_word_counter_context_content( $context ) ) ) {
			return null;
		}

		$markup = site_word_counter_line_markup(
			site_word_counter_children_alignment( $parsed_anchor_block ),
			site_word_counter_text_attrs( $parsed_anchor_block['innerBlocks'] )
		);
	} elseif ( 'footer' === $placement ) {
		// The part itself takes the line only when its outermost group won't,
		// and not when a line was deleted from that group or is already there.
		$blocks = $parsed_anchor_block['innerBlocks'] ?? array();
		if (
			site_word_counter_group_takes_footer_line( $blocks, site_word_counter_context_content( $context ) )
			|| site_word_counter_blocks_ignore_counter( $blocks )
			|| site_word_counter_content_has_counter( serialize_blocks( site_word_counter_expand_patterns( $blocks ) ) )
		) {
			return null;
		}

		$markup = site_word_counter_footer_line_markup(
			site_word_counter_text_attrs( $blocks ),
			site_word_counter_blocks_alignment( $blocks )
		);
	} else {
		$markup = site_word_counter_line_markup();
	}

	$blocks = parse_blocks( $markup );

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
 * WordPress records deleted hooked blocks in ignoredHookedBlocks, and won't
 * add them again: on the anchor block when it's in the saved markup, like
 * the footer's outermost group or Post Content, or in the saved template's
 * _wp_ignored_hooked_blocks meta when the anchor is the template part
 * itself. It records them there whenever the line was added, too, so the
 * line only counts as deleted when the saved markup doesn't have it.
 *
 * @param string $template_id Template ID, such as "twentytwentyfive//footer".
 * @return bool
 */
function site_word_counter_removed_in_editor( $template_id ) {
	$template = get_block_template( $template_id, str_contains( $template_id, '//single' ) ? 'wp_template' : 'wp_template_part' );
	if ( ! $template || empty( $template->wp_id ) ) {
		return false;
	}

	$post = get_post( $template->wp_id );
	if ( ! $post || site_word_counter_content_has_counter( $post->post_content ) ) {
		return false;
	}

	$ignored = json_decode( (string) get_post_meta( $template->wp_id, '_wp_ignored_hooked_blocks', true ), true );
	if ( is_array( $ignored ) && in_array( SITE_WORD_COUNTER_BLOCK_NAME, $ignored, true ) ) {
		return true;
	}

	return site_word_counter_blocks_ignore_counter( parse_blocks( $post->post_content ) );
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
