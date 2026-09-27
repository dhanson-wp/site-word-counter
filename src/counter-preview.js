/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * The number as the editor shows it, shared by the current and legacy blocks.
 *
 * @param {Object} props        Component props.
 * @param {string} props.format "full" or "compact".
 * @param {Object} props.total  State from useTotal(): formatted totals, whether every post is counted, and whether loading failed.
 */
export default function CounterPreview( { format, total } ) {
	const { formatted, backfillComplete, error } = total;

	let number = '…';
	if ( error ) {
		number = __( 'Word count unavailable', 'site-word-counter' );
	} else if ( formatted ) {
		number = formatted[ format ] ?? formatted.full;
	}

	return (
		<>
			<span className="wp-block-site-word-counter-site-word-counter__number">
				{ number }
			</span>
			{ ! backfillComplete && (
				<span className="wp-block-site-word-counter-site-word-counter__notice">
					{ __( 'Still counting older posts', 'site-word-counter' ) }
				</span>
			) }
		</>
	);
}
