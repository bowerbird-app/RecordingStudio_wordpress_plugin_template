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
  end

  test "renderer resolves to host pages/embed for Getting Started" do
    page = Page.find_by!(title: "Getting Started")
    recording = RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil)
    embed = recording.current_embed

    assert_equal "pages/embed", RecordingStudioEmbeddable::Renderer.resolve(recording, embed)
  end
end
