/**
 * ESLint config: the @wordpress/scripts default, plus the WordPress script
 * packages this plugin uses as externals (provided by WordPress at runtime).
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		ignores: [ 'compiled/**' ],
	},
	{
		rules: {
			// Follow the Design System's current component recommendations.
			'@wordpress/use-recommended-components': 'error',
		},
		settings: {
			'import/core-modules': [
				'@wordpress/a11y',
				'@wordpress/api-fetch',
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/components',
				'@wordpress/core-data',
				'@wordpress/data',
				'@wordpress/date',
				'@wordpress/dom-ready',
				'@wordpress/element',
				'@wordpress/i18n',
				'@wordpress/notices',
				'@wordpress/url',
			],
		},
	},
];
