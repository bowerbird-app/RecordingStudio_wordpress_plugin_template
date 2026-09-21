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

  test "packed CSS keeps FlatPack family and weights loadable from a WordPress origin" do
    css = WpPluginDemo::EmbedStyles.css
    unlayered = strip_at_layers(css)
    document = wordpress_shaped_document(css)

    refute_includes css, "@font-face"
    refute_match(/url\(\s*["']?(?!data:|https?:)/i, css)
    assert_includes css, "--font-sans: system-ui"
    assert_includes css, "--font-weight-medium"
    assert_includes css, "--font-weight-semibold"
    assert_includes css, "--font-weight-bold"
    assert_match(
      /\[data-wordpress-plugin-demo-embed\][^{]*\{[^}]*font-family:\s*var\(--font-sans\)/,
      unlayered
    )
    assert_match(
      /\[data-wp-plugin-demo-flatpack\][^{]*\.font-medium[^{]*\{[^}]*font-weight:\s*var\(--font-weight-medium\)/,
      unlayered
    )
    assert_match(
      /\[data-wp-plugin-demo-flatpack\][^{]*\.font-semibold[^{]*\{[^}]*font-weight:\s*var\(--font-weight-semibold\)/,
      unlayered
    )
    assert_match(
      /\[data-wp-plugin-demo-flatpack\][^{]*\.font-bold[^{]*\{[^}]*font-weight:\s*var\(--font-weight-bold\)/,
      unlayered
    )
    assert_includes document, 'data-wp-plugin-demo-flatpack-assets="1"'
    assert_includes document, "font-family: var(--font-sans)"
    assert_includes document, ".font-medium"
    assert_includes document, ".font-bold"
    refute_includes document, "<link"
    refute_includes document, "<script"
  end

  private

  def wordpress_shaped_document(css)
    <<~HTML
      <!DOCTYPE html>
      <html>
        <head>
          <style>
            body, .entry-content { font-family: "Times New Roman", Times, serif; font-weight: 400; }
            h1, h2, h3, p, a, span { font-family: Georgia, serif; font-weight: 400; }
          </style>
        </head>
        <body>
          <div class="entry-content rs-widget">
            <style data-wp-plugin-demo-flatpack-assets="1">#{css}</style>
            <article data-wordpress-plugin-demo-embed="page">
              <div data-wp-plugin-demo-flatpack="1">
                <h3 class="font-bold">Connect the block</h3>
                <span class="font-medium">Host is live</span>
              </div>
            </article>
          </div>
        </body>
      </html>
    HTML
  end

  def strip_at_layers(css)
    out = +""
    index = 0
    while (start = css.index(/@layer\b/, index))
      out << css[index...start]
      open_at = css.index("{", start)
      break unless open_at

      depth = 0
      cursor = open_at
      while cursor < css.length
        case css[cursor]
        when "{"
          depth += 1
        when "}"
          depth -= 1
          if depth.zero?
            cursor += 1
            break
          end
        end
        cursor += 1
      end
      index = cursor
    end
    out << css[index..]
  end
end
