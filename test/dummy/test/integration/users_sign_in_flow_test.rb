# frozen_string_literal: true

require "test_helper"

class UsersSignInFlowTest < ActionDispatch::IntegrationTest
  setup do
    load Rails.root.join("db/seeds.rb").to_s
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "email and password auth screens load charcoal primary paint on the Users sheet path" do
    users_layout = File.read(
      RecordingStudioUser::Engine.root.join("app/views/layouts/recording_studio_user/auth.html.erb")
    )
    assert_includes users_layout, 'stylesheet_link_tag "tailwind"'
    assert_includes users_layout, 'stylesheet_link_tag "flat_pack/variables"'
    assert_includes users_layout, 'stylesheet_link_tag "flat_pack/rich_text"'
    assert_includes users_layout, "yield :head"
    refute_includes users_layout, 'stylesheet_link_tag "flat_pack/application"'

    get new_user_session_path

    assert_response :success
    assert_select "button.fp-button[data-fp-style=primary]", text: "Continue with email"
    refute_includes response.body, "data-wp-plugin-demo-flatpack-assets"
    assert_primary_paint_sheet response.body

    post new_user_session_path, params: { user: { email: "admin@admin.com" } }
    follow_redirect!

    assert_response :success
    assert_select "button.fp-button[data-fp-style=primary]", text: "Sign in"
    refute_includes response.body, "data-wp-plugin-demo-flatpack-assets"
    assert_primary_paint_sheet response.body
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

  def assert_primary_paint_sheet(html)
    assert_match(%r{/assets/users_auth_primary_buttons-[a-f0-9]+\.css}, html)

    digest = html[/users_auth_primary_buttons-([a-f0-9]+)\.css/, 1]
    get "/assets/users_auth_primary_buttons-#{digest}.css"

    assert_response :success
    assert_includes response.body, '.fp-button[data-fp-style="primary"]'
    assert_includes response.body, "oklch(0.3211 0 0)"
    assert_includes response.body, "background-color: oklch(0.3211 0 0)"
  end
end
