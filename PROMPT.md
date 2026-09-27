# Claude Code prompt: Site Word Counter 1.0

Paste this into a Claude Code session opened in `~/Projects/site-word-counter`.

---

You're taking Site Word Counter from its Telex export (0.1.0) to a 1.0.0 release that's ready for WordPress.org.

Before you write code:

1. Read `CLAUDE.md` and `docs/upgrade-spec.md` in full.
2. Load the `wordpress-plugin-development` and `wordpress-blocks` skills, and check any block.json, block supports, or script module claim against them rather than memory.
3. Confirm the test site is running (`studio site status` from `~/Studio/site-word-counter`) and that the "Counter test" page at http://localhost:8918/counter-test/ renders a number.

Then work through the spec's nine stories in order:

- One story at a time, one commit per story on `trunk`, with a message that names the story.
- After each story, run its **Check** and show me the output. If a check fails, fix it before moving on. Don't mark a story done on a check you didn't run.
- Run `npm run build` before any check that touches the front end or editor, since the test site loads `compiled/`.
- Use the in-app browser to check the editor and front end for stories 5, 6, and 7, including reduced motion.

Stop and ask me before you:

- Change anything in the spec's Identity table, or its counting rules.
- Add a dependency beyond `@wordpress/scripts`, WPCS, and the Plugin Check action.
- Add a feature from the spec's "Out of scope for 1.0" list.
- Push, tag, create a release, or submit anything.

Never touch `~/Studio/derekhansonblog`.

When all nine stories pass, give me a short summary: what changed, the Plugin Check result, the zip's path, and anything in the spec that turned out wrong (and update the spec to match).
