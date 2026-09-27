/**
 * Counts the number up when a Site Word Counter block scrolls into view.
 *
 * The server renders the final number, so without JavaScript (or with reduced
 * motion) visitors see it right away. The animated copy is aria-hidden; screen
 * readers read the separate, static copy.
 */

const BLOCK_SELECTOR =
	'.wp-block-site-word-counter-site-word-counter[data-animate="true"]';
const NUMBER_SELECTOR = '.wp-block-site-word-counter-site-word-counter__number';
const DURATION = 1500;

const easeOutCubic = ( progress ) => 1 - Math.pow( 1 - progress, 3 );

function getFormatter( format ) {
	const locale = document.documentElement.lang || undefined;
	const options =
		format === 'compact'
			? { notation: 'compact', maximumFractionDigits: 1 }
			: {};

	try {
		return new Intl.NumberFormat( locale, options );
	} catch {
		return new Intl.NumberFormat( undefined, options );
	}
}

function prepare( block ) {
	const number = block.querySelector( NUMBER_SELECTOR );
	const target = Number( block.dataset.count );

	if ( ! number || ! Number.isFinite( target ) || target <= 0 ) {
		return null;
	}

	const formatter = getFormatter( block.dataset.format );
	const finalText = number.textContent;
	number.textContent = formatter.format( 0 );

	return () => {
		let start;

		const step = ( now ) => {
			start ??= now;
			const progress = Math.min( ( now - start ) / DURATION, 1 );

			if ( progress < 1 ) {
				number.textContent = formatter.format(
					Math.floor( easeOutCubic( progress ) * target )
				);
				window.requestAnimationFrame( step );
			} else {
				// End on the server's exact string, so it always matches PHP.
				number.textContent = finalText;
			}
		};

		window.requestAnimationFrame( step );
	};
}

function init() {
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	const blocks = document.querySelectorAll( BLOCK_SELECTOR );
	if ( ! blocks.length ) {
		return;
	}

	const starts = new Map();
	blocks.forEach( ( block ) => {
		const start = prepare( block );
		if ( start ) {
			starts.set( block, start );
		}
	} );

	if ( ! ( 'IntersectionObserver' in window ) ) {
		starts.forEach( ( start ) => start() );
		return;
	}

	const observer = new window.IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					observer.unobserve( entry.target );
					starts.get( entry.target )?.();
				}
			} );
		},
		{ threshold: 0.5 }
	);

	starts.forEach( ( start, block ) => observer.observe( block ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
