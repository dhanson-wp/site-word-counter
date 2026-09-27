/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import SettingsPage from './settings-page';
import './settings.scss';

domReady( () => {
	const root = document.getElementById( 'site-word-counter-settings' );
	if ( root ) {
		createRoot( root ).render( <SettingsPage /> );
	}
} );
