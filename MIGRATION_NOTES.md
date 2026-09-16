# Migration Notes

## Current Requirements

- Ruby 3.3 or newer
- Rails 8.1 or newer
- Recording Studio 4.x (`~> 4.2` in the gemspec; dummy GitHub tag `v4.2.0`)
- Accessible dummy tag `v0.9.1` and Root Switchable dummy tag `v0.5.0`
- FlatPack dummy tag `v0.1.177`
- Public RubyGems and GitHub access for dependency installation

## 0.4.2

Host `Page` now declares `renderer: "pages/embed"`. The template renders WordPress Plugin Demo HTML from `WpPluginDemo::Seed.embed_body_html_for`. Without that template, Embeddable returns its fallback stub and a raw `#<Page:…>` dump in the WP block.

After upgrade:

1. Restart the dummy host.
2. Confirm GET `:embed` for Getting Started includes "WordPress Plugin Demo" and omits the stub phrase.
3. After `db:reset`, re-print `page_recording_id` with `WpPluginDemo::Seed.getting_started_page_recording_id`.

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
