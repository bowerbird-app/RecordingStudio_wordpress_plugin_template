# frozen_string_literal: true

require "test_helper"

class WpPluginDemoSeedTest < ActiveSupport::TestCase
  setup do
    load Rails.root.join("db/seeds.rb").to_s
  end

  test "getting_started_page_recording_id points at seeded Getting Started page" do
    WpPluginDemo::Seed.ensure_studio_embed!
    page_recording_id = WpPluginDemo::Seed.getting_started_page_recording_id

    page = Page.find_by!(title: "Getting Started")
    expected = RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil).id
    assert_equal expected, page_recording_id
  end

  test "issue_runbook_connection! returns Getting Started page and named-API credentials" do
    connection = WpPluginDemo::Seed.issue_runbook_connection!(host_base_url: "http://localhost:3000")

    assert_equal "http://localhost:3000", connection.host_base_url
    assert_equal WpPluginDemo::Seed.getting_started_page_recording_id, connection.page_recording_id
    assert_match(/\A\S+\z/, connection.oauth_client_id)
    assert_match(/\A\S+\z/, connection.oauth_client_secret)
    assert_equal WpPluginDemo::Contract::TOKEN_PATH, connection.token_path
    assert_equal WpPluginDemo::Contract.actions_embed_path(connection.page_recording_id), connection.embed_path

    result = RecordingStudioApi::Services::IssueOauthAccessToken.call(
      grant_type: WpPluginDemo::Contract::TOKEN_GRANT,
      client_id: connection.oauth_client_id,
      client_secret: connection.oauth_client_secret,
      api: WpPluginDemo::Contract::API_KEY
    )
    assert result.success?, result.error&.message
    refute_nil result.value.fetch(:access_token)
  end

  test "present_runbook_connection shows id without secret until a secret is passed in" do
    issued = WpPluginDemo::Seed.issue_runbook_connection!(host_base_url: "http://localhost:3000")

    presented = WpPluginDemo::Seed.present_runbook_connection(host_base_url: "http://www.example.com")
    assert_equal "http://www.example.com", presented.host_base_url
    assert_equal issued.oauth_client_id, presented.oauth_client_id
    assert_nil presented.oauth_client_secret
    assert_equal issued.page_recording_id, presented.page_recording_id

    with_secret = WpPluginDemo::Seed.present_runbook_connection(
      host_base_url: "http://www.example.com",
      oauth_client_secret: "one-shot-secret"
    )
    assert_equal "one-shot-secret", with_secret.oauth_client_secret
  end

  test "ensure_connect_client! writes the public WordPress relay app" do
    client = WpPluginDemo::Seed.ensure_connect_client!

    assert_equal "WordPress", client.name
    refute client.confidential?
    assert_equal "wp_plugin_demo", client.api_key
    assert_equal WpPluginDemo::Seed::CONNECT_CLIENT_ID, client.client_id
    assert_equal [
      "http://localhost:3000/recording_studio_oauth/callback",
      "http://127.0.0.1:3000/recording_studio_oauth/callback"
    ], client.redirect_uris
    refute_includes client.redirect_uris, WpPluginDemo::Seed.example_return_to
    assert_nil client.client_secret_digest
    assert_equal true, client.use_central_relay
    assert_equal [
      "https://*/wp-admin/admin-post.php?action=recording_studio_oauth_callback",
      "http://*/wp-admin/admin-post.php?action=recording_studio_oauth_callback"
    ], client.allowed_return_patterns
    assert_equal [], client.exact_return_urls
    assert client.allows_return_to?("http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback")
    refute client.allows_return_to?("http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start")
  end

  test "print_connect_client! prints public relay fields and no secret" do
    WpPluginDemo::Seed.ensure_connect_client!

    output = capture_io do
      connection = WpPluginDemo::Seed.print_connect_client!(host_base_url: "http://localhost:3000")
      assert_equal "http://localhost:3000", connection.host_base_url
      assert_equal WpPluginDemo::Seed::CONNECT_CLIENT_ID, connection.connect_client_id
      assert_equal connection.connect_client_id, RecordingStudioOauth::OauthClient.find_by!(name: "WordPress").client_id
      assert_equal "http://localhost:3000/recording_studio_oauth/connect", connection.connect_url
      assert_equal "http://localhost:3000#{WpPluginDemo::Contract::CONNECT_TOKEN_PATH}", connection.token_url
      assert_equal "http://localhost:3000/recording_studio_oauth/callback", connection.relay_redirect_uri
      assert_equal WpPluginDemo::Seed.example_return_to, connection.return_to
      assert_equal WpPluginDemo::Seed.getting_started_page_recording_id, connection.page_recording_id
    end.first

    assert_includes output, "connect_client_id=#{WpPluginDemo::Seed::CONNECT_CLIENT_ID}"
    assert_includes output, "connect_url=http://localhost:3000/recording_studio_oauth/connect"
    assert_includes output, "token_url=http://localhost:3000/recording_studio_api/apis/wp_plugin_demo/oauth/token"
    assert_includes output, "relay_redirect_uri=http://localhost:3000/recording_studio_oauth/callback"
    refute_includes output, "authorize_url="
    refute_includes output, "client_secret"
    refute_includes output, "oauth_client_secret"
  end

  test "embed_body_html_for returns the generic page fallback" do
    other = Page.create!(title: "Other Demo Page")

    generic = WpPluginDemo::Seed.embed_body_html_for(other)
    assert_includes generic, "WordPress Plugin Demo"
    assert_includes generic, "seeded Getting Started page"
    refute_includes generic, "does not define a custom embeddable renderer"
  end
end
