<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$enable_animation = $attributes['enableAnimation'] ?? true;
$text_alignment = $attributes['textAlignment'] ?? 'left';

// Get the total word count
$word_count = site_word_counter_get_total();

// Format the number
$formatted_count = number_format($word_count);

// Set up classes and attributes
$wrapper_attributes = get_block_wrapper_attributes([
	'class' => "has-text-align-{$text_alignment}",
	'style' => "text-align: {$text_alignment};"
]);

// Add data attribute to disable animation if needed
$animation_attr = $enable_animation ? '' : ' data-no-animation="true"';
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="wp-block-site-word-counter-site-word-counter"<?php echo $animation_attr; ?>>
		<span class="word-counter-number">
			<?php echo esc_html($formatted_count); ?>
		</span>
	</div>
</div>