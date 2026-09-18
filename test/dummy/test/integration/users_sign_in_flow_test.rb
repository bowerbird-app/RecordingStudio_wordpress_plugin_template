# frozen_string_literal: true

require "test_helper"

class UsersSignInFlowTest < ActionDispatch::IntegrationTest
  setup do
    load Rails.root.join("db/seeds.rb").to_s
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "seeded admin continues with email then signs in with password" do
    post new_user_session_path, params: { user: { email: "admin@admin.com" } }

    assert_redirected_to "#{new_user_session_path}/password"
    follow_redirect!

    assert_response :success
    assert_select "html[data-theme='rounded']"
    assert_select "input[type='password'][name='user[password]']"
    assert_select "input[type='hidden'][name='user[email]'][value='admin@admin.com']"
    assert_select "button[type='submit']", text: "Sign in"

    post user_session_path, params: { user: { email: "admin@admin.com", password: "Password" } }

    assert_redirected_to root_path
    follow_redirect!

    assert_response :success
    assert_select "body[data-dummy-host-layout='true']", count: 1
  end
end
