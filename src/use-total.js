/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

/**
 * Fetches the site's total word count from the plugin's REST route.
 *
 * @return {{ total: ?number, formatted: ?Object, backfillComplete: boolean, animationDisabled: boolean, settingsUrl: ?string, error: boolean }} Total state.
 */
export default function useTotal() {
	const [ state, setState ] = useState( {
		total: null,
		formatted: null,
		backfillComplete: true,
		animationDisabled: false,
		settingsUrl: null,
		error: false,
	} );

	useEffect( () => {
		let cancelled = false;

		apiFetch( { path: '/site-word-counter/v1/total' } )
			.then( ( response ) => {
				if ( ! cancelled ) {
					setState( {
						total: response.total,
						formatted: response.formatted,
						backfillComplete: response.backfill_complete,
						animationDisabled: response.animation_disabled,
						settingsUrl: response.settings_url,
						error: false,
					} );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setState( ( previous ) => ( {
						...previous,
						error: true,
					} ) );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [] );

	return state;
}
