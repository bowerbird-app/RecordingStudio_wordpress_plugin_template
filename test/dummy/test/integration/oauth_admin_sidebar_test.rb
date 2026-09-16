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

  test "signed-in sidebar links to Registered apps and not plugin credentials" do
    get root_path

    assert_response :success
    assert_select "body[data-dummy-host-layout='true']", count: 1
    assert_includes response.body, "Registered apps"
    assert_includes response.body, REGISTERED_APPS_PATH
    refute_includes response.body, "Plugin credentials"
    refute_includes response.body, "/plugin_credentials"
  end

  test "registered apps admin screen loads for seeded admin" do
    get REGISTERED_APPS_PATH

    assert_response :success
    assert_includes response.body, "Registered apps"
    assert_includes response.body, "New app"

    get "#{REGISTERED_APPS_PATH}/table",
        params: { anchor_url: "http://www.example.com#{REGISTERED_APPS_PATH}" }

    assert_response :success
    assert_includes response.body, "Seed Demo App"
  end
end
