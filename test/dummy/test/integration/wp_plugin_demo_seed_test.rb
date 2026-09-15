# frozen_string_literal: true

require "test_helper"

class WpPluginDemoSeedTest < ActiveSupport::TestCase
  setup do
    load Rails.root.join("db/seeds.rb").to_s
  end

  test "getting_started_page_recording_id points at seeded Getting Started page" do
    WpPluginDemo::Seed.ensure_studio_embed!
    page_recording_id = WpPluginDemo::Seed.getting_started_page_recording_id

    page = Page.find_by!(title: "Getting Started")
    expected = RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil).id
    assert_equal expected, page_recording_id
  end

  test "issue_runbook_connection! returns Getting Started page and named-API credentials" do
    connection = WpPluginDemo::Seed.issue_runbook_connection!(host_base_url: "http://localhost:3000")

    assert_equal "http://localhost:3000", connection.host_base_url
    assert_equal WpPluginDemo::Seed.getting_started_page_recording_id, connection.page_recording_id
    assert_match(/\A\S+\z/, connection.oauth_client_id)
    assert_match(/\A\S+\z/, connection.oauth_client_secret)
    assert_equal WpPluginDemo::Contract::TOKEN_PATH, connection.token_path
    assert_equal WpPluginDemo::Contract.actions_embed_path(connection.page_recording_id), connection.embed_path

    result = RecordingStudioApi::Services::IssueOauthAccessToken.call(
      grant_type: WpPluginDemo::Contract::TOKEN_GRANT,
      client_id: connection.oauth_client_id,
      client_secret: connection.oauth_client_secret,
      api: WpPluginDemo::Contract::API_KEY
    )
    assert result.success?, result.error&.message
    refute_nil result.value.fetch(:access_token)
  end
end
