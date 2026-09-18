# frozen_string_literal: true

require "test_helper"

class WpPluginDemoPageIndexTest < ActionDispatch::IntegrationTest
  setup do
    load Rails.root.join("db/seeds.rb").to_s
    WpPluginDemo::Seed.ensure_studio_embed!
  end

  test "named API lists pages for a runbook client" do
    connection = WpPluginDemo::Seed.issue_runbook_connection!(host_base_url: "http://localhost:3000")
    result = RecordingStudioApi::Services::IssueOauthAccessToken.call(
      grant_type: WpPluginDemo::Contract::TOKEN_GRANT,
      client_id: connection.oauth_client_id,
      client_secret: connection.oauth_client_secret,
      api: WpPluginDemo::Contract::API_KEY
    )
    assert result.success?, result.error&.message
    token = result.value.fetch(:access_token)

    get "#{WpPluginDemo::Contract::NAMED_RESOURCE_PREFIX}/pages",
        headers: {
          "Authorization" => "Bearer #{token}",
          "Accept" => "application/json"
        }

    assert_response :ok
    body = JSON.parse(response.body)
    records = body.fetch("records")
    ids = records.map { |item| item.fetch("id") }
    assert_includes ids, connection.page_recording_id
    getting_started = records.find { |item| item.fetch("id") == connection.page_recording_id }
    assert_equal "Getting Started", getting_started.fetch("title")
  end
end
