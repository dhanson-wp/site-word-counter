<?php
/**
 * Word counting.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Counts the words in a piece of post content.
 *
 * Shortcodes, HTML tags, and block comments are removed first. Characters
 * from scripts written without spaces between words (Chinese, Japanese,
 * Thai, and similar) count as one word each. Everything else counts runs
 * of letters and numbers, keeping apostrophes and hyphens inside a word.
 *
 * @param string $content Raw post content.
 * @return int Number of words.
 */
function site_word_counter_count_text( $content ) {
	$text = strip_shortcodes( (string) $content );

	// Keep words in neighboring blocks apart: block comments and block-level
	// tags become spaces before the rest of the markup is stripped.
	$text = preg_replace( '/<!--.*?-->/s', ' ', $text );
	$text = preg_replace( '#</?(?:address|article|aside|blockquote|br|dd|details|div|dl|dt|figcaption|figure|footer|h[1-6]|header|hr|li|main|nav|ol|p|pre|section|summary|table|td|th|tr|ul)\b[^>]*>#i', ' ', (string) $text );
	$text = wp_strip_all_tags( (string) $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = wp_check_invalid_utf8( $text, true );

	// The lookahead skips punctuation such as the ideographic full stop, which
	// PCRE2 also matches as part of these scripts.
	$unspaced_scripts = '/(?=\p{L})[\p{Han}\p{Hiragana}\p{Katakana}\p{Thai}\p{Lao}\p{Khmer}\p{Myanmar}]/u';

	$unspaced = (int) preg_match_all( $unspaced_scripts, $text );
	$text     = (string) preg_replace( '/[\p{Han}\p{Hiragana}\p{Katakana}\p{Thai}\p{Lao}\p{Khmer}\p{Myanmar}]/u', ' ', $text );
	$words    = (int) preg_match_all( "/[\p{L}\p{M}\p{N}]+(?:['\x{2019}\-][\p{L}\p{M}\p{N}]+)*/u", $text );

	/**
	 * Filters the word count for a piece of post content.
	 *
	 * @param int    $count   Number of words.
	 * @param string $content Raw post content.
	 */
	return max( 0, (int) apply_filters( 'site_word_counter_count_text', $unspaced + $words, $content ) );
}
