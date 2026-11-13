# frozen_string_literal: true

require "test_helper"

class SeededGettingStartedEmbedHtmlTest < ActionDispatch::IntegrationTest
  STUB_PHRASE = "This recording does not define a custom embeddable renderer yet."
  DEMO_MARKER = "WordPress Plugin Demo"

  setup do
    load Rails.root.join("db/seeds.rb").to_s
    WpPluginDemo::Seed.ensure_studio_embed!
    @page_recording_id = WpPluginDemo::Seed.getting_started_page_recording_id
    connection = WpPluginDemo::Seed.issue_runbook_connection!(host_base_url: "http://localhost:3000")
    token_result = RecordingStudioApi::Services::IssueOauthAccessToken.call(
      grant_type: WpPluginDemo::Contract::TOKEN_GRANT,
      client_id: connection.oauth_client_id,
      client_secret: connection.oauth_client_secret,
      api: WpPluginDemo::Contract::API_KEY
    )
    raise token_result.error unless token_result.success?

    @bearer = token_result.value.fetch(:access_token)
  end

  test "GET embed for seeded Getting Started returns BrowserPayload v1 with real demo HTML" do
    get WpPluginDemo::Contract.actions_embed_path(@page_recording_id),
        headers: {
          "Authorization" => "Bearer #{@bearer}",
          "Accept" => "application/json"
        }

    assert_response :ok
    payload = WpPluginDemo::Contract.parse_browser_payload!(JSON.parse(response.body))
    assert_equal 1, payload.schema_version

    html = payload.html
    refute_includes html, STUB_PHRASE
    refute_match(/#&lt;Page:/, html)
    assert_includes html, DEMO_MARKER
    assert_includes html, "Getting Started"
    assert_includes html, "Connect the WordPress Plugin Demo block to this host"
    assert_includes html, 'data-wp-plugin-demo-flatpack="1"'
    assert_includes html, 'data-wp-plugin-demo-flatpack-assets="1"'
    assert_includes html, 'data-wp-plugin-demo-flatpack-part="badge"'
    assert_includes html, 'data-wp-plugin-demo-flatpack-part="card"'
    assert_includes html, 'data-wp-plugin-demo-flatpack-part="connect"'
    assert_includes html, 'data-wp-plugin-demo-flatpack-part="page-id"'
    assert_includes html, "Page id"
    assert_includes html, "Paste this into the block."
    assert_includes html, 'data-wp-plugin-demo-flatpack-part="popup"'
    assert_includes html, "--badge-success-background-color"
    assert_includes html, "--card-background-color"
    assert_includes html, "--tabs-pill-active-background-color"
    assert_includes html, "font-family: var(--font-sans)"
    assert_includes html, ".font-medium"
    assert_includes html, ".font-bold"
    refute_includes html, "@font-face"
    assert_includes html, 'data-controller="flat-pack--modal"'
    assert_includes html, "Host is live"
    assert_includes html, "Connect the block"
    assert_includes html, "This is FlatPack"
    refute_includes html, "<link"
    refute_includes html, "<script"
  end

  test "wordpress dummy session uses the seeded WordPress client and Getting Started page" do
    session = WpPluginDemo::Seed.issue_wordpress_dummy_session!

    assert_equal "http://host.docker.internal:3000", session.host_base_url
    assert_equal WpPluginDemo::Seed::CONNECT_CLIENT_ID, session.client_id
    assert_equal @page_recording_id, session.page_recording_id
    assert_match(/\Arsoauth_at_/, session.access_token)
    assert session.refresh_token.present?
    assert_operator session.expires_at, :>, Time.now.to_i + 60

    get WpPluginDemo::Contract.actions_embed_path(session.page_recording_id),
        headers: {
          "Authorization" => "Bearer #{session.access_token}",
          "Accept" => "application/json"
        }

    assert_response :ok
    html = JSON.parse(response.body).fetch("html")
    assert_includes html, "fp-button"
    assert_includes html, "Getting Started"
    assert_includes html, "flex flex-col gap-4"
    assert_includes html, '[data-wordpress-plugin-demo-embed] [data-wp-plugin-demo-flatpack] > .mb-6:has(+ [data-wp-plugin-demo-flatpack-part="badge"])'
    refute_includes html, "[data-wordpress-plugin-demo-embed] [data-wp-plugin-demo-flatpack] .mb-6 {"
  end

  test "renderer resolves to host pages/embed for Getting Started" do
    page = Page.find_by!(title: "Getting Started")
    recording = RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil)
    embed = recording.current_embed

    assert_equal "pages/embed", RecordingStudioEmbeddable::Renderer.resolve(recording, embed)
  end
end
