/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Page, getAdminThemeColors } from '@wordpress/admin-ui';
import { Button, SnackbarList, ToggleControl } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import { ThemeProvider } from '@wordpress/theme';
import { paragraph } from '@wordpress/icons';
import { Card, Icon, Skeleton, Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import PlacementCard from './placement-card';
import PostTypesControl from './post-types-control';
import StatusCard from './status-card';

const POST_TYPES = 'site_word_counter_post_types';
const DISABLE_ANIMATION = 'site_word_counter_disable_animation';
const PLACEMENTS = 'site_word_counter_placements';

const postTypeOptions = window.siteWordCounterSettings?.postTypes ?? [];

function Notices() {
	const notices = useSelect(
		( select ) =>
			select( noticesStore )
				.getNotices()
				.filter( ( notice ) => notice.type === 'snackbar' ),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );

	return (
		<SnackbarList
			className="site-word-counter-settings__snackbars"
			notices={ notices }
			onRemove={ removeNotice }
		/>
	);
}

export default function SettingsPage() {
	// Only the accent follows the admin color scheme. Its background color is
	// the admin menu's, which would turn the whole screen dark.
	const themeColors = useMemo(
		() => ( { primary: getAdminThemeColors().primary } ),
		[]
	);
	const [ status, setStatus ] = useState( null );

	const { settings, hasEdits, isSaving } = useSelect( ( select ) => {
		const store = select( coreStore );
		// getEntityRecord starts the fetch; getEditedEntityRecord adds unsaved edits.
		store.getEntityRecord( 'root', 'site' );
		return {
			settings: store.getEditedEntityRecord( 'root', 'site' ),
			hasEdits: store.hasEditsForEntityRecord( 'root', 'site' ),
			isSaving: store.isSavingEntityRecord( 'root', 'site' ),
		};
	}, [] );

	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const showError = useCallback(
		( message ) => createErrorNotice( message, { type: 'snackbar' } ),
		[ createErrorNotice ]
	);

	const loadStatus = useCallback( () => {
		apiFetch( { path: '/site-word-counter/v1/status' } )
			.then( setStatus )
			.catch( ( error ) => showError( error.message ) );
	}, [ showError ] );

	useEffect( loadStatus, [ loadStatus ] );

	const edit = ( key, value ) =>
		editEntityRecord( 'root', 'site', undefined, { [ key ]: value } );

	const save = async () => {
		try {
			await saveEditedEntityRecord( 'root', 'site', undefined, {
				throwOnError: true,
			} );
			createSuccessNotice( __( 'Settings saved.', 'site-word-counter' ), {
				type: 'snackbar',
			} );
			loadStatus();
		} catch ( error ) {
			showError(
				error?.message ??
					__( 'The settings couldn’t be saved.', 'site-word-counter' )
			);
		}
	};

	const isLoading = ! settings || settings[ POST_TYPES ] === undefined;

	return (
		<ThemeProvider color={ themeColors }>
			<div className="site-word-counter-settings__root">
				<Page
					hasPadding
					visual={ <Icon icon={ paragraph } /> }
					title={ __( 'Site Word Counter', 'site-word-counter' ) }
					subTitle={ __(
						'Count your published words and show them off across your site.',
						'site-word-counter'
					) }
					actions={
						<Button
							variant="primary"
							isBusy={ isSaving }
							disabled={ ! hasEdits || isSaving }
							accessibleWhenDisabled
							onClick={ save }
						>
							{ __( 'Save', 'site-word-counter' ) }
						</Button>
					}
				>
					<Stack
						direction="column"
						gap="lg"
						className="site-word-counter-settings__content"
					>
						{ isLoading && (
							<Card.Root>
								<Card.Content>
									<Stack direction="column" gap="sm">
										<Skeleton height="20px" width="160px" />
										<Skeleton height="16px" width="280px" />
										<Skeleton height="16px" width="240px" />
									</Stack>
								</Card.Content>
							</Card.Root>
						) }
						{ ! isLoading && (
							<>
								<Card.Root>
									<Card.Header>
										<Card.Title>
											{ __(
												'What counts',
												'site-word-counter'
											) }
										</Card.Title>
									</Card.Header>
									<Card.Content>
										<PostTypesControl
											label={ __(
												'Count words in',
												'site-word-counter'
											) }
											options={ postTypeOptions }
											value={ settings[ POST_TYPES ] }
											onChange={ ( value ) =>
												edit( POST_TYPES, value )
											}
										/>
									</Card.Content>
								</Card.Root>
								<PlacementCard
									value={ settings[ PLACEMENTS ] }
									formatted={ status?.formatted ?? '…' }
									onChange={ ( value ) =>
										edit( PLACEMENTS, value )
									}
								/>
								<Card.Root>
									<Card.Header>
										<Card.Title>
											{ __(
												'Display',
												'site-word-counter'
											) }
										</Card.Title>
									</Card.Header>
									<Card.Content>
										<ToggleControl
											__nextHasNoMarginBottom
											label={ __(
												'Turn off counter animations across the site',
												'site-word-counter'
											) }
											help={ __(
												'Every counter shows its final number right away, whatever its block setting says. Visitors who ask their device for reduced motion never see the animation either way.',
												'site-word-counter'
											) }
											checked={
												!! settings[ DISABLE_ANIMATION ]
											}
											onChange={ ( value ) =>
												edit( DISABLE_ANIMATION, value )
											}
										/>
									</Card.Content>
								</Card.Root>
							</>
						) }
						<StatusCard
							status={ status }
							onChange={ setStatus }
							onError={ showError }
						/>
					</Stack>
				</Page>
				<Notices />
			</div>
		</ThemeProvider>
	);
}
