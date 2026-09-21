# frozen_string_literal: true

require "test_helper"
require "devise/test/integration_helpers"

class OauthConnectConsentTurboTest < ActionDispatch::IntegrationTest
  include Devise::Test::IntegrationHelpers
  include OauthConnectHelpers

  setup do
    load Rails.root.join("db/seeds.rb").to_s
    @user = User.find_by!(email: "admin@admin.com")
    @oauth_client = WpPluginDemo::Seed.ensure_connect_client!
    @access_recording = studio_workspace_access_recording!(@user)
    @pkce = pkce_pair
    sign_in @user
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "connect consent form is a full page submit" do
    get "/recording_studio_oauth/apis/#{WpPluginDemo::Contract::API_KEY}/oauth/authorize", params: {
      response_type: "code",
      client_id: @oauth_client.client_id,
      redirect_uri: RecordingStudioOauth.wordpress_relay_callback_url(base_url: "http://localhost:3000"),
      state: "connect-turbo",
      code_challenge: @pkce.fetch(:challenge),
      code_challenge_method: "S256",
      access_recording_id: @access_recording.id
    }

    assert_response :success
    assert_select "form[method=post][data-turbo=false]"
    assert_select "form[data-turbo=false] button[name='decision'][value='connect']"
  end
end
