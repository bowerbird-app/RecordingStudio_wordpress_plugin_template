# frozen_string_literal: true

require "test_helper"

class UsersSignInFlowTest < ActionDispatch::IntegrationTest
  setup do
    load Rails.root.join("db/seeds.rb").to_s
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "email and password auth screens load FlatPack primary paint on the Users sheet path" do
    users_layout = File.read(
      RecordingStudioUser::Engine.root.join("app/views/layouts/recording_studio_user/auth.html.erb")
    )
    assert_includes users_layout, 'stylesheet_link_tag "tailwind"'
    assert_includes users_layout, 'stylesheet_link_tag "flat_pack/variables"'
    assert_includes users_layout, 'stylesheet_link_tag "flat_pack/application"'
    assert_includes users_layout, 'stylesheet_link_tag "flat_pack/rich_text"'
    assert_includes users_layout, "yield :head"

    get new_user_session_path

    assert_response :success
    assert_select "button.fp-button[data-fp-style=primary]", text: "Continue with email"
    refute_includes response.body, "data-wp-plugin-demo-flatpack-assets"
    assert_flatpack_application_primary_paint response.body

    post new_user_session_path, params: { user: { email: "admin@admin.com" } }
    follow_redirect!

    assert_response :success
    assert_select "button.fp-button[data-fp-style=primary]", text: "Sign in"
    refute_includes response.body, "data-wp-plugin-demo-flatpack-assets"
    assert_flatpack_application_primary_paint response.body
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

  private

  def assert_flatpack_application_primary_paint(html)
    assert_match(%r{/assets/flat_pack/application-[a-f0-9]+\.css}, html)

    digest = html[%r{flat_pack/application-([a-f0-9]+)\.css}, 1]
    get "/assets/flat_pack/application-#{digest}.css"

    assert_response :success
    assert_includes response.body, '[data-fp-style="primary"]'
    assert_includes response.body, "--button-primary-background-color"
  end
end
