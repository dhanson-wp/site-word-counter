/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

/**
 * Tally marks: four strokes and a slash. The block, legacy block, and
 * settings screen share it, and it matches the plugin icon.
 */
const tallyIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path
			d="M5.5 5v14M10 5v14M14.5 5v14M19 5v14M3 16.5l18-9"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
	</SVG>
);

export default tallyIcon;
