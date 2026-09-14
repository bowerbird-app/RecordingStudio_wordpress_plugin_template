=== RecordingStudio Widget ===
Contributors:      bowerbird
Tags:              block, widgets
Tested up to:      6.8
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Placeholder RecordingStudio Widget dynamic block. Install and activate it. It does not load live widgets yet.

== Description ==

This plugin registers one dynamic block named RecordingStudio Widget. The editor and the published page show the same safe placeholder text. The block is server-rendered. It does not discover widgets, open an iframe, or talk to Recording Studio.

The Rails dummy host is a separate app. This plugin does not ship Ruby, dummy host code, or Recording Studio APIs.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/recording-studio-widgets`.
1. Activate RecordingStudio Widget on the Plugins screen.
1. Insert the RecordingStudio Widget block into a page.

== Frequently Asked Questions ==

= Does this plugin load a live Recording Studio widget? =

No. This phase ships a placeholder only.

= Where does WordPress run locally? =

`wp-env` defaults to port 8888. The Rails dummy host uses port 3000.

== Changelog ==

= 0.1.0 =
* Scaffold the plugin with `@wordpress/create-block` 4.98.0, dynamic variant.
* Register the RecordingStudio Widget placeholder block.
* Add a Settings page placeholder.
