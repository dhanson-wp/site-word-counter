/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { 
	useBlockProps,
	InspectorControls,
	BlockControls,
	AlignmentToolbar
} from '@wordpress/block-editor';

import {
	PanelBody,
	ToggleControl
} from '@wordpress/components';

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const { enableAnimation, textAlignment } = attributes;
	const [wordCount, setWordCount] = useState(null);
	const [isLoading, setIsLoading] = useState(true);

	const blockProps = useBlockProps({
		className: `has-text-align-${textAlignment}`,
		style: {
			textAlign: textAlignment
		}
	});

	// Fetch word count for preview
	useEffect(() => {
		const fetchWordCount = async () => {
			try {
				const response = await apiFetch({
					path: '/wp/v2/posts',
					method: 'GET',
					data: {
						status: 'publish',
						per_page: 100,
						context: 'edit'
					}
				});

				// This is a simplified calculation for the editor preview
				// The actual calculation happens server-side
				let totalWords = 0;
				response.forEach(post => {
					if (post.content && post.content.rendered) {
						const textContent = post.content.rendered.replace(/<[^>]*>/g, '');
						const words = textContent.trim().split(/\s+/);
						totalWords += words.length;
					}
				});

				setWordCount(totalWords);
				setIsLoading(false);
			} catch (error) {
				console.error('Error fetching word count:', error);
				setWordCount(0);
				setIsLoading(false);
			}
		};

		fetchWordCount();
	}, []);

	const formatNumber = (num) => {
		if (num === null) return '...';
		return new Intl.NumberFormat().format(num);
	};

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={textAlignment}
					onChange={(nextAlign) => {
						setAttributes({ textAlignment: nextAlign });
					}}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={__('Word Counter Settings', 'site-word-counter-block-wp')}>
					<ToggleControl
						label={__('Enable number animation', 'site-word-counter-block-wp')}
						checked={enableAnimation}
						onChange={(value) => setAttributes({ enableAnimation: value })}
						help={__('Animate the number counting up when the page loads', 'site-word-counter-block-wp')}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="wp-block-telex-site-word-counter">
					<span className="word-counter-number">
						{isLoading ? (
							<span className="word-counter-loading">
								{__('Calculating...', 'site-word-counter-block-wp')}
							</span>
						) : (
							formatNumber(wordCount)
						)}
					</span>
				</div>
			</div>
		</>
	);
}