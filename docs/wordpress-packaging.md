# WordPress packaging

The Rails engine gem and the WordPress plugin ZIP are separate artifacts. Dummy host code must not appear in either one.

## What each artifact contains

The gem is the Rails engine only. Allowed paths:

- `app/`
- `config/`
- `db/`
- `lib/`
- `MIT-LICENSE`
- `Rakefile`
- `README.md`

The ZIP is the installable plugin folder `recording-studio-widgets/`. Allowed paths:

- `recording-studio-widget.php`
- `readme.txt`
- `includes/`
- `build/`

The ZIP must include the baked SDK under `build/sdk/` (`recording-studio-plugin-sdk.js` and `.css`). Those files are copied from `assets/sdk/` by `npm run build`.

The ZIP does not include `src/`, `assets/`, `tests/`, `node_modules/`, `vendor/`, `package.json`, `.wp-env.json`, Ruby files, or repo metadata.

## Commands

Build an installable ZIP in one step (runs `npm ci`, `npm run build`, asserts SDK, writes the archive):

```bash
bin/build-plugin-zip
```

Alias:

```bash
bin/package-wordpress-zip
```

Pass `--skip-compile` when `wordpress/recording-studio-widgets/build/` (including `build/sdk/`) is already built.

Build and inspect both artifacts:

```bash
bin/check-package-boundaries
```

`bin/check-package-boundaries` compiles plugin assets by default. Pass `--skip-compile` after a prior `npm ci && npm run build` (CI does this). Pass `--zip-only` or `--gem-only` to limit which artifact is built.

Build only the gem:

```bash
bin/package-gem
```

Outputs land in `pkg/`. That directory is gitignored.

Staff ZIP shipping and WordPress Settings steps: [wordpress-plugin-demo-runbook.md](wordpress-plugin-demo-runbook.md).

## How the check fails

`bin/package_boundaries.rb` holds the allowlists, SDK requirements, and the dummy-host fingerprints. A path that contains `test/dummy` fails. File contents that include `module Dummy`, `Dummy::Application`, or `dummy_page_nav` fail. A ZIP without `build/sdk/recording-studio-plugin-sdk.js` (and the CSS sibling) fails before the archive is written.

The gem also rejects `.php` and WordPress source trees. The ZIP also rejects `.rb`, `Gemfile`, and plugin development files.

To confirm the fail path, place a dummy host file in a ZIP and run `PackageBoundaries.inspect_zip`. The check must report a dummy host violation.
