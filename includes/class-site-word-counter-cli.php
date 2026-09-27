<?php
/**
 * WP-CLI commands.
 *
 * @package SiteWordCounter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Counts words for Site Word Counter.
 */
class Site_Word_Counter_CLI {

	/**
	 * Counts posts that don't have a stored word count yet.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Recount every post, including posts that already have a count. Use it after changing how words are counted.
	 *
	 * [--batch-size=<number>]
	 * : Posts to count per batch.
	 * ---
	 * default: 200
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp site-word-counter recount
	 *     wp site-word-counter recount --all
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function recount( $args, $assoc_args ) {
		$all        = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );
		$batch_size = max( 1, (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'batch-size', SITE_WORD_COUNTER_BATCH_SIZE ) );
		$to_count   = site_word_counter_count_posts( ! $all );

		if ( 0 === $to_count ) {
			WP_CLI::success( 'Every post already has a word count.' );
			$this->total();
			return;
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Counting words', $to_count );
		$offset   = 0;

		do {
			$result = site_word_counter_process_batch( $all, $offset, $batch_size );
			$offset = $result['next_offset'];
			$progress->tick( $result['processed'] );
		} while ( ! $result['done'] );

		$progress->finish();
		WP_CLI::success( sprintf( 'Counted %d posts.', $to_count ) );
		$this->total();
	}

	/**
	 * Prints the site's total word count.
	 *
	 * ## EXAMPLES
	 *
	 *     wp site-word-counter total
	 */
	public function total() {
		WP_CLI::line( sprintf( 'Total words: %d', site_word_counter_get_total() ) );
	}
}

WP_CLI::add_command( 'site-word-counter', 'Site_Word_Counter_CLI' );
