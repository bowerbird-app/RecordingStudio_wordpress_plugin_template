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

The ZIP does not include `src/`, `tests/`, `node_modules/`, `vendor/`, `package.json`, `.wp-env.json`, Ruby files, or repo metadata.

## Commands

Build and inspect both artifacts:

```bash
bin/check-package-boundaries
```

Build only the gem:

```bash
bin/package-gem
```

Build only the WordPress ZIP:

```bash
bin/package-wordpress-zip
```

Outputs land in `pkg/`. That directory is gitignored.

## How the check fails

`bin/package_boundaries.rb` holds the allowlists and the dummy-host fingerprints. A path that contains `test/dummy` fails. File contents that include `module Dummy`, `Dummy::Application`, or `dummy_page_nav` fail.

The gem also rejects `.php` and WordPress source trees. The ZIP also rejects `.rb`, `Gemfile`, and plugin development files.

To confirm the fail path, place a dummy host file in a ZIP and run `PackageBoundaries.inspect_zip`. The check must report a dummy host violation.

## Plugin build before you zip

Compile assets before you package:

```bash
cd wordpress/recording-studio-widgets
npm install
npm run build
```

`bin/package-wordpress-zip` copies compiled files from `build/`. It does not run webpack.

The installable ZIP includes compiled block assets under `build/recording-studio-widget/` and the baked Recording Studio plugin SDK under `build/sdk/` (copied from committed `assets/sdk/` during `npm run build`).
