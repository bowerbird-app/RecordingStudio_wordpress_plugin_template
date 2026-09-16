# frozen_string_literal: true

require "test_helper"
require "devise/test/integration_helpers"

class PagesBrowserTest < ActionDispatch::IntegrationTest
  include Devise::Test::IntegrationHelpers

  setup do
    load Rails.root.join("db/seeds.rb").to_s

    @user = User.find_by!(email: "admin@admin.com")
    sign_in @user

    @page = Page.find_by!(title: "Getting Started")
    @page_recording = RecordingStudio::Recording.find_by!(recordable: @page, trashed_at: nil)
  end

  teardown do
    Current.actor = nil if defined?(Current)
  end

  test "pages index lists Getting Started with recording id" do
    get pages_path

    assert_response :success
    assert_select "body[data-dummy-host-layout='true']", count: 1
    assert_includes response.body, "Getting Started"
    assert_includes response.body, @page_recording.id
    assert_select "a[href=?]", page_path(@page_recording)
  end

  test "pages show hosts iframe to embed preview" do
    get page_path(@page_recording)

    assert_response :success
    assert_select "body[data-dummy-host-layout='true']", count: 1
    assert_includes response.body, "WordPress embed preview"
    assert_select "iframe[title=?][src=?]",
                  "WordPress embed preview",
                  embed_preview_page_path(@page_recording)
  end

  test "embed preview renders Getting Started without host chrome" do
    get embed_preview_page_path(@page_recording)

    assert_response :success
    assert_includes response.body, "Getting Started"
    assert_select "article[data-wordpress-plugin-demo-embed='page']", count: 1
    assert_select "body[data-dummy-host-layout='true']", count: 0
    refute_includes response.body, "flat-pack--sidebar-layout"
    refute_includes response.body, "flat_pack/application"
  end
end
