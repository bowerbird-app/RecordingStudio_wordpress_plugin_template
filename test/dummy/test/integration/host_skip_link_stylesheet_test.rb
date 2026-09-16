# frozen_string_literal: true

require "test_helper"
require "devise/test/integration_helpers"

class HostSkipLinkStylesheetTest < ActionDispatch::IntegrationTest
  include Devise::Test::IntegrationHelpers

  TEST_PASSWORD = "SkipLinkStylesheetPassword!2026"

  setup do
    load Rails.root.join("db/seeds.rb").to_s

    @user = User.find_or_create_by!(email: "skip-link-stylesheet-test@example.com") do |user|
      user.password = TEST_PASSWORD
      user.password_confirmation = TEST_PASSWORD
    end

    sign_in @user
  end

  test "host layout links FlatPack application CSS that hides the skip link until focus" do
    get root_path
    assert_response :success

    assert_select "body[data-dummy-host-layout='true']", count: 1
    assert_select "a.fp-skip-link[href='#main']", text: "Skip to content"

    assert_match(%r{/assets/flat_pack/application-[a-f0-9]+\.css}, response.body)

    digest = response.body[%r{flat_pack/application-([a-f0-9]+)\.css}, 1]
    get "/assets/flat_pack/application-#{digest}.css"
    assert_response :success
    assert_includes response.body, ".fp-skip-link"
    assert_includes response.body, "translateY(-150%)"
    assert_includes response.body, ".fp-skip-link:focus-visible"
  end
end
