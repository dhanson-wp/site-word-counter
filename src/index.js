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
import tallyIcon from './icon';
import { registerLegacyBlock, legacyTransform } from './legacy';

registerBlockType( metadata.name, {
	icon: tallyIcon,
	edit: Edit,
	transforms: {
		from: [ legacyTransform ],
	},
} );

registerLegacyBlock();
