# Site Word Counter

A WordPress block plugin that shows the total number of words published across a site. It started as a WordPress Telex export (first commit) and is being taken to a 1.0.0 WordPress.org release.

- The plan is `docs/upgrade-spec.md`. Follow it, and update it when a decision changes.
- Default branch is `trunk`. Remote is `dhanson-wp/site-word-counter`.
- Source lives in `src/`, and the build writes to `compiled/` (not `build/`, which PressShip leaves out).
- Prefix PHP with `site_word_counter_`. Text domain is `site-word-counter`. Block name is `site-word-counter/site-word-counter`.
- Follow WordPress Coding Standards (PHPCS) and `@wordpress/scripts` lint rules.
- Target WordPress 7.1 (the current release), with 6.9 as the minimum. Check current docs through the `wp-devdocs` MCP, not memory.
- Accessibility first: respect `prefers-reduced-motion`, and keep the real number available to screen readers.

## Testing

- Studio test site: `~/Studio/site-word-counter`, http://localhost:8918. Run WP-CLI from that folder with `studio wp …`.
- The site's `wp-content/plugins/site-word-counter` is a symlink to this repo, so a build shows up right away.
- The fixtures and expected counts (total 34) are in the Test site section of the spec.
- Never use or change `~/Studio/derekhansonblog`. It's the live blog's local copy.

## Releases

- Derek ships with PressShip. Build the zip with `npx pressship pack .`, with `site-word-counter/` as the top-level folder, and use PressShip for the WordPress.org submission.
- Fix a bad release by bumping the version. Never move a tag.
- Don't push, tag, or submit to WordPress.org unless Derek asks.
