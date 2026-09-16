# frozen_string_literal: true

require "test_helper"
require "devise/test/integration_helpers"

class OauthAdminSidebarTest < ActionDispatch::IntegrationTest
  include Devise::Test::IntegrationHelpers
  include OauthAdminHelpers

  setup do
    load Rails.root.join("db/seeds.rb").to_s

    @user = User.find_by!(email: "admin@admin.com")
    sign_in @user
    switch_to_admin_root!
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "signed-in sidebar links API Keys and Pages and hides Registered apps" do
    api_clients_path = recording_studio_api.api_clients_path

    get root_path

    assert_response :success
    assert_select "body[data-dummy-host-layout='true']", count: 1
    assert_includes response.body, "API Keys"
    assert_includes response.body, api_clients_path
    assert_select "a[href=?]", api_clients_path
    assert_includes response.body, "Pages"
    assert_select "a[href=?]", pages_path
    refute_includes response.body, "Registered apps"
    refute_includes response.body, "Plugin credentials"
    refute_includes response.body, "/plugin_credentials"
  end

  test "registered apps admin screen loads for seeded admin" do
    get REGISTERED_APPS_PATH

    assert_response :success
    assert_includes response.body, "Registered apps"
    assert_includes response.body, "New app"
    assert_select "turbo-frame#screen-table[src=?]", "#{REGISTERED_APPS_PATH}/table"

    get "#{REGISTERED_APPS_PATH}/table",
        params: { anchor_url: "http://www.example.com#{REGISTERED_APPS_PATH}" }

    assert_response :success
    assert_includes response.body, "Seed Demo App"
  end
end
