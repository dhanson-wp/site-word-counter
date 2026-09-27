/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useTotal from './use-total';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { enableAnimation, format } = attributes;
	const {
		formatted,
		backfillComplete,
		animationDisabled,
		settingsUrl,
		error,
	} = useTotal();

	const blockProps = useBlockProps();

	let number = '…';
	if ( error ) {
		number = __( 'Word count unavailable', 'site-word-counter' );
	} else if ( formatted ) {
		number = formatted[ format ] ?? formatted.full;
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'site-word-counter' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Number format', 'site-word-counter' ) }
						value={ format }
						options={ [
							{
								label: __(
									'Full (33,895)',
									'site-word-counter'
								),
								value: 'full',
							},
							{
								label: __(
									'Compact (33.9K)',
									'site-word-counter'
								),
								value: 'compact',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { format: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Animate the number',
							'site-word-counter'
						) }
						help={
							animationDisabled ? (
								<>
									{ __(
										'Counter animations are turned off for the whole site.',
										'site-word-counter'
									) }{ ' ' }
									{ settingsUrl && (
										<a href={ settingsUrl }>
											{ __(
												'Change this in Settings › Word Counter.',
												'site-word-counter'
											) }
										</a>
									) }
								</>
							) : (
								__(
									'Counts up when the block scrolls into view. Visitors who prefer reduced motion always see the final number.',
									'site-word-counter'
								)
							)
						}
						checked={ enableAnimation && ! animationDisabled }
						disabled={ animationDisabled }
						onChange={ ( value ) =>
							setAttributes( { enableAnimation: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<span className="wp-block-site-word-counter-site-word-counter__number">
					{ number }
				</span>
				{ ! backfillComplete && (
					<span className="wp-block-site-word-counter-site-word-counter__notice">
						{ __(
							'Still counting older posts',
							'site-word-counter'
						) }
					</span>
				) }
			</div>
		</>
	);
}
