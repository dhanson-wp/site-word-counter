/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import apiFetch from '@wordpress/api-fetch';
import { Button, ProgressBar } from '@wordpress/components';
import { dateI18n, getSettings } from '@wordpress/date';
import { useState } from '@wordpress/element';
import { Badge, Card, Skeleton, Stack, Text } from '@wordpress/ui';

/**
 * Counting status, with a button that recounts every post in batches.
 *
 * @param {Object}                    props          Component props.
 * @param {?Object}                   props.status   Status from the REST route, or null while loading.
 * @param {(status: Object) => void}  props.onChange Receives the new status after a recount.
 * @param {(message: string) => void} props.onError  Receives an error message.
 */
export default function StatusCard( { status, onChange, onError } ) {
	const [ progress, setProgress ] = useState( null );

	const recount = async () => {
		const toCount = status?.to_count ?? 0;
		setProgress( 0 );
		speak( __( 'Recounting words.', 'site-word-counter' ) );

		try {
			let offset = 0;
			let result;
			do {
				result = await apiFetch( {
					path: '/site-word-counter/v1/recount',
					method: 'POST',
					data: { offset },
				} );
				offset = result.next_offset;
				setProgress(
					toCount ? Math.min( 100, ( offset / toCount ) * 100 ) : 100
				);
			} while ( ! result.done );

			onChange( result.status );
			speak(
				sprintf(
					/* translators: %s: total number of words, formatted. */
					__( 'Recount finished. %s words.', 'site-word-counter' ),
					result.status.formatted
				)
			);
		} catch ( error ) {
			onError(
				error?.message ??
					__( 'The recount stopped early.', 'site-word-counter' )
			);
		} finally {
			setProgress( null );
		}
	};

	const isRecounting = progress !== null;

	return (
		<Card.Root>
			<Card.Header>
				<Card.Title>{ __( 'Status', 'site-word-counter' ) }</Card.Title>
			</Card.Header>
			<Card.Content>
				{ ! status ? (
					<Stack direction="column" gap="sm">
						<Skeleton height="32px" width="120px" />
						<Skeleton height="16px" width="240px" />
					</Stack>
				) : (
					<Stack direction="column" gap="md">
						<Stack
							direction="row"
							gap="sm"
							align="center"
							wrap="wrap"
						>
							<Text variant="heading-xl" render={ <p /> }>
								{ sprintf(
									/* translators: %s: total number of words, formatted. */
									__( '%s words', 'site-word-counter' ),
									status.formatted
								) }
							</Text>
							{ status.backfill_running ? (
								<Badge intent="informational">
									{ __(
										'Counting older posts',
										'site-word-counter'
									) }
								</Badge>
							) : (
								<Badge intent="stable">
									{ __( 'Up to date', 'site-word-counter' ) }
								</Badge>
							) }
						</Stack>
						<Text variant="body-md" render={ <p /> }>
							{ sprintf(
								/* translators: 1: posts counted, 2: posts to count. */
								_n(
									'%1$s of %2$s post counted.',
									'%1$s of %2$s posts counted.',
									status.to_count,
									'site-word-counter'
								),
								status.counted.toLocaleString(),
								status.to_count.toLocaleString()
							) }{ ' ' }
							{ status.last_recount
								? sprintf(
										/* translators: %s: date and time of the last recount. */
										__(
											'Last full recount: %s.',
											'site-word-counter'
										),
										dateI18n(
											getSettings().formats.datetime,
											status.last_recount
										)
									)
								: __(
										'No full recount yet.',
										'site-word-counter'
									) }
						</Text>
						{ isRecounting && (
							<ProgressBar
								value={ progress }
								aria-label={ __(
									'Recount progress',
									'site-word-counter'
								) }
							/>
						) }
						<div>
							<Button
								__next40pxDefaultSize
								variant="secondary"
								isBusy={ isRecounting }
								disabled={ isRecounting }
								accessibleWhenDisabled
								onClick={ recount }
							>
								{ isRecounting
									? __( 'Recounting…', 'site-word-counter' )
									: __( 'Recount now', 'site-word-counter' ) }
							</Button>
						</div>
						<Text variant="body-sm" render={ <p /> }>
							{ __(
								'Counts every post again. Use it after changing how words are counted, or if the total looks wrong.',
								'site-word-counter'
							) }
						</Text>
					</Stack>
				) }
			</Card.Content>
		</Card.Root>
	);
}
