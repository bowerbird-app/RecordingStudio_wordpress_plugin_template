# frozen_string_literal: true

require "test_helper"

class CentralRelayConnectTest < ActionDispatch::IntegrationTest
  include OauthConnectHelpers

  setup do
    load Rails.root.join("db/seeds.rb").to_s
    @client = WpPluginDemo::Seed.ensure_connect_client!
    @pkce = pkce_pair
    host! "localhost:3000"
  end

  test "wordpress relay paths are not routed" do
    get "/recording_studio_oauth/wordpress/connect", params: start_params
    assert_response :not_found

    get "/recording_studio_oauth/wordpress/callback"
    assert_response :not_found
  end

  test "connect start sends authorize at the central callback" do
    get "/recording_studio_oauth/connect", params: start_params

    assert_response :redirect
    location = response.redirect_url
    query = Rack::Utils.parse_query(URI.parse(location).query)

    assert_includes location, "/recording_studio_oauth/apis/wp_plugin_demo/oauth/authorize"
    assert_equal "http://localhost:3000/recording_studio_oauth/callback", query.fetch("redirect_uri")
    assert_equal @client.client_id, query.fetch("client_id")
    assert_equal "S256", query.fetch("code_challenge_method")
    refute_includes query.fetch("redirect_uri"), "/wordpress/"
    refute_includes query.fetch("redirect_uri"), "admin-post.php"
  end

  private

  def start_params
    {
      client_id: @client.client_id,
      return_to: "http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback",
      state: "site-csrf",
      code_challenge: @pkce.fetch(:challenge),
      code_challenge_method: "S256"
    }
  end
end
