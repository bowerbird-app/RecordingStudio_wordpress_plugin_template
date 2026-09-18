# frozen_string_literal: true

require "test_helper"

class RecordingStudioWordpressPluginTemplateTest < Minitest::Test
  def test_version_matches_release
    assert_equal "0.4.15", ::RecordingStudioWordpressPluginTemplate::VERSION
  end

  def test_engine_exists
    assert_kind_of Class, ::RecordingStudioWordpressPluginTemplate::Engine
  end

  def test_gemspec_pins_recording_studio_4_2
    gemspec = File.read(File.expand_path("../recording_studio_wordpress_plugin_template.gemspec", __dir__))

    assert_includes gemspec, 'spec.add_dependency "recording_studio", "~> 4.2"'
  end

  def test_gemspec_excludes_cursor_config
    spec = Gem::Specification.load(File.expand_path("../recording_studio_wordpress_plugin_template.gemspec", __dir__))
    cursor_files = spec.files.select { |path| path == ".cursor" || path.split("/").include?(".cursor") }
    leaked_host = spec.files.select do |path|
      path.start_with?("wordpress/", "test/") || path.include?("test/dummy")
    end

    assert_empty cursor_files, "gemspec must not package .cursor/ (got #{cursor_files.inspect})"
    assert_empty leaked_host, "gemspec must not package dummy or WordPress trees (got #{leaked_host.inspect})"
  end

  def test_cursor_environment_is_repo_managed_without_snapshot
    path = File.expand_path("../.cursor/environment.json", __dir__)
    json = JSON.parse(File.read(path))

    assert_equal "recording-studio-wordpress-plugin-template", json["name"]
    assert_equal ".cursor/install.sh", json["install"]
    assert_equal ".cursor/start.sh", json["start"]
    refute json.key?("snapshot"), "snapshot pins a Personal build and skips install"
    refute json.key?("agentCanUpdateSnapshot")
    ports = json.fetch("ports").map { |entry| entry.fetch("port") }
    assert_includes ports, 3000
    assert_includes ports, 8888
  end

  def test_wordpress_plugin_registers_wp_plugin_demo_dynamic_block
    plugin_root = File.expand_path("../wordpress/recording-studio-widgets", __dir__)
    plugin = File.read(File.join(plugin_root, "recording-studio-widget.php"))
    block = JSON.parse(File.read(File.join(plugin_root, "src/recording-studio-widget/block.json")))
    env = JSON.parse(File.read(File.join(plugin_root, ".wp-env.json")))

    assert_includes plugin, "Plugin Name:       WordPress Plugin Demo"
    assert_includes plugin, "recording_studio_widget_register_settings_page"
    assert_equal "recording-studio/recording-studio-widget", block["name"]
    assert_equal "WordPress Plugin Demo", block["title"]
    assert_equal "file:./render.php", block["render"]
    assert_equal "file:./front.js", block["viewScript"]
    assert block.dig("attributes", "pageRecordingId")
    assert_equal 8888, env["port"]
    assert_equal 8889, env["testsPort"]
  end

  def test_ci_runs_plugin_lint_and_package_boundary_jobs
    workflow = File.read(File.expand_path("../.github/workflows/ci.yml", __dir__))

    assert_includes workflow, "recording_studio_wordpress_plugin_template_test"
    refute_includes workflow, %w[gem template test].join("_")
    assert_includes workflow, "plugin-js:"
    assert_includes workflow, "plugin-php:"
    assert_includes workflow, "packages:"
    assert_includes workflow, "bin/check-package-boundaries"
    assert_includes workflow, "composer install"
    assert_includes workflow, "npm run lint:js"
    assert_includes workflow, "recording-studio-widgets.zip"
  end

  def test_cursor_install_still_fetches_skills
    install_script = File.read(File.expand_path("../.cursor/install.sh", __dir__))

    assert_includes install_script, "fetch-skills.sh"
  end

  def test_dummy_gemfile_pins_verified_4x_github_tags
    gemfile = File.read(File.expand_path("dummy/Gemfile", __dir__))

    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio", tag: "v4.2.0"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_accessible", tag: "v0.9.1"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_root_switchable", tag: "v0.5.0"'
    assert_includes gemfile, 'github: "bowerbird-app/flatpack", tag: "v0.1.177"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_api", tag: "v0.5.6"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_Embeddable", tag: "v0.2.1"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_admin", tag: "v2.0.2"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_publishable", tag: "v0.2.0"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_attachable", tag: "v0.5.1"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_Oauth", tag: "v0.2.2"'
    lockfile = File.read(File.expand_path("dummy/Gemfile.lock", __dir__))
    assert_includes lockfile, "recording_studio_oauth (0.2.2)"
    assert_includes lockfile, "recording_studio_api (0.5.6)"
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_site_settings", tag: "v0.1.0"'
    assert_includes gemfile, 'github: "bowerbird-app/RecordingStudio_users", tag: "v0.11.0"'
    refute_includes gemfile, "recording_studio/v3.0.0"
    refute_includes gemfile, 'tag: "v0.1.133"'
    refute_includes gemfile, 'tag: "v0.6.0"'
    refute_includes gemfile, 'tag: "0.3.1"'
  end

  def test_dummy_schema_includes_accessible_depends_on_recording_id
    schema = File.read(File.expand_path("dummy/db/schema.rb", __dir__))
    migration = File.read(
      File.expand_path(
        "dummy/db/migrate/20260911024811_add_depends_on_recording_id_to_recording_studio_accesses.rb",
        __dir__
      )
    )

    assert_includes schema, 't.uuid "depends_on_recording_id"'
    assert_includes schema, "index_recording_studio_accesses_on_depends_on_recording_id"
    assert_includes migration, "add_column :recording_studio_accesses, :depends_on_recording_id, :uuid"
  end

  def test_template_does_not_ship_copied_core_hooks_or_base_service
    refute File.exist?(File.expand_path("../lib/recording_studio_wordpress_plugin_template/hooks.rb", __dir__))
    refute File.exist?(File.expand_path("../lib/recording_studio_wordpress_plugin_template/services/base_service.rb",
                                        __dir__))
    refute File.exist?(File.expand_path(
                         "../lib/recording_studio_wordpress_plugin_template/services/example_service.rb", __dir__
                       ))
  end

  def test_example_capability_wraps_include_for_and_is_not_enabled_globally
    source = File.read(File.expand_path("../lib/recording_studio_wordpress_plugin_template/capabilities/example.rb",
                                        __dir__))

    assert_includes source, "def self.to(**)"
    assert_includes source, "RecordingStudio::Capabilities.include_for(:example, **)"
    refute_includes source, "enable_capability"
    refute_includes source, "set_capability_options"
    refute RecordingStudio.capability_enabled?(:example, for: "Folder")
    refute RecordingStudio.capability_enabled?(:example, for: "Page")
    assert_empty RecordingStudio.configuration.enabled_recordable_types_for(:example)
  end

  def test_dummy_app_uses_flatpack_host_sidebar_layout
    application_controller_path = File.expand_path("dummy/app/controllers/application_controller.rb", __dir__)
    controller_source = File.read(application_controller_path)
    host_layout = File.read(File.expand_path("dummy/app/views/layouts/host.html.erb", __dir__))
    sidebar = File.read(File.expand_path("dummy/app/views/layouts/flat_pack/_sidebar.html.erb", __dir__))

    assert_includes controller_source, "include RecordingStudio::UsesDefaultLayout"
    assert_includes controller_source, '"host"'
    assert_includes controller_source, "devise_controller? ? \"application\""
    assert File.exist?(File.expand_path("dummy/app/views/layouts/flat_pack/_sidebar.html.erb", __dir__))
    helper_source = File.read(File.expand_path("dummy/app/helpers/application_helper.rb", __dir__))
    assert_includes host_layout, "FlatPack::SidebarLayout::Component"
    assert_includes sidebar, "dummy_host_nav_items"
    assert_includes helper_source, "Home"
    assert_includes helper_source, "Recordings tree"
    assert_includes helper_source, "API Keys"
    assert_includes helper_source, "Pages"
    assert_includes helper_source, "Registered Apps"
  end

  def test_dummy_login_layout_keeps_flatpack_assets_without_tight_main_offset
    application_layout = File.read(File.expand_path("dummy/app/views/layouts/application.html.erb", __dir__))

    assert_includes application_layout, '<html data-theme="rounded">'
    assert_includes application_layout, 'stylesheet_link_tag "flat_pack/variables"'
    assert_includes application_layout, "javascript_importmap_tags"
    assert_includes application_layout, "min-h-screen"
    refute_includes application_layout, "mt-28"
    refute_includes application_layout, "flat_pack_sidebar"
  end

  def test_dummy_tailwind_keeps_flatpack_theme_selection_in_flatpack
    tailwind_source = File.read(File.expand_path("dummy/app/assets/tailwind/application.css", __dir__))

    assert_includes tailwind_source, "../../../vendor/flat_pack/app/components/**/*.{rb,erb}"
    assert_includes tailwind_source, "../../../vendor/recording_studio/app/views/**/*.erb"
    assert_includes tailwind_source, "flatpack-*/app/components/**/*.{rb,erb}"
    assert_includes tailwind_source, "RecordingStudio-*/app/views/**/*.erb"
    assert_includes tailwind_source, "recording_studio_user-*/app/views/**/*.erb"
    assert_includes tailwind_source, "RecordingStudio_users-*/app/views/**/*.erb"
    refute_includes tailwind_source, "RecordingStudio_users-62401500d19a"
    refute_includes tailwind_source, "flatpack-ac45389d5f20"
    refute_includes tailwind_source, "@theme"
    refute_includes tailwind_source, ":root {"
    refute_includes tailwind_source, "--color-fp-primary"
  end

  def test_dummy_tailwind_sources_resolve_via_vendor_symlinks
    dummy_root = File.expand_path("dummy", __dir__)
    gemfile = File.join(dummy_root, "Gemfile")
    env = {
      "BUNDLE_GEMFILE" => gemfile,
      "BUNDLE_APP_CONFIG" => nil,
      "BUNDLE_BIN_PATH" => nil,
      "BUNDLE_LOCKFILE" => nil,
      "BUNDLER_SETUP" => nil,
      "BUNDLER_VERSION" => nil,
      "RUBYLIB" => nil,
      "RUBYOPT" => nil,
      "RAILS_ENV" => "test",
      "DISABLE_SIMPLECOV" => "true"
    }
    env["BUNDLE_PATH"] = ENV["BUNDLE_PATH"] if ENV["BUNDLE_PATH"]

    success = Bundler.with_unbundled_env do
      system(
        env,
        "bundle", "exec", "bin/rails", "recording_studio_root_switchable:link_tailwind_sources",
        chdir: dummy_root,
        out: File::NULL,
        err: File::NULL
      )
    end
    assert success, "link_tailwind_sources should succeed with the dummy Gemfile"

    flat_pack = File.join(dummy_root, "vendor/flat_pack/app/components")
    recording_studio = File.join(dummy_root, "vendor/recording_studio/app/views")

    assert File.directory?(flat_pack), "expected vendor/flat_pack symlink after link_tailwind_sources"
    assert File.directory?(recording_studio), "expected vendor/recording_studio symlink after link_tailwind_sources"
    assert Dir.glob(File.join(flat_pack, "**/*.{rb,erb}")).any?, "FlatPack components should be scannable"
  end

  def test_recording_studio_keeps_strict_recordable_declarations_enabled
    initializer_path = File.expand_path("dummy/config/initializers/recording_studio.rb", __dir__)
    initializer_source = File.read(initializer_path)

    assert_includes initializer_source, "config.require_recordable_declarations = true"
    assert_includes initializer_source, '"Workspace"'
    assert_includes initializer_source, '"Folder"'
    assert_includes initializer_source, '"Page"'
    assert_includes initializer_source, '"RecordingStudioEmbeddable::Embed"'
    assert_includes initializer_source, '"RecordingStudioPublishable::Publishable"'
    assert_includes initializer_source, '"RecordingStudioAttachable::Attachment"'
    assert_includes initializer_source, '"RecordingStudio::Access"'
    assert_includes initializer_source, '"RecordingStudioApi::ApiClient"'
    assert_includes initializer_source, '"RecordingStudioApi::ApiCredential"'
    assert_includes initializer_source, '"RecordingStudioApi::ApiAccessToken"'
    assert_includes initializer_source, '"RecordingStudioApi::AdminApi"'
    assert_includes initializer_source, '"AdminRoot"'
    assert_includes initializer_source, '"RecordingStudioUser::People"'
    assert_includes initializer_source, '"RecordingStudioUser::Profile"'
    assert_includes initializer_source, '"RecordingStudioSiteSettings::SiteSetting"'
    refute_includes initializer_source, "config.include_children"
    refute_includes initializer_source, "config.features."
    refute_includes initializer_source, "v3"
  end

  def test_dummy_api_initializer_registers_admin_root_types
    initializer_source = File.read(File.expand_path("dummy/config/initializers/recording_studio_api.rb", __dir__))

    assert_includes initializer_source, 'config.admin_root_recordable_type_names = ["AdminRoot"]'
  end

  def test_dummy_ignores_embeddable_lib_on_host_zeitwerk
    initializer_source = File.read(
      File.expand_path("dummy/config/initializers/recording_studio_embeddable_zeitwerk.rb", __dir__)
    )

    assert_includes initializer_source, "Rails.autoloaders.main.ignore(embeddable_lib)"
    assert_includes initializer_source, "RecordingStudioEmbeddable::Engine"
  end

  def test_dummy_readme_explains_dummy_app_purpose
    readme_path = File.expand_path("dummy/README.md", __dir__)
    readme_source = File.read(readme_path)

    assert_includes readme_source, "This Rails app is the host for RecordingStudio WordPress widgets"
    assert_includes readme_source, "/recording_studio"
    assert_includes readme_source, "redirects to `/`"
    refute_includes readme_source, "flat_pack_sidebar"
  end

  def test_product_readme_is_the_template_guide
    readme = File.read(File.expand_path("../README.md", __dir__))

    assert_includes readme, "RecordingStudio WordPress widgets"
    assert_includes readme, "https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template"
    assert_includes readme, "bin/check-package-boundaries"
    assert_includes readme, "npm run env start"
    assert_includes readme, "4.98.0"
    assert_includes readme, "v4.2.0"
    assert_includes readme, "v0.1.177"
    assert_includes readme, "v0.9.1"
    assert_includes readme, "dummy GitHub tag `v0.2.2`"
    assert_includes readme, "public clients can exchange codes on the named token URL"
    refute_includes readme, "Internal template"
    refute_includes readme, "internal template"
    refute_includes readme, "v0.1.133"
    refute_includes readme, "v3 declarations"
    refute_includes readme, "RecordingStudio v3"
    refute_includes readme, "ExampleService"
    refute_includes readme, "recording_studio/v3.0.0"
  end

  def test_wordpress_packaging_doc_states_the_artifact_allowlists
    doc = File.read(File.expand_path("../docs/wordpress-packaging.md", __dir__))

    assert_includes doc, "bin/check-package-boundaries"
    assert_includes doc, "test/dummy"
    assert_includes doc, "recording-studio-widget.php"
    refute_includes doc, "Internal template"
  end

  def test_gemspec_uses_wordpress_product_homepage
    gemspec = File.read(File.expand_path("../recording_studio_wordpress_plugin_template.gemspec", __dir__))

    assert_includes gemspec, 'spec.homepage    = "https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template"'
    assert_includes gemspec, "RecordingStudio WordPress widgets"
    refute_includes gemspec, "internal template"
    refute_includes gemspec, "https://github.com/bowerbird-app/recording_studio_wordpress_plugin_template"
  end

  def test_dummy_home_page_uses_demo_title_only
    view_path = File.expand_path("dummy/app/views/home/index.html.erb", __dir__)
    view_source = File.read(view_path)

    assert_includes view_source, 'title: "WordPress widgets host"'
    assert_includes view_source, "API Keys"
    assert_includes view_source, "Pages"
    assert_includes view_source, "Registered Apps"
    refute_includes view_source, "Nothing talks to WordPress yet"
    assert_includes view_source, "FlatPack::Card::Component"
    assert_includes view_source, "dummy_page_nav"
    refute_includes view_source, 'title: "Template Demo"'
    refute_includes view_source, "internal template"
    refute_includes view_source, "FlatPack::Breadcrumb::Component"
  end

  def test_dummy_docs_pages_use_minimal_flatpack_documentation_components
    docs_view_paths = Dir[File.expand_path("dummy/app/views/docs/*.html.erb", __dir__)].reject do |view_path|
      File.basename(view_path).start_with?("_")
    end
    refute_empty docs_view_paths

    docs_view_paths.each do |view_path|
      view_source = File.read(view_path)

      assert_includes view_source, "dummy_page_nav"
      assert_includes view_source, "FlatPack::PageTitle::Component"
      refute_includes view_source, "FlatPack::Card::Component"
      refute_includes view_source, "FlatPack::Breadcrumb::Component"
    end

    methods_view = File.read(File.expand_path("dummy/app/views/docs/methods.html.erb", __dir__))
    assert_includes methods_view, "FlatPack::SectionTitle::Component"
    assert_includes methods_view, "FlatPack::CodeBlock::Component"

    gem_views_view = File.read(File.expand_path("dummy/app/views/docs/gem_views.html.erb", __dir__))
    assert_includes gem_views_view, "FlatPack::Table::Component"
    refute_includes gem_views_view, "FlatPack::List::Component"

    recordable_types_view = File.read(File.expand_path("dummy/app/views/docs/recordable_types.html.erb", __dir__))
    assert_includes recordable_types_view, "FlatPack::List::Component"
    refute_includes recordable_types_view, "v3 parent/root"

    recordings_tree_view = File.read(File.expand_path("dummy/app/views/docs/recordings_tree.html.erb", __dir__))
    assert_includes recordings_tree_view, "FlatPack::Tree::Component"
    refute_includes recordings_tree_view, "Current structure"
    refute_includes recordings_tree_view, "This tree is generated from RecordingStudio::Recording records"
  end

  def test_dummy_recordings_tree_view_omits_structure_section_copy
    recordings_tree_view = File.read(File.expand_path("dummy/app/views/docs/recordings_tree.html.erb", __dir__))

    assert_includes recordings_tree_view, 'title: "Recordings tree"'
    assert_includes recordings_tree_view, "FlatPack::Tree::Component"
    recording_tree_partial = File.read(File.expand_path("dummy/app/views/docs/_recording_tree_node.html.erb", __dir__))
    assert_includes recording_tree_partial, "parent_builder.node"
    refute_includes recordings_tree_view, "Current structure"
    refute_includes recordings_tree_view, "This tree is generated from RecordingStudio::Recording records"
  end

  def test_engine_does_not_ship_a_home_view
    view_path = File.expand_path("../app/views/recording_studio_wordpress_plugin_template/home/index.html.erb", __dir__)

    refute File.exist?(view_path)
  end
end
