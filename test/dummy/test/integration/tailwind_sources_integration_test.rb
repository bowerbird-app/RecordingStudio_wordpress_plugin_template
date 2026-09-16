# frozen_string_literal: true

require "test_helper"

class TailwindSourcesIntegrationTest < ActionDispatch::IntegrationTest
  test "development config allows trycloudflare preview hosts" do
    development_rb = Rails.root.join("config/environments/development.rb").read
    assert_includes development_rb, 'config.hosts << ".trycloudflare.com"'
  end

  test "sign in page links a non-empty built Tailwind stylesheet" do
    ensure_flatpack_tailwind_build!

    get new_user_session_path
    assert_response :success

    assert_match(%r{/assets/tailwind-[a-f0-9]+\.css}, response.body)

    digest = response.body[/tailwind-([a-f0-9]+)\.css/, 1]
    get "/assets/tailwind-#{digest}.css"
    assert_response :success
    assert_operator response.body.bytesize, :>, 50_000,
                    "Tailwind build should include FlatPack utilities (was ~17KB when @source paths missed gems)"
    assert_includes response.body, "surface-page-background-color"
  end

  private

  def ensure_flatpack_tailwind_build!
    css_path = Rails.root.join("app/assets/builds/tailwind.css")
    return if css_path.exist? && css_path.size > 50_000

    assert system(
      "bundle", "exec", "rails", "recording_studio_root_switchable:link_tailwind_sources",
      chdir: Rails.root.to_s,
      out: File::NULL,
      err: File::NULL
    ), "link_tailwind_sources should succeed"
    assert system(
      "bundle", "exec", "rails", "tailwindcss:build",
      chdir: Rails.root.to_s,
      out: File::NULL,
      err: File::NULL
    ), "tailwindcss:build should succeed"
  end
end
