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

    TYPOGRAPHY_CSS = <<~CSS.freeze
      [data-wordpress-plugin-demo-embed],
      [data-wp-plugin-demo-flatpack] {
        --font-sans: system-ui, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        --font-mono: ui-monospace, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
        --font-weight-medium: 500;
        --font-weight-semibold: 600;
        --font-weight-bold: 700;
        font-family: var(--font-sans) !important;
      }

      [data-wordpress-plugin-demo-embed] :is(h1, h2, h3, h4, h5, h6, p, a, span, button, li, label, div),
      [data-wp-plugin-demo-flatpack] :is(h1, h2, h3, h4, h5, h6, p, a, span, button, li, label, div) {
        font-family: var(--font-sans) !important;
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

    module_function

    def style_tag
      %(<style data-wp-plugin-demo-flatpack-assets="1">#{css}</style>).html_safe
    end

    def css
      [packed_sheets, dummy_tailwind, TYPOGRAPHY_CSS, CONTAINMENT_CSS].join("\n")
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
    private_class_method :packed_sheets, :dummy_tailwind, :read_engine_sheet, :strip_imports
  end
end
