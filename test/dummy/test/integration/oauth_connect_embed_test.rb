# frozen_string_literal: true

require "test_helper"

class OauthConnectEmbedTest < ActionDispatch::IntegrationTest
  include OauthConnectHelpers

  setup do
    load Rails.root.join("db/seeds.rb").to_s
    WpPluginDemo::Seed.ensure_studio_embed!
    @user = User.find_by!(email: "admin@admin.com")
    @oauth_client = WpPluginDemo::Seed.ensure_connect_client!
    @access_recording = studio_workspace_access_recording!(@user)
    @page_recording_id = WpPluginDemo::Seed.getting_started_page_recording_id
    @redirect_uri = WpPluginDemo::Seed::CONNECT_REDIRECT_URIS.fetch(0)
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "authorization_code tokens embed on wp_plugin_demo" do
    pkce = pkce_pair
    approved = approve_delegated_oauth(
      oauth_client: @oauth_client,
      user: @user,
      access_recording: @access_recording,
      redirect_uri: @redirect_uri,
      pkce: pkce
    )

    post WpPluginDemo::Contract::TOKEN_PATH, params: {
      grant_type: "authorization_code",
      client_id: @oauth_client.client_id,
      code: approved.fetch(:code),
      redirect_uri: @redirect_uri,
      code_verifier: pkce.fetch(:verifier)
    }

    assert_response :ok
    issued = JSON.parse(response.body)
    access_token = issued.fetch("access_token")
    assert_match(/\Arsoauth_at_/, access_token)
    assert issued.fetch("refresh_token").present?
    assert issued.fetch("expires_in").present?
    assert_equal "Bearer", issued.fetch("token_type")

    get WpPluginDemo::Contract.actions_embed_path(@page_recording_id),
        headers: {
          "Authorization" => "Bearer #{access_token}",
          "Accept" => "application/json"
        }

    assert_response :ok
    payload = WpPluginDemo::Contract.parse_browser_payload!(JSON.parse(response.body))
    assert_equal 1, payload.schema_version
  end
end
