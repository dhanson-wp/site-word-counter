/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import Edit from './edit';
import metadata from './block.json';
import { registerLegacyBlock, legacyTransform } from './legacy';

registerBlockType( metadata.name, {
	edit: Edit,
	transforms: {
		from: [ legacyTransform ],
	},
} );

registerLegacyBlock();
