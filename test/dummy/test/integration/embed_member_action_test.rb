# frozen_string_literal: true

require "test_helper"

class EmbedMemberActionTest < ActionDispatch::IntegrationTest
  setup do
    @client = WpPluginDemo::Provision.isolated_client!
  end

  test "named actions embed returns schema v1" do
    before = RecordingStudioEmbeddable::EmbeddableViewLog.count

    get WpPluginDemo::Contract.actions_embed_path(@client.page_recording_id),
        headers: @client.bearer_headers.merge("Accept" => "application/json")

    assert_response :ok
    payload = WpPluginDemo::Contract.parse_browser_payload!(JSON.parse(response.body))
    assert_equal 1, payload.schema_version
    assert_equal before, RecordingStudioEmbeddable::EmbeddableViewLog.count
  end

  test "named short alias returns schema v1" do
    get WpPluginDemo::Contract.short_embed_path(@client.page_recording_id),
        headers: @client.bearer_headers.merge("Accept" => "application/json")

    assert_response :ok
    payload = WpPluginDemo::Contract.parse_browser_payload!(JSON.parse(response.body))
    assert_equal 1, payload.schema_version
  end

  test "named embed without bearer is unauthorized" do
    get WpPluginDemo::Contract.actions_embed_path(@client.page_recording_id),
        headers: { "Accept" => "application/json" }

    assert_response :unauthorized
  end

  test "named embed out of scope is not found" do
    foreign = WpPluginDemo::Provision.foreign_page!

    get WpPluginDemo::Contract.actions_embed_path(foreign.id),
        headers: @client.bearer_headers.merge("Accept" => "application/json")

    assert_response :not_found
  end

  test "named oauth token path issues bearer" do
    post WpPluginDemo::Contract::TOKEN_PATH,
         params: {
           grant_type: WpPluginDemo::Contract::TOKEN_GRANT,
           client_id: @client.oauth_client_id,
           client_secret: @client.oauth_client_secret
         }

    assert_response :ok
    token = JSON.parse(response.body).fetch("access_token")

    get WpPluginDemo::Contract.actions_embed_path(@client.page_recording_id),
        headers: {
          "Authorization" => "Bearer #{token}",
          "Accept" => "application/json"
        }

    assert_response :ok
    payload = WpPluginDemo::Contract.parse_browser_payload!(JSON.parse(response.body))
    assert_equal 1, payload.schema_version
  end

  test "soft registered embed handler is gem owned" do
    assert_nothing_raised do
      WpPluginDemo::Contract.assert_gem_owned_embed_handler!
    end
  end

  test "soft register boot order is after config api" do
    source = File.read(Rails.root.join("config/initializers/recording_studio_api.rb"))
    order = WpPluginDemo::Contract.parse_soft_register_order!(source)

    assert order.valid?
  end

  test "named token does not use public embed path" do
    get WpPluginDemo::Contract.public_actions_embed_path(@client.page_recording_id),
        headers: @client.bearer_headers.merge("Accept" => "application/json")

    refute_equal 200, response.status
  end

  test "iframe embed route still recognized" do
    assert_recognizes(
      {
        controller: "recording_studio_embeddable/embeds",
        action: "show",
        token: "example-token"
      },
      "/recording_studio_embeddable/embeds/example-token"
    )
  end
end
