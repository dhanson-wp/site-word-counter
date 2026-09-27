/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { Stack, Text } from '@wordpress/ui';

/**
 * A checkbox for each public post type.
 *
 * @param {Object}                    props          Component props.
 * @param {string}                    props.label    Group label.
 * @param {Array<Object>}             props.options  Post types, as { value, label }.
 * @param {string[]}                  props.value    Selected post type slugs.
 * @param {(value: string[]) => void} props.onChange Receives the new selection.
 */
export default function PostTypesControl( {
	label,
	options,
	value,
	onChange,
} ) {
	const selected = value ?? [];

	const toggle = ( slug, isChecked ) => {
		const next = isChecked
			? [ ...selected, slug ]
			: selected.filter( ( item ) => item !== slug );
		onChange( next );
	};

	return (
		<fieldset className="site-word-counter-settings__post-types">
			<legend>
				<Text variant="body-md">{ label }</Text>
			</legend>
			<Stack direction="column" gap="sm">
				{ options.map( ( option ) => (
					<CheckboxControl
						key={ option.value }
						label={ option.label }
						checked={ selected.includes( option.value ) }
						onChange={ ( isChecked ) =>
							toggle( option.value, isChecked )
						}
					/>
				) ) }
			</Stack>
			<Text
				variant="body-sm"
				className="site-word-counter-settings__help"
			>
				{ selected.length === 0
					? __(
							'Pick at least one. With none picked, posts and pages count.',
							'site-word-counter'
						)
					: __(
							'Titles, drafts, and private posts never count.',
							'site-word-counter'
						) }
			</Text>
		</fieldset>
	);
}
