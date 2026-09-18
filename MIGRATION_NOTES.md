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
- Users dummy tag `v0.11.0`
- Public RubyGems and GitHub access for dependency installation

## 0.4.15

Dummy host pins Recording Studio Oauth `v0.2.1`. Connect consent is a full-page submit (`data-turbo="false"`), so WordPress Connect can return to wp-admin without a Turbo fetch or CORS failure.

From `test/dummy`, run `bundle update recording_studio_oauth`. Restart the dummy host. Do not create new Oauth clients.

## 0.4.14

Connect start prints a short leaving-WordPress page, then sends the browser to the same allowlisted authorize URL. Settings still accept `rs_notice=connected` and always show **This site is connected.** from stored Connect tokens.

If Connect tokens are the chosen source and refresh fails, the plugin returns that Connect error. It does not POST `client_credentials` with a leftover Advanced secret.

Submit Advanced with an empty client secret to clear a stored secret.

Rebuild and reinstall the plugin ZIP. No dummy host, named API, or Oauth seed changes.

## 0.4.13

Connect to Recording Studio allowlists the hostname from the saved host base URL, then uses `wp_safe_redirect`. WordPress no longer rejects a Cloudflare tunnel or other external host and no longer sends the browser to `/wp-admin/`.

Rebuild and reinstall the plugin ZIP. Add the WordPress callback URL on the public client when the origin is not already registered. No dummy host or named API changes.

## 0.4.12

Dummy sidebar **Registered Apps** opens Oauth Admin at `/admin/screens/oauth_clients`. Use it to find the Connect public client id. Switch the root switcher to **Admin** if the screen returns 403. API Keys and Pages stay in the sidebar.

## 0.4.11

This version mounts Recording Studio Users auth chrome on the dummy host and adds WordPress Plugin Demo Connect.

Host `User` stays the Devise actor. People is the shared root. Profile is the only child. Connect is the primary WordPress path. API keys stay under Advanced.

From `test/dummy/`:

1. Pin `recording_studio_user` at GitHub tag `v0.11.0` in the dummy Gemfile. Do not add it to the root Gemfile or gemspec.
2. Run `bundle install`.
3. Run `bin/rails generate recording_studio_user:install`.
4. Run `bin/rails generate recording_studio_user:migrations`.
5. Register `"RecordingStudioUser::People"` and `"RecordingStudioUser::Profile"` in `config/initializers/recording_studio.rb`.
6. Skip Devise sessions, registrations, and passwords. Point confirmations and OmniAuth callbacks at the Users controllers. Add `recording_studio_user_auth_for :users`. Keep `:omniauthable` on host `User` so that `devise_for` mapping boots while `omniauth_providers` stays empty.
7. Enable `section :users` on `AdminRoot`.
8. Run `bin/rails db:migrate`.
9. Re-seed so `RecordingStudioUser.record_profile!` creates Avery Admin's Profile when `profile_for` is nil, and so the public **WordPress Plugin Demo** Oauth client exists with the exact wp-admin admin-post redirect URIs.

Leave `omniauth_providers = {}`. Leave OTP off. Do not add People to the root switcher. Do not bootstrap People as an owned access root.

WordPress Settings: host URL + public client id, then Connect to Recording Studio. When the site is already connected, Settings shows Disconnect and Connect again. Connect again starts PKCE and does not clear tokens first. A failed reconnect keeps the previous tokens. Sign in through Users chrome and pick a workspace. Print Connect fields with `WpPluginDemo::Seed.print_connect_client!`. Keep `print_runbook_connection!` for Advanced API keys.

Any WordPress origin other than `localhost:8888` / `127.0.0.1:8888` must be added as an exact redirect URI on that public client in Oauth Admin Registered apps.

Connect and Advanced both POST `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token`. Do not invent a second ACL. The discovery route `/recording_studio_api/oauth/token` is the public-API default and is not used for this named client.

The dummy named API lists pages at `GET /recording_studio_api/apis/wp_plugin_demo/v1/pages`. The WordPress block picker uses that index. Rebuild plugin assets after upgrade (`npm run build` in `wordpress/recording-studio-widgets`). Paste a UUID if the list is empty.

## 0.4.10

The WordPress block no longer registers a create-block stylesheet on `.wp-block-recording-studio-recording-studio-widget`. Rebuild the plugin assets and reinstall the ZIP so editor and front stop overriding host embed presentation.

## 0.4.3

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
