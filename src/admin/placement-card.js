/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Card, Stack, Text } from '@wordpress/ui';

const placementInfo = window.siteWordCounterSettings?.placement ?? {
	isBlockTheme: false,
	placements: [],
	firstYear: '',
};

const COPY = {
	footer: {
		caption: __( 'Word count in your footer', 'site-word-counter' ),
		description: __(
			'At the end of your site’s footer, on every page.',
			'site-word-counter'
		),
	},
	after_posts: {
		caption: __(
			'Word count at the end of each post',
			'site-word-counter'
		),
		description: __(
			'After the content of every single post.',
			'site-word-counter'
		),
	},
};

const UNAVAILABLE = {
	no_footer: __(
		'Your theme doesn’t have a footer template part, so there’s no footer to add it to. You can add the “Words published since” pattern to any template in the Site Editor instead.',
		'site-word-counter'
	),
	footer_not_in_area: __(
		'Your theme’s footer template part isn’t set as a footer area, so the word count can’t be added to it for you. Add the “Words published since” pattern to it in the Site Editor instead.',
		'site-word-counter'
	),
	no_single_template: __(
		'Your theme doesn’t have a single post template, so the word count can’t be added after your posts for you.',
		'site-word-counter'
	),
};

function placementDescription( placement ) {
	if ( ! placement.enabled ) {
		return (
			UNAVAILABLE[ placement.unavailable ] ??
			__(
				'Your theme doesn’t have a template for this, so the word count can’t be added here for you.',
				'site-word-counter'
			)
		);
	}
	if ( placement.removedInEditor ) {
		return __(
			'You removed it from this template in the Site Editor, so it won’t be added again. Add it back there with the “Words published since” pattern.',
			'site-word-counter'
		);
	}
	return COPY[ placement.id ].description;
}

function Line( { formatted } ) {
	return (
		<span className="site-word-counter-tile__line">
			<strong>{ formatted }</strong>{ ' ' }
			{ sprintf(
				/* translators: %s: the year of the site's first published post. */
				__( 'words published since %s.', 'site-word-counter' ),
				placementInfo.firstYear
			) }
		</span>
	);
}

function FooterWireframe( { formatted } ) {
	return (
		<div className="site-word-counter-tile__page">
			<div className="site-word-counter-tile__header">
				<span className="site-word-counter-tile__bar is-logo" />
				<span className="site-word-counter-tile__nav">
					<span className="site-word-counter-tile__bar" />
					<span className="site-word-counter-tile__bar" />
					<span className="site-word-counter-tile__bar" />
				</span>
			</div>
			<div className="site-word-counter-tile__body">
				<span className="site-word-counter-tile__bar is-title" />
				<span className="site-word-counter-tile__bar" />
				<span className="site-word-counter-tile__bar" />
				<span className="site-word-counter-tile__bar is-short" />
			</div>
			<div className="site-word-counter-tile__footer">
				<span className="site-word-counter-tile__bar is-logo" />
				<Line formatted={ formatted } />
			</div>
		</div>
	);
}

function PostWireframe( { formatted } ) {
	return (
		<div className="site-word-counter-tile__page">
			<div className="site-word-counter-tile__body">
				<span className="site-word-counter-tile__bar is-title" />
				<span className="site-word-counter-tile__bar" />
				<span className="site-word-counter-tile__bar" />
				<span className="site-word-counter-tile__bar is-short" />
				<span className="site-word-counter-tile__bar" />
				<span className="site-word-counter-tile__bar is-short" />
			</div>
			<div className="site-word-counter-tile__after">
				<Line formatted={ formatted } />
			</div>
		</div>
	);
}

/**
 * "Show it on your site": visual tiles for adding the word count to the theme.
 *
 * @param {Object}                    props           Component props.
 * @param {string[]}                  props.value     Turned-on placements.
 * @param {string}                    props.formatted The formatted total, for the previews.
 * @param {(value: string[]) => void} props.onChange  Receives the new placements.
 */
export default function PlacementCard( { value, formatted, onChange } ) {
	const selected = value ?? [];
	const toggle = ( id, isChecked ) =>
		onChange(
			isChecked
				? [ ...selected, id ]
				: selected.filter( ( item ) => item !== id )
		);

	return (
		<Card.Root>
			<Card.Header>
				<Card.Title>
					{ __( 'Show it on your site', 'site-word-counter' ) }
				</Card.Title>
			</Card.Header>
			<Card.Content>
				{ ! placementInfo.isBlockTheme ? (
					<Text variant="body-md" render={ <p /> }>
						{ __(
							'Your theme doesn’t use editable templates, so the word count can’t be added for you. Add the Site Word Counter block, or its “Words published since” pattern, anywhere blocks are allowed.',
							'site-word-counter'
						) }
					</Text>
				) : (
					<Stack direction="column" gap="md">
						<Text variant="body-md" render={ <p /> }>
							{ __(
								'Add your word count to your theme automatically. Change the wording or remove it in the Site Editor.',
								'site-word-counter'
							) }
						</Text>
						<Text
							variant="body-sm"
							render={ <h3 /> }
							className="site-word-counter-tiles__label"
						>
							{ __( 'Every page and post', 'site-word-counter' ) }
						</Text>
						<div className="site-word-counter-tiles">
							{ placementInfo.placements.map( ( placement ) => {
								const id = `site-word-counter-placement-${ placement.id }`;
								const isChecked = selected.includes(
									placement.id
								);
								const Wireframe =
									placement.id === 'footer'
										? FooterWireframe
										: PostWireframe;

								return (
									<div
										key={ placement.id }
										className="site-word-counter-tile"
									>
										<label
											htmlFor={ id }
											className={
												'site-word-counter-tile__frame' +
												( isChecked
													? ' is-checked'
													: '' ) +
												( placement.enabled
													? ''
													: ' is-disabled' )
											}
										>
											<input
												id={ id }
												type="checkbox"
												className="site-word-counter-tile__checkbox"
												checked={ isChecked }
												disabled={ ! placement.enabled }
												aria-describedby={ `${ id }-description` }
												onChange={ ( event ) =>
													toggle(
														placement.id,
														event.target.checked
													)
												}
											/>
											<span aria-hidden="true">
												<Wireframe
													formatted={ formatted }
												/>
											</span>
											<span className="site-word-counter-tile__caption">
												{ COPY[ placement.id ].caption }
											</span>
										</label>
										<Text
											variant="body-sm"
											render={ <p /> }
											id={ `${ id }-description` }
											className="site-word-counter-tile__description"
										>
											{ placementDescription(
												placement
											) }
										</Text>
										{ placement.editUrl && (
											<a href={ placement.editUrl }>
												{ __(
													'Preview and edit',
													'site-word-counter'
												) }
											</a>
										) }
									</div>
								);
							} ) }
						</div>
						<Text
							variant="body-sm"
							render={ <p /> }
							className="site-word-counter-settings__help"
						>
							{ __(
								'Once you edit and save the line in the Site Editor, it becomes part of that template, so turning it off here won’t remove it. Remove it in the Site Editor instead.',
								'site-word-counter'
							) }
						</Text>
					</Stack>
				) }
			</Card.Content>
		</Card.Root>
	);
}
