#!/usr/bin/env bash
# Dev-only seed for the local WordPress dummy. Safe to run on every start.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/../../recording-studio-widgets" && pwd)"
cd "$PLUGIN_DIR"

SAMPLE_PAGE_RECORDING_ID="11111111-1111-4111-8111-111111111111"
BLOCK_MARKUP="<!-- wp:recording-studio/recording-studio-widget {\"pageRecordingId\":\"${SAMPLE_PAGE_RECORDING_ID}\"} /-->"

wp_cli() {
	npx wp-env run cli wp -- "$@"
}

wp_value() {
	wp_cli "$@" | tr -d '\r' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//'
}

wp_cli plugin activate recording-studio-widgets

# wp-env creates admin / password. Reset both so the documented dev login stays true.
wp_cli user update admin \
	--user_pass=password \
	--user_email=dev-admin@example.test \
	--display_name="Dev Admin" \
	--skip-email

wp_cli user meta update admin show_welcome_panel 0
wp_cli rewrite structure '/%postname%/' --hard

POST_ID="$(wp_value post list --post_type=page --name=studio-widget --field=ID | tr -d '[:space:]')"
if [[ -z "$POST_ID" ]]; then
	POST_ID="$(wp_value post create \
		--post_type=page \
		--post_status=publish \
		--post_title="Studio widget" \
		--post_name=studio-widget \
		--post_content="$BLOCK_MARKUP" \
		--porcelain | tr -d '[:space:]')"
else
	wp_cli post update "$POST_ID" \
		--post_status=publish \
		--post_title="Studio widget" \
		--post_name=studio-widget \
		--post_content="$BLOCK_MARKUP"
fi

wp_cli option update show_on_front page
wp_cli option update page_on_front "$POST_ID"

CONTRAST_MARKUP='<!-- wp:recording-studio/recording-studio-widget {"pageRecordingId":"11111111-1111-4111-8111-111111111111"} /--><!-- wp:html --><div class="wp-block" data-rs-widget><article data-wordpress-plugin-demo-embed="page" data-theme="rounded"><a class="fp-button" data-fp-style="primary" href="#getting-started-connect" style="--fp-button-text:#fff;--fp-button-background:rgb(51, 51, 51)">Connect</a></article></div><!-- /wp:html -->'

CONTRAST_ID="$(wp_value post list --post_type=page --name=button-contrast --field=ID | tr -d '[:space:]')"
if [[ -z "$CONTRAST_ID" ]]; then
	wp_cli post create \
		--post_type=page \
		--post_status=publish \
		--post_title="Button contrast" \
		--post_name=button-contrast \
		--post_content="$CONTRAST_MARKUP" \
		--porcelain >/dev/null
else
	wp_cli post update "$CONTRAST_ID" \
		--post_status=publish \
		--post_title="Button contrast" \
		--post_name=button-contrast \
		--post_content="$CONTRAST_MARKUP"
fi

echo "WordPress dummy ready: http://localhost:8888/studio-widget/"
echo "Dev admin: admin / password (dev-admin@example.test)"
echo "Stand-in page recording id: ${SAMPLE_PAGE_RECORDING_ID}"
