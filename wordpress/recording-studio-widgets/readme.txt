=== WordPress Plugin Demo ===
Contributors:      bowerbird
Tags:              block, widgets, recording studio
Tested up to:      6.8
Stable tag:        0.2.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Embeds a Recording Studio page from the wp_plugin_demo host API using a server-rendered BrowserPayload and the baked plugin SDK.

== Description ==

This plugin registers one dynamic block named **WordPress Plugin Demo**. Authors set a **page recording id** (UUID). The server obtains OAuth client credentials, fetches embed JSON from the host named API, and prints a safe BrowserPayload v1 payload in the page. The front-end SDK mounts from that payload. Secrets and bearer tokens never reach the browser.

The Rails dummy host is a separate app on another origin. This plugin does not ship Ruby or dummy host code.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/recording-studio-widgets`.
1. Activate **WordPress Plugin Demo** on the Plugins screen.
1. Under **Settings → WordPress Plugin Demo**, set the host base URL (for example `http://localhost:3000`), OAuth client id, and client secret.
1. Insert the **WordPress Plugin Demo** block and enter the page recording UUID to embed.

== Connecting to the dummy host ==

1. Start the Rails dummy host on port 3000 (see the repository README).
1. From `test/dummy`, print Settings fields and the Getting Started page recording id:

`bin/rails runner 'WpPluginDemo::Seed.print_connection_for_runbook!'`

1. Copy host base URL, client id, client secret into **Settings → WordPress Plugin Demo**, and the page recording id into the block.
1. Full cold-start steps live in the repo at `docs/wordpress-plugin-demo-runbook.md`.

Token URL defaults to `{host}/recording_studio_api/apis/wp_plugin_demo/oauth/token`. Override only when your host uses a different token endpoint.

== Frequently Asked Questions ==

= Where does WordPress run locally? =

`wp-env` defaults to port 8888. The Rails dummy host uses port 3000.

= Does the block call the host from the visitor's browser? =

No. PHP fetches embed JSON on the server. Visitors only receive the validated BrowserPayload JSON and the SDK script.

== Changelog ==

= 0.2.0 =
* Connect to the wp_plugin_demo named API with OAuth client credentials.
* Server-render BrowserPayload v1 and mount the baked Recording Studio plugin SDK on the front.
* Editor preview via authenticated REST (`edit_posts`).

= 0.1.0 =
* Scaffold the plugin with `@wordpress/create-block` 4.98.0, dynamic variant.
* Placeholder block and settings page.
