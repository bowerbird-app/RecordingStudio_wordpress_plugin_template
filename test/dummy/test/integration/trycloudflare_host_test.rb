# frozen_string_literal: true

require "test_helper"

class TrycloudflareHostTest < ActionDispatch::IntegrationTest
  test "local Rails allows Cloudflare quick tunnel hosts" do
    host! "cotton-alumni-workshop-crown.trycloudflare.com"
    get "/up"
    assert_response :success
  end
end
