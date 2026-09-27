# Site Word Counter 1.0 upgrade spec

Take the Telex export (0.1.0, first commit in this repo) to a 1.0.0 release that passes Plugin Check and WordPress.org review, following the path Scroll Indicator took.

The block stays what the original post describes: a number that looks like plain text, so people can build their own message around it with other blocks ("Blogging 33,895 words since 2022."). Source post: https://derekhanson.blog/site-wide-counter-block/

## Goals

1. The number is right, on any site, in any language.
2. It stays fast on a site with twenty years of posts.
3. The editor shows the same number as the front end.
4. The animation is optional, accessible, and never shows a wrong final number.
5. It passes Plugin Check, PHPCS (WordPress Coding Standards), and the .org guidelines with no Telex leftovers.

## Out of scope for 1.0

- Per-block post type selection (1.0 uses one site-wide filter).
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
| Contributors (readme) | WordPress Telex | Derek's WordPress.org username (ask) |
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
- **Counted types.** `apply_filters( 'site_word_counter_post_types', array( 'post', 'page' ) )`. Only public types.
- **Filtered total.** `apply_filters( 'site_word_counter_total', $total )` before it's rendered.

### Backfill

Existing sites have no meta yet.

- On activation, schedule a single WP-Cron event that counts up to 200 posts without meta, then reschedules itself until none are left.
- A WP-CLI command, `wp site-word-counter recount [--all]`, does the same thing synchronously, with a progress bar. `--all` recounts posts that already have meta (use it after changing the counting filter).
- Rendering never counts post content. While the backfill runs, the total covers what's counted so far.

### Block

- `block.json`: `apiVersion: 3`, name `site-word-counter/site-word-counter`, text domain `site-word-counter`, dynamic (`render` file), no `save`.
- Supports: `typography.textAlign`, `typography.fontSize`, `typography.lineHeight`, `__experimentalFontFamily`, `__experimentalFontWeight`, `__experimentalLetterSpacing`, `color.text`, `color.background`, `spacing.margin`, `spacing.padding`, and `align`. Remove the custom `textAlignment` attribute and the `AlignmentToolbar`.
- Attributes: `enableAnimation` (boolean, default `true`) and `format` (`"full"` or `"compact"`, default `"full"`).
- Compact format renders `33.9K` or `1.2M`. Use `number_format_i18n()` for the number, and a translatable suffix with a translator comment.
- Markup is a single wrapper element with no inner element carrying the block class:

  ```html
  <span class="wp-block-site-word-counter-site-word-counter ..." data-count="33895" data-format="full">
    <span class="screen-reader-text">33,895</span>
    <span class="wp-block-site-word-counter-site-word-counter__number" aria-hidden="true">33,895</span>
  </span>
  ```

  Use a `div` wrapper if a `span` causes layout or validation problems in a Row. The screen reader copy never animates. Escape everything, and add a phpcs ignore comment with a reason for `get_block_wrapper_attributes()`.
- Keep the old block name registered so existing content doesn't break. Register `telex/block-site-word-counter` with `inserter: false` and the same render callback. Map its `textAlignment` attribute to the new text alignment at render time, and add a `transforms.from` on the new block so an editor can convert it with one click.

### Editor

- A REST route, `GET /site-word-counter/v1/total`, returns `{ "total": int, "formatted": string, "backfill_complete": bool }`. Its permission callback requires `edit_posts`.
- `edit.js` fetches it once with `apiFetch` and renders the same markup as the front end, without animating.
- If `backfill_complete` is false, show a small notice under the number in the editor ("Still counting older posts").

### Front end

- `view.js` becomes a `viewScriptModule` with no dependencies (no Interactivity API needed for this).
- Do nothing if `enableAnimation` is off or `matchMedia( '(prefers-reduced-motion: reduce)' )` matches.
- Start the count-up when the block first scrolls into view (`IntersectionObserver`, threshold around 0.5), once per block.
- Format intermediate frames with `Intl.NumberFormat( document.documentElement.lang || undefined )`, and on the last frame set the text back to the exact server-rendered string, so the final number always matches PHP.
- Ease out over about 1.5 seconds.

### Cleanup

- `uninstall.php` deletes the meta key for all posts, the cached total, and the scheduled backfill event.
- Remove `save.js` and `artefact.xml` from the plugin (`artefact.xml` is already untracked).

### Requirements

- `Requires at least`: the lowest WordPress version that has every block support used here. Check `typography.textAlign`, and don't guess.
- `Requires PHP`: 7.4.
- `Tested up to`: the current WordPress release at build time.

## Stories

Work through these in order, one commit per story. Each story lists how to check it.

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

### 7. Front end animation and accessibility

- The view module, reduced motion, `IntersectionObserver`, and screen reader markup.

**Check:** with reduced motion emulated, the number doesn't animate. With the `lang` attribute set to `de-DE` and a filtered total of 33895, the animation ends on `33.895`, matching PHP. The screen reader text is always the final number.

### 8. Uninstall and cleanup

- `uninstall.php`. Remove `save.js`.

**Check:** on a throwaway copy, delete the plugin, then confirm no `_site_word_counter_words` meta, cached total, or cron event remains.

### 9. Docs, readme, and release checks

- Rewrite `readme.txt` (honest description, real FAQ, filters, WP-CLI, changelog, `Stable tag: 1.0.0`).
- Add a GitHub-style `README.md` like Scroll Indicator's.
- Add a GitHub Actions workflow running lint, PHPCS, and `WordPress/plugin-check-action`.
- Bump everything to 1.0.0.

**Check:** Plugin Check (`studio wp plugin check site-word-counter` with the Plugin Check plugin installed on the test site) reports no errors. `npx pressship pack .` makes `site-word-counter.zip` with `site-word-counter/` as its top-level folder and no dev files.

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

- **Total: 34.** A draft post (ID 9), the Sample Page (ID 2, set to draft), and the Privacy Policy draft must not count.
- Never use or change `~/Studio/derekhansonblog`. It's Derek's live blog's local copy.
