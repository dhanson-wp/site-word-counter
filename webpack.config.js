/**
 * The @wordpress/scripts default config, plus the settings screen entries.
 */
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// @wordpress/theme is only a WordPress script from 7.0 on, and the plugin
// supports 6.9, so bundle it like the other Design System packages.
const isBundledTheme = ( request ) =>
	request === '@wordpress/theme' || request.startsWith( '@wordpress/theme/' );

const withSettingsScreen = ( config ) => ( {
	...config,
	entry: async () => {
		const defaultEntries =
			typeof config.entry === 'function'
				? await config.entry()
				: config.entry;

		return {
			...defaultEntries,
			'admin/index': './src/admin/index.js',
			'admin/design-tokens': './src/admin/design-tokens.js',
		};
	},
	plugins: config.plugins.map( ( plugin ) =>
		plugin instanceof DependencyExtractionWebpackPlugin
			? new DependencyExtractionWebpackPlugin( {
					requestToExternal: ( request ) =>
						isBundledTheme( request ) ? false : undefined,
					requestToHandle: ( request ) =>
						isBundledTheme( request ) ? false : undefined,
			  } )
			: plugin
	),
} );

module.exports = Array.isArray( defaultConfig )
	? [ withSettingsScreen( defaultConfig[ 0 ] ), ...defaultConfig.slice( 1 ) ]
	: withSettingsScreen( defaultConfig );
