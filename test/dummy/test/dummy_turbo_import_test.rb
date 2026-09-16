# frozen_string_literal: true

require "test_helper"

class DummyTurboImportTest < ActiveSupport::TestCase
  test "application.js imports turbo so Admin lazy table frames can load" do
    application_js = Rails.root.join("app/javascript/application.js").read

    assert_includes application_js, 'import "@hotwired/turbo-rails"'
  end
end
