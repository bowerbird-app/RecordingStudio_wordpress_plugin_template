# Connect blocker

WordPress Plugin Demo Connect cannot use the locked discovery token URL on the gems this dummy pins.

## What we measured

Host-only probe on `recording_studio_oauth` `v0.2.0` and `recording_studio_api` `v0.5.5` (2026-09-18):

| OauthClient `api_key` | Token POST | Result | Named embed with that bearer |
| --- | --- | --- | --- |
| `wp_plugin_demo` | `/recording_studio_api/oauth/token` | `401 invalid_client` | never reached |
| `wp_plugin_demo` | `/recording_studio_api/apis/wp_plugin_demo/oauth/token` | `200` `rsoauth_at_…` | `200` schema v1 |
| `public` | `/recording_studio_api/oauth/token` | `200` `rsoauth_at_…` | `401` authentication_failed |
| `public` | `/recording_studio_api/apis/wp_plugin_demo/oauth/token` | `401 invalid_client` | never reached |

`CreateOauthAuthorization` plus Accessible on Studio Workspace is not the failure. The grant succeeds. Token issue and named-API authentication both key off `api_key`.

## Why host+plugin cannot close this

Locked contract:

- Seed the public client as `api_key: wp_plugin_demo`
- Connect token + refresh at `{host}/recording_studio_api/oauth/token`
- Embed at `{host}/recording_studio_api/apis/wp_plugin_demo/v1/pages/{id}/actions/embed`

API mounts the discovery token route as `defaults: { api_key: "public" }` (`RecordingStudio_api` `config/routes.rb`). Oauth `AuthenticateOauthClient` then requires `client.api_key == "public"`. API `TokenAuthenticationBase` requires `credential.api_client.api_key ==` the named API (`wp_plugin_demo`) before a bearer is accepted on embed.

Those two checks cannot both pass for one OauthClient. Fixing either check is an Oauth or API gem change. This host will not invent a second token URL, a route shim, or a parallel ACL.

## What this branch still does

- Seeds the public `WordPress Plugin Demo` client with the exact admin-post redirect URIs
- Prints Connect fields (discovery `token_url`, no secret)
- Dummy Users chrome on signed-out authorize stays
- Dummy embed coverage posts `authorization_code` to the named API token path, which is the only combination that returns `rsoauth_at_` and a `200` embed on these gems
- WordPress Connect talks to the locked discovery token path. That exchange will `401` until Oauth/API change

## Gem fix that unblocks the locked contract

One of:

- Discovery `POST /recording_studio_api/oauth/token` authenticates the OauthClient by `client_id` and uses that client's `api_key` for issue + later bearer checks
- Named API bearer auth accepts `rsoauth_at_` tokens whose OauthClient `api_key` is `public` when Accessible already authorizes the grant

Do not paper this over in the WordPress plugin or dummy routes.
