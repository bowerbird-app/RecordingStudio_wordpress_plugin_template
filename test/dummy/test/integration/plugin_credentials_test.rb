# frozen_string_literal: true

require "test_helper"
require "devise/test/integration_helpers"

class PluginCredentialsTest < ActionDispatch::IntegrationTest
  include Devise::Test::IntegrationHelpers

  TEST_PASSWORD = "PluginCredsPassword!2026"

  setup do
    load Rails.root.join("db/seeds.rb").to_s

    @user = User.find_or_create_by!(email: "plugin-creds-test@example.com") do |user|
      user.password = TEST_PASSWORD
      user.password_confirmation = TEST_PASSWORD
    end

    sign_in @user
  end

  test "show renders host url page id and sidebar without a secret before mint" do
    get plugin_credentials_path

    assert_response :success
    assert_select "body[data-dummy-host-layout='true']", count: 1
    assert_includes response.body, "Plugin credentials"
    assert_includes response.body, "Home"
    assert_includes response.body, "Recordings tree"
    assert_includes response.body, request.base_url
    assert_includes response.body, WpPluginDemo::Seed.getting_started_page_recording_id
    refute_includes response.body, 'name="oauth_client_secret"'
  end

  test "create mints credentials and reveals the secret once" do
    post plugin_credentials_path
    assert_redirected_to plugin_credentials_path

    follow_redirect!
    assert_response :success
    assert_includes response.body, 'name="oauth_client_id"'
    assert_includes response.body, 'name="oauth_client_secret"'
    assert_includes response.body, "only time the client secret is shown"

    get plugin_credentials_path
    assert_response :success
    assert_includes response.body, 'name="oauth_client_id"'
    refute_includes response.body, 'name="oauth_client_secret"'
    assert_includes response.body, "Client secret is hidden"
  end

  test "visitor path redirects to sign in and does not expose credentials" do
    sign_out @user

    get plugin_credentials_path
    assert_redirected_to new_user_session_path
  end
end
