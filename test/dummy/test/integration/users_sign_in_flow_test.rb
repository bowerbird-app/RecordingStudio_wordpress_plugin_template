# frozen_string_literal: true

require "test_helper"

class UsersSignInFlowTest < ActionDispatch::IntegrationTest
  setup do
    load Rails.root.join("db/seeds.rb").to_s
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "auth chrome Tailwind sheet paints primary buttons outside layers" do
    get new_user_session_path

    assert_response :success
    assert_select "button.fp-button[data-fp-style=primary]", text: "Continue with email"
    assert_match(%r{/assets/tailwind-[a-f0-9]+\.css}, response.body)
    refute_includes response.body, "data-wp-plugin-demo-flatpack-assets"

    digest = response.body[/tailwind-([a-f0-9]+)\.css/, 1]
    get "/assets/tailwind-#{digest}.css"

    assert_response :success
    unlayered = strip_at_layers(response.body)
    assert_includes response.body, "background-color:#0000"
    assert_match(/\.fp-button\[data-fp-style=["']?primary["']?\]/, unlayered)
    assert_match(/--fp-button-background:\s*var\(--button-primary-background-color\)/, unlayered)
    assert_match(
      /\.fp-button\{[^}]*background-color:\s*var\(--fp-button-background\)/,
      unlayered
    )

    post new_user_session_path, params: { user: { email: "admin@admin.com" } }
    follow_redirect!

    assert_response :success
    assert_select "button.fp-button[data-fp-style=primary]", text: "Sign in"
    assert_match(%r{/assets/tailwind-[a-f0-9]+\.css}, response.body)
    refute_includes response.body, "data-wp-plugin-demo-flatpack-assets"
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

  def strip_at_layers(css)
    out = +""
    index = 0
    while (start = css.index(/@layer\b/, index))
      out << css[index...start]
      open_at = css.index("{", start)
      break unless open_at

      depth = 0
      cursor = open_at
      while cursor < css.length
        case css[cursor]
        when "{"
          depth += 1
        when "}"
          depth -= 1
          if depth.zero?
            cursor += 1
            break
          end
        end
        cursor += 1
      end
      index = cursor
    end
    out << css[index..]
  end
end
