=== Site Word Counter ===
Contributors:      dhansondesigns
Tags:              word count, block, statistics, writing, blogging
Requires at least: 7.1
Tested up to:      7.1
Requires PHP:      7.4
Stable tag:        1.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Show the total number of words you've published across your whole site, as a block you can style and place anywhere.

== Description ==

If you've been writing online for years, you've published a lot of words. Site Word Counter shows that number on your site.

The block is deliberately simple: by itself, it's just a number. Put it in a Row with a couple of Paragraph blocks and it becomes a line like "Blogging 33,895 words since 2022." It works well as a footer credit, on an About page, or anywhere you want to show how long you've been at it.

**What it does**

* Counts the words in every published post and page, or the post types you choose.
* Stores each post's count when you save it, so the total stays fast on sites with thousands of posts.
* Counts accented words correctly, and counts Chinese, Japanese, and Thai text by character.
* Leaves shortcodes, HTML, and titles out of the count.
* Shows the full number (33,895) or a compact one (33.9K), formatted for your site's language.
* Counts up when it scrolls into view. Visitors who prefer reduced motion see the final number right away, and screen readers always read the final number.
* Uses the block editor's own typography, color, and spacing controls.

**Show it on your site**

On block themes, **Settings › Word Counter** can add a line like "33,895 words published since 2019." to your footer, below each post, or both, with the year taken from your first post. Each spot has a **Preview and edit** link that opens it in the Site Editor, where you can reword, restyle, or remove it. Two patterns, "Words published since" and "Word count stat", are in the block inserter for anywhere else.

**In a sentence**

Need the number mid-sentence, like "The day I shared it, it said 23,004. Today it says 37,051."? In any paragraph, heading, list item, quote, or image caption, choose **Word count** from the block toolbar's **More** menu. The number stays current on your site and looks like the rest of your text.

**Settings**

Go to **Settings › Word Counter** to choose which post types count, choose where the word count shows up, turn off counter animations across the site, check the counting status, and recount every post.

**For developers**

* `site_word_counter_post_types` filters the counted post types.
* `site_word_counter_available_post_types` filters the post types offered in Settings.
* `site_word_counter_post_content` filters the text counted for a post, so a custom post type can count words kept in custom fields.
* `site_word_counter_count_text` filters the count for a piece of content, so you can swap in your own counting rules.
* `site_word_counter_total` filters the total before it's shown.
* `wp site-word-counter recount [--all]` counts posts from the command line, and `wp site-word-counter total` prints the total.

Site Word Counter started as a [WordPress Telex](https://telex.automattic.ai) experiment.

**Source code**

The plugin ships compiled JavaScript and CSS. The human-readable source, build tools, and issue tracker are on [GitHub](https://github.com/dhanson-wp/site-word-counter), in `src/` and `includes/`.

== Installation ==

1. Install Site Word Counter from **Plugins › Add New**, or upload the ZIP from **Plugins › Add New › Upload Plugin**.
2. Activate it. Existing posts are counted in the background right away.
3. Add the **Site Word Counter** block to a post, page, or template part.

== Frequently Asked Questions ==

= What counts as a word? =

Runs of letters and numbers in any language, with apostrophes and hyphens inside a word kept together, so "isn't" and "state-of-the-art" are one word each. In Chinese, Japanese, Thai, and other languages written without spaces, each character counts as one. Titles, shortcodes, HTML, and block markup don't count.

= Which content counts? =

Published posts and pages by default. Drafts, private posts, and scheduled posts don't count until they're published. You can add or remove post types in **Settings › Word Counter**.

= Does it work with custom post types? =

Yes. Any post type visitors can see, such as movie reviews, recipes, or products, shows up in **Settings › Word Counter**. Tick it, save, and its existing posts are counted in the background. If a post type keeps its text in custom fields instead of the editor, a developer can add those fields with the `site_word_counter_post_content` filter.

= I just installed it and the number looks low. =

Older posts are counted in the background after activation, a few hundred at a time. The settings page shows the progress, and **Recount now** finishes it right away.

= Will it slow down my site? =

No. Each post is counted once when it's saved, and the total is a single cached query. The counting animation is a small script that only loads on pages with an animated counter.

= How do I add it to my footer? =

On a block theme, go to **Settings › Word Counter**, tick **Word count in your footer**, and save. Use **Preview and edit** to change the wording in the Site Editor. On a classic theme, add the Site Word Counter block or the "Words published since" pattern anywhere blocks are allowed, such as a widget area.

= I deleted the line in the Site Editor. How do I get it back? =

WordPress remembers that you removed it and won't add it again. Open the footer or template in the Site Editor and insert the "Words published since" pattern.

= Can I put the word count inside a sentence? =

Yes. Click where the number should go, open the **More** menu (the chevron) in the block toolbar, and choose **Word count**. It works in paragraphs, headings, list items, quotes, and image captions. Visitors always see the current total. The post keeps the total from when you inserted it, so the sentence still makes sense if you ever turn the plugin off.

= Can I turn off the animation? =

Yes. Each block has its own setting, and **Settings › Word Counter** can turn off every counter's animation at once. Visitors who ask their device for reduced motion never see it either way.

= I used the version from the Telex blog post. Will my counter break? =

No. Counters made with that version keep working. In the editor they show your number like before, with a **Convert** button in the block toolbar that turns them into the current block.

== Screenshots ==

1. The word count added below a post: "33,895 words published since 2019."
2. The "Word count stat" pattern on an About page, with the word count in the footer.
3. Block settings: number format and animation, plus the editor's own typography and color controls.
4. Settings › Word Counter: choose what counts, and add the word count to your footer or below each post.

== Changelog ==

= 1.1.0 =
* Put your live word count inside a sentence. In any paragraph, heading, list item, quote, or caption, choose Word count from the toolbar's More menu, and the number stays current and looks like the rest of the text.
* Inline counts get a faint dotted underline in the editor, so you can tell them apart from numbers you typed. Visitors don't see it.

= 1.0.2 =
* The footer line now matches your theme. It sits inside the footer, right under its last line, and uses the same text size, color, and font as the rest of the footer.

= 1.0.1 =
* The README now links straight to the latest release, so you can download the plugin without digging through GitHub.

= 1.0.0 =
* Rebuilt from the Telex prototype for WordPress.org.
* Counts each post when it's saved and totals them with one query, with a background backfill and WP-CLI commands.
* Works with any public custom post type.
* Adds the word count to your footer or below each post on block themes, with Preview and edit links to the Site Editor.
* "Words published since" and "Word count stat" block patterns.
* Unicode-aware counting that leaves out shortcodes and markup.
* New compact number format, localized numbers, and an editor preview that matches the front end.
* Accessible count-up animation that respects reduced motion and starts when the counter scrolls into view.
* New Settings › Word Counter screen.
* Uses the block editor's typography, color, and spacing controls.
* Removes everything it stored when you delete it.

= 0.1.0 =
* The original Telex prototype.
