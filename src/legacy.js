/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import tallyIcon from './icon';
import useTotal from './use-total';
import CounterPreview from './counter-preview';

export const LEGACY_NAME = 'telex/block-site-word-counter';
const CURRENT_NAME = 'site-word-counter/site-word-counter';

/**
 * Maps the Telex block's attributes to the current block's.
 *
 * @param {Object} attributes Legacy attributes.
 * @return {Object} Current attributes.
 */
export function mapLegacyAttributes( attributes ) {
	const { textAlignment, ...rest } = attributes;

	if ( textAlignment === 'center' || textAlignment === 'right' ) {
		rest.style = {
			...rest.style,
			typography: { ...rest.style?.typography, textAlign: textAlignment },
		};
	}

	return rest;
}

export const legacyTransform = {
	type: 'block',
	blocks: [ LEGACY_NAME ],
	transform: ( attributes ) =>
		createBlock( CURRENT_NAME, mapLegacyAttributes( attributes ) ),
};

function LegacyEdit( { attributes, clientId } ) {
	const { replaceBlocks } = useDispatch( blockEditorStore );
	const total = useTotal();

	// Show the counter the way the site does: same number, same alignment.
	const { textAlignment } = attributes;
	const blockProps = useBlockProps( {
		className:
			textAlignment === 'center' || textAlignment === 'right'
				? `has-text-align-${ textAlignment }`
				: undefined,
	} );

	const convert = () =>
		replaceBlocks(
			clientId,
			createBlock( CURRENT_NAME, mapLegacyAttributes( attributes ) )
		);

	return (
		<>
			<BlockControls group="other">
				<ToolbarGroup>
					<ToolbarButton onClick={ convert }>
						{ __( 'Convert', 'site-word-counter' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __( 'Earlier version', 'site-word-counter' ) }
				>
					<p>
						{ __(
							'This counter was made with an earlier version of Site Word Counter. It still works on your site. Convert it to change its settings.',
							'site-word-counter'
						) }
					</p>
					<Button variant="secondary" onClick={ convert }>
						{ __( 'Convert', 'site-word-counter' ) }
					</Button>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<CounterPreview format="full" total={ total } />
			</div>
		</>
	);
}

/**
 * Registers the Telex block name so existing content still opens in the editor.
 */
export function registerLegacyBlock() {
	registerBlockType( LEGACY_NAME, {
		apiVersion: 3,
		title: __( 'Site Word Counter (legacy)', 'site-word-counter' ),
		category: 'widgets',
		icon: tallyIcon,
		attributes: {
			enableAnimation: { type: 'boolean', default: true },
			textAlignment: { type: 'string', default: 'left' },
		},
		supports: {
			inserter: false,
			html: false,
			color: { text: true, background: false },
			typography: { fontSize: true },
			spacing: { margin: true, padding: true },
		},
		edit: LegacyEdit,
		save: () => null,
	} );
}
