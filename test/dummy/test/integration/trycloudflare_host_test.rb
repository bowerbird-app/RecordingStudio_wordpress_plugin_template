# frozen_string_literal: true

require "test_helper"
require "action_dispatch/middleware/host_authorization"

class TrycloudflareHostTest < ActiveSupport::TestCase
  test "development allows Cloudflare quick tunnel hosts" do
    initializer = Rails.root.join("config/initializers/allow_trycloudflare_hosts.rb").read
    assert_includes initializer, 'config.hosts << ".trycloudflare.com"'
    assert_includes initializer, "Rails.env.development?"

    permissions = ActionDispatch::HostAuthorization::Permissions.new([".trycloudflare.com"])
    assert permissions.allows?("amount-led-intermediate-fastest.trycloudflare.com")
    refute permissions.allows?("www.example.com")
  end
end
