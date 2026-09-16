# frozen_string_literal: true

require "test_helper"

class DummyHostNavHelperTest < ActionView::TestCase
  tests ApplicationHelper

  setup do
    @request = ActionDispatch::TestRequest.create
    @view_flow = ActionView::OutputFlow.new
  end

  test "API Keys href uses api clients engine path" do
    items = dummy_host_nav_items
    api_keys = items.find { |item| item.fetch(:key) == :api_keys }
    registered = items.find { |item| item.fetch(:key) == :registered_apps }

    assert_equal "API Keys", api_keys.fetch(:text)
    assert_equal recording_studio_api.api_clients_path, api_keys.fetch(:href)
    assert_equal ApplicationHelper::REGISTERED_APPS_PATH, registered.fetch(:href)
  end

  test "API Keys is active on api clients paths and Registered apps is not" do
    api_clients_path = recording_studio_api.api_clients_path

    [
      api_clients_path,
      "#{api_clients_path}/new",
      "#{api_clients_path}/abc-123",
      "#{api_clients_path}/abc-123/edit"
    ].each do |path|
      @request.path = path
      items = dummy_host_nav_items
      api_keys = items.find { |item| item.fetch(:key) == :api_keys }
      registered = items.find { |item| item.fetch(:key) == :registered_apps }

      assert api_keys.fetch(:active), "expected API Keys active on #{path}"
      refute registered.fetch(:active), "expected Registered apps inactive on #{path}"
    end
  end

  test "API Keys is inactive outside api clients and Registered apps stays admin-only" do
    @request.path = "/recording_studio_api/apis/wp_plugin_demo/oauth/token"
    items = dummy_host_nav_items
    api_keys = items.find { |item| item.fetch(:key) == :api_keys }
    registered = items.find { |item| item.fetch(:key) == :registered_apps }

    refute api_keys.fetch(:active)
    refute registered.fetch(:active)

    @request.path = "/admin/screens/oauth_clients"
    items = dummy_host_nav_items
    api_keys = items.find { |item| item.fetch(:key) == :api_keys }
    registered = items.find { |item| item.fetch(:key) == :registered_apps }

    refute api_keys.fetch(:active)
    assert registered.fetch(:active)
  end
end
