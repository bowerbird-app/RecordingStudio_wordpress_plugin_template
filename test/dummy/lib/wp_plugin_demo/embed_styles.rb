# frozen_string_literal: true

module WpPluginDemo
  module EmbedStyles
    SHEETS = {
      variables: "app/assets/stylesheets/flat_pack/variables.css",
      application: "app/assets/stylesheets/flat_pack/application.css"
    }.freeze

    CONTAINMENT_CSS = <<~CSS.freeze
      [data-wp-plugin-demo-flatpack] [data-controller="flat-pack--modal"] {
        position: relative !important;
        inset: auto !important;
        display: block !important;
        height: auto !important;
        overflow: visible !important;
        background: transparent !important;
        backdrop-filter: none !important;
      }

      [data-wp-plugin-demo-flatpack] [data-controller="flat-pack--modal"] [role="dialog"] {
        opacity: 1 !important;
        transform: none !important;
        max-height: none !important;
      }

      [data-wp-plugin-demo-flatpack] [data-controller="flat-pack--modal"] .min-h-screen {
        min-height: 0 !important;
      }
    CSS

    EMBED_ROOT = "[data-wordpress-plugin-demo-embed]"

    TYPOGRAPHY_CSS = <<~CSS.freeze
      [data-wordpress-plugin-demo-embed],
      [data-wp-plugin-demo-flatpack] {
        --font-sans: system-ui, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        --font-mono: ui-monospace, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
        --font-weight-medium: 500;
        --font-weight-semibold: 600;
        --font-weight-bold: 700;
        --leading-tight: 1.25;
        --leading-normal: 1.5;
        font-family: var(--font-sans) !important;
        line-height: var(--leading-normal) !important;
      }

      [data-wordpress-plugin-demo-embed] :is(h1, h2, h3, h4, h5, h6, p, a, span, button, li, label, div),
      [data-wp-plugin-demo-flatpack] :is(h1, h2, h3, h4, h5, h6, p, a, span, button, li, label, div) {
        font-family: var(--font-sans) !important;
      }

      [data-wordpress-plugin-demo-embed] :is(h1, h2, h3, h4, h5, h6) {
        line-height: var(--leading-tight) !important;
        margin-block: 0 !important;
      }

      [data-wordpress-plugin-demo-embed] [data-wp-plugin-demo-flatpack-part="card"] :is(h1, h2, h3, h4, h5, h6, p) {
        margin-block: 0 !important;
      }

      [data-wordpress-plugin-demo-embed] [data-wp-plugin-demo-flatpack-part="card"] .mb-6 {
        margin-bottom: 0;
      }

      [data-wp-plugin-demo-flatpack] .font-medium {
        font-weight: var(--font-weight-medium) !important;
      }

      [data-wp-plugin-demo-flatpack] .font-semibold {
        font-weight: var(--font-weight-semibold) !important;
      }

      [data-wp-plugin-demo-flatpack] .font-bold {
        font-weight: var(--font-weight-bold) !important;
      }
    CSS

    # WordPress theme rules such as `.wp-block a` and `.editor-styles-wrapper .wp-block a`
    # are unlayered and beat Tailwind's `@layer utilities`. These sit on the embed root
    # so the primary label stays readable on the primary fill.
    BUTTON_CSS = <<~CSS.freeze
      [data-wordpress-plugin-demo-embed] :is(a, button).fp-button[data-fp-style] {
        color: var(--fp-button-text, var(--color-primary-text, #fff));
        background-color: var(--fp-button-background, var(--color-primary));
      }
    CSS

    module_function

    def style_tag
      %(<style data-wp-plugin-demo-flatpack-assets="1">#{css}</style>).html_safe
    end

    def css
      sheets = scope_to_embed_root([packed_sheets, dummy_tailwind].join("\n"))
      [sheets, TYPOGRAPHY_CSS, CONTAINMENT_CSS, BUTTON_CSS].join("\n")
    end

    def packed_sheets
      SHEETS.values.map { |relative| strip_imports(read_engine_sheet(relative)) }.join("\n")
    end

    def dummy_tailwind
      path = Rails.root.join("app/assets/builds/tailwind.css")
      raise ArgumentError, "missing #{path}; run bin/rails tailwindcss:build" unless path.file?

      path.read
    end

    def read_engine_sheet(relative)
      path = FlatPack::Engine.root.join(relative)
      raise ArgumentError, "missing FlatPack sheet #{relative}" unless path.file?

      path.read
    end

    def strip_imports(css)
      css.gsub(/^@import\s+[^;]+;\s*/m, "")
    end

    def scope_to_embed_root(css)
      transform_css(css, keyframes: 0)
    end

    def transform_css(css, keyframes:)
      out = +""
      index = 0
      while index < css.length
        if css[index, 2] == "/*"
          close = css.index("*/", index + 2)
          close = css.length - 2 if close.nil?
          out << css[index..close + 1]
          index = close + 2
          next
        end

        if css[index] == "@"
          name, prelude, index = read_at_rule(css, index)
          kind = name.downcase

          if kind == "layer"
            if prelude.include?("{")
              inner_source, index = take_block_body(css, index)
              out << transform_css(inner_source, keyframes: keyframes)
            end
            next
          end

          if kind == "theme" && prelude.include?("{")
            _, index = take_block_body(css, index)
            next
          end

          if %w[media supports].include?(kind) && prelude.include?("{")
            inner_source, index = take_block_body(css, index)
            inner = transform_css(inner_source, keyframes: keyframes)
            out << "@#{name}#{prelude.sub(/\{\s*\z/, "")}{#{inner}}"
            next
          end

          if kind == "keyframes" && prelude.include?("{")
            inner_source, index = take_block_body(css, index)
            inner = transform_css(inner_source, keyframes: keyframes + 1)
            out << "@#{name}#{prelude.sub(/\{\s*\z/, "")}{#{inner}}"
            next
          end

          if prelude.include?("{")
            inner, index = take_block_body(css, index)
            out << "@#{name}#{prelude.sub(/\{\s*\z/, "")}{#{inner}}"
            next
          end

          out << "@#{name}#{prelude}"
          next
        end

        if css[index] =~ /\s/
          out << css[index]
          index += 1
          next
        end

        selector, brace, index = read_selector(css, index)
        if brace
          body, index = raw_block(css, index)
          selector = keyframes.positive? ? selector : rewrite_selector_list(selector)
          out << "#{selector}{#{body}}"
        else
          out << selector
        end
      end
      out
    end

    # index points at the first character inside a block. The opening brace is already consumed.
    def take_block_body(css, index)
      depth = 1
      cursor = index
      while cursor < css.length && depth.positive?
        if css[cursor, 2] == "/*"
          close = css.index("*/", cursor + 2) || (css.length - 2)
          cursor = close + 2
          next
        end
        char = css[cursor]
        if char == '"' || char == "'"
          cursor = skip_string(css, cursor)
          next
        end
        if char == "{"
          depth += 1
        elsif char == "}"
          depth -= 1
          return [css[index...cursor], cursor + 1] if depth.zero?
        end
        cursor += 1
      end
      [css[index..], css.length]
    end

    def raw_block(css, index)
      raise ArgumentError, "expected block" unless css[index] == "{"

      depth = 0
      cursor = index
      while cursor < css.length
        if css[cursor, 2] == "/*"
          close = css.index("*/", cursor + 2) || (css.length - 2)
          cursor = close + 2
          next
        end
        char = css[cursor]
        if char == '"' || char == "'"
          cursor = skip_string(css, cursor)
          next
        end
        if char == "{"
          depth += 1
        elsif char == "}"
          depth -= 1
          return [css[(index + 1)...cursor], cursor + 1] if depth.zero?
        end
        cursor += 1
      end
      [css[(index + 1)..], css.length]
    end

    def read_at_rule(css, index)
      start = index + 1
      cursor = start
      while cursor < css.length
        if css[cursor, 2] == "/*"
          close = css.index("*/", cursor + 2) || (css.length - 2)
          cursor = close + 2
          next
        end
        break if css[cursor] =~ /[\s{(;]/

        cursor += 1
      end
      name = css[start...cursor]
      prelude = +""
      paren = 0
      while cursor < css.length
        if css[cursor, 2] == "/*"
          close = css.index("*/", cursor + 2) || (css.length - 2)
          prelude << css[cursor..close + 1]
          cursor = close + 2
          next
        end
        char = css[cursor]
        prelude << char
        cursor += 1
        paren += 1 if char == "("
        paren -= 1 if char == ")" && paren.positive?
        break if paren.zero? && (char == "{" || char == ";")
      end
      [name, prelude, cursor]
    end

    def read_selector(css, index)
      start = index
      cursor = index
      paren = 0
      while cursor < css.length
        if css[cursor, 2] == "/*"
          close = css.index("*/", cursor + 2) || (css.length - 2)
          cursor = close + 2
          next
        end
        char = css[cursor]
        if char == '"' || char == "'"
          cursor = skip_string(css, cursor)
          next
        end
        paren += 1 if char == "("
        paren -= 1 if char == ")" && paren.positive?
        if paren.zero? && char == "{"
          return [css[start...cursor], true, cursor]
        end
        cursor += 1
      end
      [css[start..], false, css.length]
    end

    def rewrite_selector_list(list)
      split_selectors(list).map { |selector| rewrite_selector(selector.strip) }.reject(&:empty?).join(",")
    end

    def split_selectors(list)
      parts = []
      current = +""
      paren = 0
      cursor = 0
      while cursor < list.length
        char = list[cursor]
        if char == '"' || char == "'"
          finish = skip_string(list, cursor)
          current << list[cursor...finish]
          cursor = finish
          next
        end
        paren += 1 if char == "("
        paren -= 1 if char == ")" && paren.positive?
        if char == "," && paren.zero?
          parts << current
          current = +""
        else
          current << char
        end
        cursor += 1
      end
      parts << current
      parts
    end

    def rewrite_selector(selector)
      return selector if selector.empty? || selector.include?(EMBED_ROOT)

      rewritten = selector.gsub(/:root\b|:host\b/, EMBED_ROOT)
      rewritten = rewritten.sub(/\A\[data-theme(?:="[^"]*"|='[^']*')?\]/, "#{EMBED_ROOT}\\0")
      return rewritten if rewritten.start_with?(EMBED_ROOT)

      "#{EMBED_ROOT} #{rewritten}"
    end

    def skip_string(css, index)
      quote = css[index]
      cursor = index + 1
      while cursor < css.length
        return cursor + 1 if css[cursor] == quote && css[cursor - 1] != "\\"

        cursor += 1
      end
      css.length
    end
    private_class_method :packed_sheets, :dummy_tailwind, :read_engine_sheet, :strip_imports,
      :scope_to_embed_root, :transform_css, :take_block_body, :raw_block, :read_at_rule,
      :read_selector, :rewrite_selector_list, :split_selectors, :rewrite_selector, :skip_string
  end
end
