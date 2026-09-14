# RecordingStudio WordPress widgets

This repo is the RecordingStudio WordPress widgets addon. The Rubygems name stays `recording_studio_wordpress_plugin_template`.

The Rails dummy host and the WordPress plugin are separate packages. The dummy app boots on its own. WordPress is a later, separate origin.

Homepage: [github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template](https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template)

## What ships

- Recording Studio 4.x gem pinned and configured
- Devise authentication with a pre-seeded admin user
- Workspace, Folder, and Page recordables seeded into the dummy host app
- FlatPack UI component library for dummy views
- Dummy app (`test/dummy/`) with a FlatPack sign-in screen, a home page on Recording Studio's default layout, mounted Recording Studio routes, and FlatPack's built-in rounded theme

Authenticated dummy pages use Recording Studio's shared default layout (`RecordingStudio::UsesDefaultLayout`) plus FlatPack CSS and JS. Devise keeps its own sign-in layout. Dummy `/docs/*` pages stay in the dummy app as a host-app sandbox. They are not the product README.

## Quick start

### Cursor Cloud Agent

A Cloud Agent boots this repo into a ready-to-use dummy environment. The setup lives in `.cursor/`:

- `install.sh` provisions Ruby (pinned by `.ruby-version`), PostgreSQL 16, all gems, the seeded dummy database, and compiled CSS at build time, then fetches Recording Studio skills.
- `start.sh` starts PostgreSQL on every boot.
- `environment.json` runs the `rails-server` and `tailwind-watch` terminals and exposes port 3000.

Open port 3000 and sign in at `/users/sign_in`. No environment variables are required. The dummy app's `database.yml` defaults match the provisioned PostgreSQL cluster.

### GitHub Codespaces

1. Click **Code**, then **Codespaces**, then **Create codespace**.
2. Wait for setup to complete.
3. Run:

   ```bash
   cd test/dummy
   bin/rails db:setup
   bin/dev
   ```

4. Open port 3000. You land on the dummy home page and can sign in at `/users/sign_in`.

The dummy app is a host-app validation surface for authentication, FlatPack rendering, Tailwind source scanning, and Recording Studio route wiring. It does not ship widget models, widget APIs, or WordPress render routes.

### Login credentials

| Field    | Value             |
|----------|-------------------|
| Email    | admin@admin.com   |
| Password | Password          |

The login form is prefilled with these credentials.

### Useful routes

- `/` is the dummy app home page
- `/users/sign_in` is the Devise sign-in page
- `/recording_studio` redirects to `/` while the mounted Recording Studio engine remains data and API focused
- `/docs/install`, `/docs/config`, `/docs/recordable_types`, `/docs/recordings_tree`, `/docs/gem_views`, `/docs/methods` are dummy-only starter pages

The home page in `test/dummy/app/views/home/index.html.erb` is a short demo of the dummy host. Keep deeper explanations on the dummy docs pages, not in this README.

## Architecture

### Root recording pattern

The dummy host follows Recording Studio's root recording pattern:

- Workspace is the top-level recordable
- Folder and Page demonstrate nested recordables under the workspace root
- Each configured recordable declares `recording_studio_recordable(...)`. Strict declaration validation stays enabled
- A root `RecordingStudio::Recording` wraps the Workspace
- `Current.actor` is set from `current_user` (Devise) in `ApplicationController`

### Extending Recording Studio

To add new recordable types:

1. Create your model (for example `Page` or `Comment`).
2. Register it in `config/initializers/recording_studio.rb`:

   ```ruby
   RecordingStudio.configure do |config|
     config.recordable_types = ["Workspace", "YourNewType"]
   end
   ```

3. Declare whether the model can be a root and which parents may contain it:

   ```ruby
   class YourNewType < ApplicationRecord
     recording_studio_recordable label: "Your new type",
                                 root: false,
                                 allowed_parent_types: ["Workspace", "Folder"]
   end
   ```

4. Validate declarations and create recordings under the root:

   ```ruby
   RecordingStudio.validate_recordable_declarations!
   root_recording = RecordingStudio.root_recording_for(workspace)
   root_recording.record(YourNewType) do |record|
     record.title = "Example"
   end
   ```

### Recordable declarations

Every configured ActiveRecord recordable type must declare its hierarchy rules. Declarations are required.

- `Workspace` declares `root: true`
- `Folder` and `Page` declare `root: false, allowed_parent_types: ["Workspace", "Folder"]`
- `config.require_recordable_declarations = true` remains enabled in the dummy app initializer

Useful console checks:

```ruby
RecordingStudio.validate_recordable_declarations!
RecordingStudio.root_recordable_types
RecordingStudio.allowed_parent_types_for("Page")
```

### Capabilities

Capability mixins are opt-in. Installing this gem does not enable mixins on host types.

The dummy Workspace enables Accessible because that addon is bundled:

```ruby
RecordingStudio.enable_capability(:accessible, on: Workspace)
```

The dummy also ships one example mixin that uses core 4.2.0's `include_for` factory:

```ruby
include RecordingStudio::Capabilities::Example.to(label: "dummy workspace")
```

`.to` wraps `RecordingStudio::Capabilities.include_for`. It does not add a fourth verb and it does not call `enable_capability` or `set_capability_options` itself. Folder and Page stay without the example mixin.

Use core `RecordingStudio::Hooks` and `RecordingStudio::Services::BaseService`. Do not copy those classes into a new addon.

### FlatPack UI components

All dummy views use FlatPack ViewComponents. Available components include:

- `FlatPack::Button::Component` for buttons (`:primary`, `:secondary`, `:ghost`)
- `FlatPack::Card::Component` for cards (`:default`, `:elevated`, `:outlined`)
- `FlatPack::Alert::Component` for alerts (`:success`, `:error`, `:warning`, `:info`)
- `FlatPack::Badge::Component` for status badges
- `FlatPack::Table::Component` for data tables
- `FlatPack::TextInput::Component`, `EmailInput`, and `PasswordInput` for form inputs
- `FlatPack::PageNav::Component` for default-layout page navigation
- `FlatPack::PageTitle::Component` for page titles

Use the live FlatPack demo app at [flatpack.bowerbird.io](https://flatpack.bowerbird.io/) as the approved UI reference for current shared patterns. Its component table is the fastest way to discover available FlatPack components before introducing new custom UI.

See the [FlatPack README](https://github.com/bowerbird-app/flatpack) for full documentation.

## Tech stack

| Component       | Version |
|-----------------|---------|
| Ruby            | 3.3+    |
| Rails           | 8.1+    |
| PostgreSQL      | 16      |
| TailwindCSS     | 4       |
| RecordingStudio | 4.x (`~> 4.2` in the gemspec; dummy GitHub tag `v4.2.0`) |
| Accessible      | dummy GitHub tag `v0.9.1` |
| Root Switchable | dummy GitHub tag `v0.5.0` |
| FlatPack        | dummy GitHub tag `v0.1.177` |
| Devise          | latest  |

The dummy Gemfile keeps `github:` sources so Bundler can fetch those gems. The gemspec still pins `recording_studio` to `~> 4.2` so the addon declares the core dependency even when GitHub is the fetch source.

## Documentation

`docs/gem_template/` stays as architectural reference for the engine conventions. Do not treat those files as the product README. This README and the dummy app are the source of truth for RecordingStudio WordPress widgets.
