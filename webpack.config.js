/**
 * The @wordpress/scripts default config, plus the settings screen and the
 * front-end view module entries.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const withEntries = ( config, entries ) => ( {
	...config,
	entry: async () => {
		const defaultEntries =
			typeof config.entry === 'function'
				? await config.entry()
				: config.entry;

		return { ...defaultEntries, ...entries };
	},
} );

// The front-end view module is registered and enqueued from PHP only when a
// counter animates, so it's its own module entry rather than a block.json
// viewScriptModule.
module.exports = [
	withEntries( defaultConfig[ 0 ], { 'admin/index': './src/admin/index.js' } ),
	withEntries( defaultConfig[ 1 ], { view: './src/view.js' } ),
];
