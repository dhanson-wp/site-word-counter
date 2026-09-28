/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { escapeHTML } from '@wordpress/escape-html';
import { decodeEntities } from '@wordpress/html-entities';
import {
	create,
	insert,
	insertObject,
	registerFormatType,
} from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import tallyIcon from '../icon';

export const FORMAT_NAME = 'site-word-counter/inline-count';

// The marker the front end looks for. Keep it in step with
// SITE_WORD_COUNTER_INLINE_CLASS in includes/inline-count.php.
const CLASS_NAME = 'site-word-counter-inline';

let totalRequest = null;

/**
 * Fetches the full, localized total once per editor session.
 *
 * Format edit components mount every time a rich text field is selected, so
 * the request is shared instead of repeated for every block.
 *
 * @return {Promise<string>} The total, such as "37,051".
 */
function fetchFormattedTotal() {
	if ( ! totalRequest ) {
		totalRequest = apiFetch( { path: '/site-word-counter/v1/total' } )
			.then( ( response ) => decodeEntities( response.formatted.full ) )
			.catch( ( error ) => {
				totalRequest = null;
				throw error;
			} );
	}

	return totalRequest;
}

/**
 * The "Word count" item in the rich text toolbar's More menu.
 *
 * The count is an uneditable object, like a footnote marker, so nobody can
 * type inside it by accident. The front end swaps its text for the live total.
 *
 * @param {Object}                    props                Format edit props.
 * @param {Object}                    props.value          Rich text value.
 * @param {( value: Object ) => void} props.onChange       Updates the rich text value.
 * @param {() => void}                props.onFocus        Returns focus to the rich text field.
 * @param {boolean}                   props.isObjectActive Whether an inline count is selected.
 */
function InlineCountButton( { value, onChange, onFocus, isObjectActive } ) {
	// Load the total ahead of a click, so inserting it feels instant.
	useEffect( () => {
		fetchFormattedTotal().catch( () => {} );
	}, [] );

	function unwrap() {
		// Keep the number as plain text, the same as removing any other format.
		const replacement = value.replacements[ value.start ];
		const text = create( { html: replacement?.innerHTML ?? '' } ).text;
		const newValue = insert( value, text, value.start, value.end );
		onChange( newValue );
		onFocus();
	}

	function insertCount() {
		const { start, end } = value;

		fetchFormattedTotal()
			.then( ( formatted ) => {
				const newValue = insertObject(
					value,
					{
						type: FORMAT_NAME,
						innerHTML: escapeHTML( formatted ),
					},
					start,
					end
				);
				onChange( newValue );
				onFocus();
			} )
			.catch( () => {} );
	}

	return (
		<RichTextToolbarButton
			icon={ tallyIcon }
			title={ __( 'Word count', 'site-word-counter' ) }
			onClick={ isObjectActive ? unwrap : insertCount }
			isActive={ isObjectActive }
		/>
	);
}

registerFormatType( FORMAT_NAME, {
	title: __( 'Word count', 'site-word-counter' ),
	tagName: 'span',
	className: CLASS_NAME,
	// Treat the count as one uneditable piece, the way core treats footnotes.
	contentEditable: false,
	edit: InlineCountButton,
} );
