# frozen_string_literal: true

require "test_helper"

class WpPluginDemoEmbedStylesTest < ActiveSupport::TestCase
  test "style tag packs FlatPack tokens and dummy Tailwind without import or script tags" do
    tag = WpPluginDemo::EmbedStyles.style_tag

    assert_includes tag, 'data-wp-plugin-demo-flatpack-assets="1"'
    assert_includes tag, "--badge-success-background-color"
    assert_includes tag, "--card-background-color"
    assert_includes tag, "--tabs-pill-active-background-color"
    assert_includes tag, "--modal-surface-color"
    assert_includes tag, "data-controller=\"flat-pack--modal\""
    refute_includes tag, "@import"
    refute_includes tag, "<link"
    refute_includes tag, "<script"
  end
end
