# frozen_string_literal: true

require "test_helper"

class OauthAuthorizeLoginRedirectTest < ActionDispatch::IntegrationTest
  test "signed-out oauth authorize redirects to Users auth chrome" do
    get "/recording_studio_oauth/oauth/authorize"

    assert_redirected_to new_user_session_path
    follow_redirect!

    assert_users_auth_chrome!
  end

  test "signed-out oauth authorize with query redirects to Users auth chrome" do
    get "/recording_studio_oauth/oauth/authorize", params: {
      client_id: "missing",
      redirect_uri: "http://127.0.0.1:3000/callback",
      response_type: "code",
      state: "phase-1"
    }

    assert_redirected_to new_user_session_path
    follow_redirect!

    assert_users_auth_chrome!
  end

  private

  def assert_users_auth_chrome!
    assert_response :success
    assert_select "html[data-theme='rounded']"
    assert_select "h2", text: "Welcome back"
    assert_select "button[type='submit']", text: "Continue with email"
    refute_includes response.body, "Remember me"
    refute_includes response.body, "--card-background-color"
    refute_includes response.body, "Default: admin@admin.com"
  end
end
