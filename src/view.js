/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

// Add number formatting animation on load
document.addEventListener('DOMContentLoaded', function() {
	const wordCounters = document.querySelectorAll('.wp-block-telex-site-word-counter');
	
	wordCounters.forEach(function(counterBlock) {
		// Check if animation is enabled
		const animationEnabled = !counterBlock.hasAttribute('data-no-animation');
		
		if (!animationEnabled) {
			return;
		}
		
		const numberElement = counterBlock.querySelector('.word-counter-number');
		if (!numberElement) return;
		
		const finalNumber = parseInt(numberElement.textContent.replace(/,/g, ''));
		if (!isNaN(finalNumber) && finalNumber > 0) {
			animateCounter(numberElement, 0, finalNumber, 2000);
		}
	});
});

function animateCounter(element, start, end, duration) {
	let startTimestamp = null;
	const step = (timestamp) => {
		if (!startTimestamp) startTimestamp = timestamp;
		const progress = Math.min((timestamp - startTimestamp) / duration, 1);
		const current = Math.floor(progress * (end - start) + start);
		element.textContent = new Intl.NumberFormat().format(current);
		if (progress < 1) {
			window.requestAnimationFrame(step);
		}
	};
	window.requestAnimationFrame(step);
}