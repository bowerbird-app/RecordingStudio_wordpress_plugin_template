# Migration Notes

## Tailwind / FlatPack CSS in the dummy

The dummy scans FlatPack components and Recording Studio views through machine-local symlinks under `test/dummy/vendor/`:

```bash
cd test/dummy
bin/rails recording_studio_root_switchable:link_tailwind_sources
bin/rails tailwindcss:build
```

`app/assets/tailwind/application.css` prefers `vendor/flat_pack` and `vendor/recording_studio`, with fallbacks for `vendor/bundle` and system gem paths. Without the link step, Tailwind builds a near-empty stylesheet and FlatPack screens look unstyled.

---

## Current Requirements

- Ruby 3.3 or newer
- Rails 8.1 or newer
- Recording Studio 4.x (`~> 4.2` in the gemspec; dummy GitHub tag `v4.2.0`)
- Accessible dummy tag `v0.9.1` and Root Switchable dummy tag `v0.5.0`
- FlatPack dummy tag `v0.1.177`
- Public RubyGems and GitHub access for dependency installation

## Verification

Install both bundles and run the complete gem and dummy app test path:

```bash
bundle install
BUNDLE_GEMFILE=test/dummy/Gemfile bundle install
bundle exec rake test:all
```

Run the dummy app from its directory for browser verification:

```bash
cd test/dummy
bin/dev
```

Use the [FlatPack repository](https://github.com/bowerbird-app/flatpack) and the live FlatPack demo linked from the top-level README for current component documentation.
