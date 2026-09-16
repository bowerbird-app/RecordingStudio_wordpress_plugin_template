# frozen_string_literal: true

require "test_helper"

class DummyHostNavHelperTest < ActionView::TestCase
  tests ApplicationHelper

  setup do
    @request = ActionDispatch::TestRequest.create
    @view_flow = ActionView::OutputFlow.new
  end

  test "nav items are Home, Recordings tree, API Keys, Pages" do
    items = dummy_host_nav_items
    keys = items.map { |item| item.fetch(:key) }

    assert_equal %i[home recordings_tree api_keys pages], keys
    refute items.any? { |item| item.fetch(:key) == :registered_apps }
    refute items.any? { |item| item.fetch(:text) == "Registered apps" }
  end

  test "API Keys href uses api clients engine path" do
    items = dummy_host_nav_items
    api_keys = items.find { |item| item.fetch(:key) == :api_keys }
    pages = items.find { |item| item.fetch(:key) == :pages }

    assert_equal "API Keys", api_keys.fetch(:text)
    assert_equal recording_studio_api.api_clients_path, api_keys.fetch(:href)
    assert_equal "Pages", pages.fetch(:text)
    assert_equal main_app.pages_path, pages.fetch(:href)
  end

  test "API Keys is active on api clients paths and Pages is not" do
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
      pages = items.find { |item| item.fetch(:key) == :pages }

      assert api_keys.fetch(:active), "expected API Keys active on #{path}"
      refute pages.fetch(:active), "expected Pages inactive on #{path}"
    end
  end

  test "Pages is active on pages paths and API Keys is not" do
    [
      main_app.pages_path,
      "#{main_app.pages_path}/abc-123",
      "#{main_app.pages_path}/abc-123/embed_preview"
    ].each do |path|
      @request.path = path
      items = dummy_host_nav_items
      pages = items.find { |item| item.fetch(:key) == :pages }
      api_keys = items.find { |item| item.fetch(:key) == :api_keys }

      assert pages.fetch(:active), "expected Pages active on #{path}"
      refute api_keys.fetch(:active), "expected API Keys inactive on #{path}"
    end
  end

  test "API Keys is inactive outside api clients including admin paths" do
    @request.path = "/recording_studio_api/apis/wp_plugin_demo/oauth/token"
    items = dummy_host_nav_items
    api_keys = items.find { |item| item.fetch(:key) == :api_keys }
    pages = items.find { |item| item.fetch(:key) == :pages }

    refute api_keys.fetch(:active)
    refute pages.fetch(:active)

    @request.path = "/admin/screens/oauth_clients"
    items = dummy_host_nav_items
    api_keys = items.find { |item| item.fetch(:key) == :api_keys }
    pages = items.find { |item| item.fetch(:key) == :pages }

    refute api_keys.fetch(:active)
    refute pages.fetch(:active)
  end
end
