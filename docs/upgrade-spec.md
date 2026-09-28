# Site Word Counter 1.0 upgrade spec

Take the Telex export (0.1.0, first commit in this repo) to a 1.0.0 release that passes Plugin Check and WordPress.org review, following the path Scroll Indicator took.

The block stays what the original post describes: a number that looks like plain text, so people can build their own message around it with other blocks ("Blogging 33,895 words since 2022."). Source post: https://derekhanson.blog/site-wide-counter-block/

## Goals

1. The number is right, on any site, in any language.
2. It stays fast on a site with twenty years of posts.
3. The editor shows the same number as the front end.
4. The animation is optional, accessible, and never shows a wrong final number.
5. Site owners choose what counts, see the counting status, and recount from a modern settings screen under Settings, with no command line needed.
6. It passes Plugin Check, PHPCS (WordPress Coding Standards), and the .org guidelines with no Telex leftovers.

## Out of scope for 1.0

- Per-block post type selection. Post types are chosen once for the site on the settings page, because there's one cached total.
- Site-wide defaults for block settings. Each setting lives in one place: what counts is on the settings page, and how the number looks is on the block.
- A "since" year pulled from the first published post.
- Counting words in comments, titles, excerpts, or custom fields.
- Multisite network totals.
- Brand assets (banner, icon, screenshots). Derek makes these.

## Identity

| Thing | Telex 0.1.0 | 1.0.0 |
|---|---|---|
| Plugin name | Site Word Counter | Site Word Counter |
| Slug and folder | `site-word-counter` | `site-word-counter` |
| Main file | `site-word-counter.php` | `site-word-counter.php` |
| Text domain | `site-word-counter-block-wp` | `site-word-counter` |
| Block name | `telex/block-site-word-counter` | `site-word-counter/site-word-counter` |
| PHP prefix | none | `site_word_counter_` (functions), `SITE_WORD_COUNTER_` (constants) |
| Author | WordPress Telex | Derek Hanson |
| Contributors (readme) | WordPress Telex | `dhansondesigns` |
| Build output | `build/` | `compiled/` |
| Version | 0.1.0 | 1.0.0 |

These follow `dhanson-wp/scroll-indicator`: block name `<slug>/<slug>`, compiled assets in `compiled/` because PressShip leaves out `build/`, and `npx pressship pack .` for the zip.

## Known problems in 0.1.0

Each of these must be fixed by the stories below.

1. Global functions `get_site_total_word_count()` and `clear_site_word_count_cache()` have no prefix.
2. `str_word_count()` only counts ASCII letters. Accented words split apart, and Japanese, Chinese, Korean, and Thai text counts as nearly zero.
3. Shortcodes are counted as words.
4. A cache miss loads every published post and page and counts them all in one request.
5. The cache clears on every `save_post`, including revisions, autosaves, and menu items.
6. `number_format()` isn't localized, and `view.js` strips commas and reformats with the browser's locale, so sites that use `.` or spaces as separators animate to the wrong number.
7. The editor preview counts only the first 100 posts, skips pages, and uses a different method, so it doesn't match the front end.
8. An inner `div.wp-block-telex-site-word-counter` duplicates the wrapper, and the alignment selectors in `style.scss` never match it.
9. A custom `textAlignment` attribute and `AlignmentToolbar` stand in for a block support.
10. `typography.fontFamily` and `typography.fontWeight` aren't stable support keys (they need the `__experimental` prefix), so those controls probably never appear. Confirm in the editor.
11. The count-up ignores `prefers-reduced-motion`, and screen readers can hear a partial number mid-animation.
12. The animation starts on `DOMContentLoaded`, so a footer counter finishes before anyone sees it.
13. `Requires at least: 6.0` is below what `apiVersion: 3` needs.
14. `readme.txt` is out of date ("Tested up to: 6.8") and overpromises ("powerful", "real-time", display format options that don't exist).
15. No uninstall cleanup. `save.js` is an empty file, and `artefact.xml` is Telex packaging.

## Design

### Counting

One pure function, `site_word_counter_count_text( string $content ): int`, does all counting.

1. Remove shortcodes with `strip_shortcodes()`.
2. Remove tags and block comments with `wp_strip_all_tags()`, then decode entities with `html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' )`.
3. Count each character in a script written without spaces (Han, Hiragana, Katakana, Thai, and similar) as one word, using Unicode script classes such as `\p{Han}`, `\p{Hiragana}`, `\p{Katakana}`, and `\p{Thai}`.
4. Remove those characters, then count runs of letters and numbers, keeping apostrophes and hyphens inside a word together: roughly `/[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/u`.
5. Return the filtered result: `apply_filters( 'site_word_counter_count_text', $count, $content )`.

Titles aren't counted, only post content. That matches what "words published" means for a blog and what 0.1.0 does.

### Storage

Store a per-post count and sum it, rather than recounting the whole site.

- **Post meta.** `_site_word_counter_words` (underscore, so it's hidden from the custom fields UI), registered with `register_post_meta()` as an integer, not shown in REST.
- **Write on save.** Hook `wp_after_insert_post`. Skip revisions, autosaves, and post types that aren't counted. Store the count for any status, since a draft that gets published later already has its number.
- **Total.** One query sums the meta for published posts of the counted types, prepared with `$wpdb->prepare()`. Cache the result in an option or transient with no expiry (`site_word_counter_total`) and delete it on `transition_post_status`, on `deleted_post`, and whenever the meta changes for a counted post.
- **Available types.** Any post type that's public or publicly queryable, minus attachments, so custom post types like movie reviews qualify (`site_word_counter_available_post_types` filters the list). New types are offered in Settings, not counted automatically.
- **Post text.** `apply_filters( 'site_word_counter_post_content', $post->post_content, $post )` runs before counting, so a post type can add text kept in custom fields.
- **Counted types.** Read from the `site_word_counter_post_types` option (see Settings page), default `array( 'post', 'page' )`, then passed through `apply_filters( 'site_word_counter_post_types', $types )`. Only public types. Changing the option clears the cached total. Posts of a newly added type without meta are picked up by the backfill.
- **Filtered total.** `apply_filters( 'site_word_counter_total', $total )` before it's rendered.

### Backfill

Existing sites have no meta yet.

- On activation, schedule a single WP-Cron event that counts up to 200 posts without meta, then reschedules itself until none are left.
- A WP-CLI command, `wp site-word-counter recount [--all]`, does the same thing synchronously, with a progress bar. `--all` recounts posts that already have meta (use it after changing the counting filter).
- Rendering never counts post content. While the backfill runs, the total covers what's counted so far.

### Block

- `block.json`: `apiVersion: 3`, name `site-word-counter/site-word-counter`, text domain `site-word-counter`, dynamic (`render` file), no `save`.
- Supports: `typography.textAlign`, `typography.fontSize`, `typography.lineHeight`, `__experimentalFontFamily`, `__experimentalFontWeight`, `__experimentalFontStyle`, `__experimentalLetterSpacing`, `color.text`, `color.background`, `spacing.margin`, and `spacing.padding`. Remove the custom `textAlignment` attribute and the `AlignmentToolbar`. (Built: block `align` was dropped, since a number doesn't need wide or full widths and `textAlign` covers alignment. In 7.1, text color shows under Typography in the Styles tab.)
- Attributes: `enableAnimation` (boolean, default `true`) and `format` (`"full"` or `"compact"`, default `"full"`).
- Compact format renders `33.9K` or `1.2M`. Use `number_format_i18n()` for the number, and a translatable suffix with a translator comment.
- Markup is a single wrapper element with no inner element carrying the block class:

  ```html
  <span class="wp-block-site-word-counter-site-word-counter ..." data-count="33895" data-format="full">
    <span class="screen-reader-text">33,895</span>
    <span class="wp-block-site-word-counter-site-word-counter__number" aria-hidden="true">33,895</span>
  </span>
  ```

  (Built: the wrapper is a `div`, because `textAlign` does nothing on an inline `span`. When animation is off, only one plain number span is rendered.) The screen reader copy never animates. Escape everything, and add a phpcs ignore comment with a reason for `get_block_wrapper_attributes()`.
- Keep the old block name registered so existing content doesn't break. Register `telex/block-site-word-counter` with `inserter: false` and the same render callback. Map its `textAlignment` attribute to the new text alignment at render time, and add a `transforms.from` on the new block so an editor can convert it with one click.

### Settings page

A React screen at **Settings > Word Counter**, not a top-level menu item.

- **Menu.** `add_options_page()` with the `manage_options` capability, slug `site-word-counter`. Add a "Settings" link on the Plugins screen row with `plugin_action_links_{basename}`.
- **Options.** Registered with `register_setting()` in the `site_word_counter` group, each with a `sanitize_callback`, a default, and `show_in_rest` with a schema, so the screen saves through `/wp/v2/settings`:
  - `site_word_counter_post_types`: array of public post type slugs, default `[ "post", "page" ]`. Sanitize against `get_post_types( array( 'public' => true ) )`, and never save an empty list (fall back to the default).
  - `site_word_counter_disable_animation`: boolean, default `false`. When true, no counter on the site animates, whatever each block says.
- **Screen.** Three layers of the WordPress Design System, each doing one job:
  - `@wordpress/admin-ui` for the page frame (`Page` and its header), so it looks like the other new admin screens.
  - ~~`@wordpress/dataviews` (`DataForm`, card layout) for the form~~. (Built: `DataForm` was dropped. Bundled through `@wordpress/dataviews/wp`, it brings its own copy of the component library, date-fns, and framer-motion, and the screen's script was 394 KB gzipped for two settings. Without it, the screen is 36 KB gzipped. The two sections are `@wordpress/ui` `Card`s holding `CheckboxControl`s and a `ToggleControl`, loaded and saved through `@wordpress/core-data`'s site entity.)
  - `@wordpress/ui` for anything else (`Stack` for layout, and its buttons and notices where they exist), wrapped in `ThemeProvider` seeded with `getAdminThemeColors()` from `@wordpress/admin-ui`, so the screen follows the user's admin color scheme.
  - A sticky Save button, and a success or error notice after saving.
- **Bundling.** `@wordpress/ui` and `@wordpress/admin-ui` aren't `window.wp` globals, so they're bundled. `@wordpress/theme` is WordPress's `wp-theme` script, and from 7.1 (the plugin's minimum) it has a public `ThemeProvider` and a `wp-theme` design tokens stylesheet, so it stays external. (7.0's `wp-theme` only exposes `privateApis` and has no stylesheet, which crashed the screen in testing. Before the minimum moved to 7.1, `webpack.config.js` bundled it.) They so they're bundled into this screen's script. `@wordpress/ui` is marked experimental, so pin exact versions in `package.json` (no `^`) and upgrade on purpose. Because they're bundled, a WordPress update can't break the screen. Enqueue the script and styles only on this screen, and keep the bundle size reasonable.
- **Styles.** Make the screen's stylesheet depend on `wp-components` and `wp-theme` (the design tokens). Add `isolation: isolate` to the screen's root element.
- **Sections:**
  1. **What counts.** A checkbox list of public post types, with help text saying titles, drafts, and private posts never count.
  2. **Display.** The "Turn off counter animations across the site" toggle, with help text pointing to reduced-motion accessibility.
  3. **Status.** Read only: the current total (formatted), posts counted of posts to count, whether the backfill is running, and when the last full recount ran. A **Recount now** button runs the recount in batches through REST with a progress bar, and the screen stays usable while it runs.
- **Component choices.** `@wordpress/eslint-plugin`'s `use-recommended-components` rule is turned on in `eslint.config.cjs`, and it decides: `Card`, `Stack`, `Text`, `Badge`, and `Skeleton` come from `@wordpress/ui`; `Button`, `CheckboxControl`, `ToggleControl`, `ProgressBar`, and `SnackbarList` stay on `@wordpress/components`, because their `@wordpress/ui` versions aren't recommended yet. `ToggleGroupControl` is still experimental, so the block's format picker is a `SelectControl`. `ThemeProvider` takes only the admin scheme's primary color; its background is the admin menu's and turns the screen dark.
- **Design system.** Follow the current package docs (https://developer.wordpress.org/block-editor/reference-guides/packages/packages-ui/ and packages-admin-ui/) and Gutenberg's "use recommended components" guidance for which component to use where, since some `@wordpress/components` components are still the recommended choice. Use design tokens, never custom colors or spacing. Note in this spec any component that had to fall back to `@wordpress/components`, and why.
- **Accessibility.** Every control has a visible label. Progress is announced with `speak()` from `@wordpress/a11y`. It works with the keyboard alone and at 200% zoom.

### Editor

- A REST route, `GET /site-word-counter/v1/total`, returns `{ "total": int, "formatted": { "full": string, "compact": string }, "backfill_complete": bool }`, so switching the format in the editor doesn't need another request. Its permission callback requires `edit_posts`.
- The settings page adds `GET /site-word-counter/v1/status` (counted posts, posts to count, backfill running, last recount time) and `POST /site-word-counter/v1/recount` (processes one batch and returns progress, `offset` in and out). Both require `manage_options`.
- `edit.js` fetches it once with `apiFetch` and renders the same markup as the front end, without animating.
- If `backfill_complete` is false, show a small notice under the number in the editor ("Still counting older posts").

### Front end

- `view.js` becomes a script module with no dependencies (no Interactivity API needed for this). (Built: it's its own module entry in `webpack.config.js`, registered as `site-word-counter/view` and enqueued from the render function only when a counter animates, because a `block.json` `viewScriptModule` always loads.)
- Do nothing if `enableAnimation` is off, the site-wide `site_word_counter_disable_animation` option is on, or `matchMedia( '(prefers-reduced-motion: reduce)' )` matches. When animation is off for either setting, `render.php` doesn't enqueue the view module at all.
- In the editor, when the site-wide switch is on, the block's animation toggle is disabled with help text saying animations are turned off in Settings > Word Counter, and linking there.
- Start the count-up when the block first scrolls into view (`IntersectionObserver`, threshold around 0.5), once per block.
- Format intermediate frames with `Intl.NumberFormat( document.documentElement.lang || undefined )`, and on the last frame set the text back to the exact server-rendered string, so the final number always matches PHP.
- Ease out over about 1.5 seconds.

### Cleanup

- `uninstall.php` deletes the meta key for all posts, the cached total, both options, the last recount time, and the scheduled backfill event.
- Remove `save.js` and `artefact.xml` from the plugin (`artefact.xml` is already untracked).

### Requirements

- `Requires at least`: **7.1**. (Raised from 6.9 on 2026-09-27 at Derek's call. 7.1 is the current release and Ipsum's own minimum, and it's the first version whose `wp-theme` has what the settings screen needs; 7.0 was tried and crashed.)
- `Requires PHP`: 7.4.
- `Tested up to`: **7.1**, the current release (7.1.2 as of 2026-09-27; confirm it's still current at build time).
- WordPress 7.1 makes 40px the default height for form controls and ignores `__next40pxDefaultSize` at runtime. With 7.1 as the minimum, `__next40pxDefaultSize` is dropped everywhere. Don't use `View`'s `css` prop, the removed `Navigation` component, or `__experimentalApplyValueToSides`.
- The 7.1 changes are in the [WordPress 7.1 Field Guide](https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/). Check it, and the [Design System theming dev note](https://make.wordpress.org/core/2026/07/31/design-system-theming-in-wordpress-7-1/), before stories 5 and 7.

## Stories

Work through these stories in order, one commit per story. Each story lists how to check it.

### 1. Tooling and baseline

- Add `@wordpress/scripts` as a dev dependency, and scripts matching Scroll Indicator (`build` to `compiled/` with `--webpack-copy-php`, `start`, `lint:js`, `lint:css`, `format`, `plugin-zip` with PressShip).
- Add Composer with `wp-coding-standards/wpcs` and a `phpcs.xml.dist` for the prefix and text domain.
- Add `.distignore` and `.pressshipignore` so `docs/`, `CLAUDE.md`, `PROMPT.md`, `node_modules/`, `src/`, `.github/`, and dev config don't ship.

**Check:** `npm run build` writes `compiled/` and the block still renders on the test site. `npm run lint:js` and `composer phpcs` run (failures are expected until later stories).

### 2. Identity

- Rename per the identity table. Prefix every function, hook, option, and meta key. Point the main file at `compiled/`.

**Check:** `grep -ri telex` finds only the legacy block registration. `grep -r "function get_site\|function clear_site"` finds nothing.

### 3. Counting and storage

- `site_word_counter_count_text()`, the meta, the save hook, the summed total, cache invalidation, and the filters.

**Check** on the test site (see Test site below): `studio wp eval 'echo site_word_counter_count_text( get_post( 7 )->post_content );'` prints `9`, and the total prints `34`. Publishing, unpublishing, trashing, and deleting a post changes the total right away.

### 4. Backfill and WP-CLI

- The cron backfill and `wp site-word-counter recount`.

**Check:** delete all `_site_word_counter_words` meta, run `studio wp site-word-counter recount`, and the total is `34` again. Deactivate, delete meta, reactivate, run cron (`studio wp cron event run --due-now`), and the total is `34`.

### 5. Block markup, supports, and legacy name

- New `block.json`, render markup, supports, and the `format` attribute. The legacy `telex/` registration and transform.

**Check:** the "Counter test" page, which still uses `telex/block-site-word-counter`, renders `34` with no errors. A new block inserted in the editor renders `34`. Text alignment, font family, font weight, and colors all appear in the editor and apply on the front end. Compact format renders correctly for a filtered total of 33895 (`33.9K`).

### 6. Editor preview

- The REST route and `edit.js`.

**Check:** the editor shows `34`, the same as the front end. Logged out, the route returns 401.

### 7. Settings page

- The options, REST routes for status and recount, the React screen, and the Plugins screen link.

**Check:** Settings > Word Counter appears under Settings, and not as its own menu item. An Editor role user can't see it. Unticking Pages and saving drops the total from `34` to `30` on the front end and in the editor, and ticking it again brings back `34`. Saving an empty post type list keeps posts and pages. Recount now finishes with a progress bar and updates the status. With the animation switch on, no counter animates and the block's toggle is disabled with a link to Settings. The screen passes a keyboard-only run and has no console errors.

### 8. Front end animation and accessibility

- The view module, reduced motion, `IntersectionObserver`, and screen reader markup.

**Check:** with reduced motion emulated, the number doesn't animate. With the `lang` attribute set to `de-DE` and a filtered total of 33895, the animation ends on `33.895`, matching PHP. The screen reader text is always the final number.

### 9. Uninstall and cleanup

- `uninstall.php`. Remove `save.js`.

**Check:** on a throwaway copy, delete the plugin, then confirm no `_site_word_counter_words` meta, options, cached total, or cron event remains.

### 10. Docs, readme, and release checks

- Rewrite `readme.txt` (honest description, the settings page, real FAQ, filters, WP-CLI, changelog, `Stable tag: 1.0.0`). Add a screenshot slot for the settings screen.
- Add a GitHub-style `README.md` like Scroll Indicator's.
- Add a GitHub Actions workflow running lint, PHPCS, and `WordPress/plugin-check-action`.
- Bump everything to 1.0.0.

**Check:** Plugin Check (`studio wp plugin check site-word-counter` with the Plugin Check plugin installed on the test site) reports no errors. The plugin works on WordPress 7.1 (switch the test site's version with `studio site set --wp`, or use a Playground). `npx pressship pack .` makes `site-word-counter.zip` with `site-word-counter/` as its top-level folder and no dev files.

### 11. Placement and patterns

Added after the first ten stories, on the `feature/placement` branch. The settings screen follows Jetpack Newsletter's newer settings screen: visual tiles that show where something lands.

- **Option.** `site_word_counter_placements`, an array of `footer` and `after_posts`, default empty, registered with `show_in_rest` and an enum schema. Uninstall deletes it.
- **What gets added.** A Row (`core/group`, flex layout) holding the counter and a Paragraph: "33,895 words published since 2019." The year is the year of the earliest published post of the counted types, cached with the total and cleared with it. The same markup is the "Words published since" pattern, so there's one source.
- **How it's added.** Block Hooks. `hooked_block_types` hooks `site-word-counter/site-word-counter` as `last_child` of the footer's outermost group (context is a `wp_template_part` with area `footer`, or the pattern the active footer part is built from), and `after` `core/post-content` in the `single-post` or `single` template. `hooked_block_site-word-counter/site-word-counter` swaps the bare block for the Row. WordPress tracks the original block name in `ignoredHookedBlocks`, so a line someone deletes in the Site Editor stays deleted.
- **No duplicates.** Skip insertion when the template or part already contains a Site Word Counter block (new or legacy name).
- **Classic themes.** No template parts, so the card explains that and points to the patterns. Nothing is inserted.
- **Settings card, "Show it on your site".** Under a small uppercase label, two tiles side by side: a wireframe of a page with the line in its footer, and a wireframe of a post with the line after its content. Each tile is a label wrapping a checkbox in its corner, the wireframe (`aria-hidden`), and a caption, so the whole tile toggles it. Under each caption, a **Preview and edit** link opens the footer template part or single template in the Site Editor (`site-editor.php?postType=…&postId={theme}//{slug}&canvas=edit`), as Jetpack does. Help text says that once the line is edited and saved in the Site Editor, it's part of the template, so turning the tile off won't remove it.
- **Header.** The `admin-ui` Page gets a `visual` icon (the paragraph icon, matching the block) and the description "Count your published words and show them off across your site."
- **Patterns.** A "Site Word Counter" pattern category with "Words published since" (the Row) and "Word count stat" (a large counter over a small "words published" label, for About pages).

(Built: in the footer, the Row goes inside the footer's outermost group as its last child, so it takes the footer's layout, padding, and block gap and sits right under the footer's last line. Block Hooks can't tell that group from the groups inside it, so `hooked_block_types` accepts the last child of any group in the footer and `hooked_block_site-word-counter/site-word-counter` returns null for all but the outermost one, found by comparing the anchor with the first block of the part or pattern. Returning null also keeps `ignoredHookedBlocks` off the inner groups. A footer that isn't a single group (or a single pattern that is one) falls back to `last_child` of the template part, wrapped in a full-width constrained group. Either way the Row copies the footer's own width: `wide` when the footer's top-level group has wide content (Twenty Twenty-Five), the content column otherwise (Ipsum). The counter and the words copy the text style of the footer's last Paragraph or Site Tagline, merged over what its parent groups set: `fontSize`, `textColor`, `fontFamily`, text properties of `style.typography`, `style.color.text`, and text alignment, which also justifies the Row. With no text block, they use the `small` font size. The Row takes the font size too, so its `0.3em` gap scales with the text. Themes like Ipsum build their single template from a pattern, so a `core/post-content` anchor inside a pattern the single template uses also counts as "below posts". Tested on Ipsum, the upcoming default theme, Twenty Twenty-Five, and Tufte Blocks. **Preview and edit** for the footer finds the footer part the index template actually includes, because Twenty Twenty-Five has three footer-area parts. When a line was deleted in the Site Editor, the tile says so, based on `ignoredHookedBlocks` on a block in the saved template (the footer group) or the template's `_wp_ignored_hooked_blocks` meta (the template part fallback), when the saved template has no counter. The tiles are native checkboxes inside labels, since `@wordpress/ui`'s checkbox isn't recommended yet.)

**Check:** on Twenty Twenty-Five, turning on Footer adds the line with 34 and the first post's year to every page's footer, and turning it off removes it. Below posts adds it after single posts only, not pages. The line shows in the Site Editor's footer; deleting it there and saving keeps it gone. A footer that already has a counter gets no second one. On a classic theme, the card shows the explanation and nothing is inserted. Both patterns are registered. The tiles work with the keyboard alone. Checks pass on 7.1 with Ipsum and Twenty Twenty-Five.

## Test site

- Studio site: `~/Studio/site-word-counter`, http://localhost:8918. Run WP-CLI from that folder as `studio wp …`.
- `wp-content/plugins/site-word-counter` is a symlink to this repo.
- Published fixtures, and what correct counting gives:

| ID | Type | Title | Content | Words |
|---|---|---|---|---|
| 5 | post | Plain English | "This post has exactly ten words for the counting tests." | 10 |
| 6 | post | Accents | "Café naïve résumé déjà vu, piñata façade." | 7 |
| 7 | post | Japanese | "今日は良い天気です。" | 9 |
| 8 | post | Shortcode | "Four words and shortcode." plus a `[gallery]` shortcode | 4 |
| 10 | page | Counter test | "Blogging", the counter block, "words since 2022." | 4 |

- Custom post types from the test helper mu-plugin: `movie_review` (public; ID 19, "Five stars, would watch again.", 5 words) and `swc_recipe` (not public but publicly queryable; ID 20, "Mix flour and water.", 4 words). Neither counts by default. Ticking both gives 43.
- **Total: 34.** A draft post (ID 9), the Sample Page (ID 2, set to draft), and the Privacy Policy draft must not count.
- Never use or change `~/Studio/derekhansonblog`. It's Derek's live blog's local copy.
