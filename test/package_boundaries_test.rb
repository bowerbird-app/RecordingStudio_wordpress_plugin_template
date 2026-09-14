# frozen_string_literal: true

require "test_helper"
require_relative "../bin/package_boundaries"

class PackageBoundariesTest < Minitest::Test
  def test_gem_allowlist_keeps_engine_files_and_rejects_dummy_and_wordpress
    assert PackageBoundaries.gem_path_allowed?("lib/recording_studio_wordpress_plugin_template.rb")
    assert PackageBoundaries.gem_path_allowed?("app/controllers/recording_studio_wordpress_plugin_template/home_controller.rb")
    assert PackageBoundaries.gem_path_allowed?("README.md")
    refute PackageBoundaries.gem_path_allowed?("test/dummy/config/application.rb")
    refute PackageBoundaries.gem_path_allowed?("wordpress/recording-studio-widgets/recording-studio-widget.php")
    refute PackageBoundaries.gem_path_allowed?("lib/widget.php")
    refute PackageBoundaries.gem_path_allowed?("coverage/.resultset.json")
  end

  def test_zip_allowlist_keeps_installable_plugin_files_only
    assert PackageBoundaries.zip_path_allowed?("recording-studio-widget.php")
    assert PackageBoundaries.zip_path_allowed?("recording-studio-widgets/readme.txt")
    assert PackageBoundaries.zip_path_allowed?("includes/placeholder.php")
    assert PackageBoundaries.zip_path_allowed?("build/recording-studio-widget/render.php")
    refute PackageBoundaries.zip_path_allowed?("src/recording-studio-widget/edit.js")
    refute PackageBoundaries.zip_path_allowed?("tests/php/placeholder-text.php")
    refute PackageBoundaries.zip_path_allowed?("package.json")
    refute PackageBoundaries.zip_path_allowed?(".wp-env.json")
    refute PackageBoundaries.zip_path_allowed?("Gemfile")
    refute PackageBoundaries.zip_path_allowed?("test/dummy/config/application.rb")
    refute PackageBoundaries.zip_path_allowed?("lib/recording_studio_wordpress_plugin_template.rb")
  end

  def test_violations_fail_when_dummy_host_code_appears
    gem_violations = PackageBoundaries.violations_for(
      :gem,
      paths: ["test/dummy/config/application.rb"],
      contents: { "test/dummy/config/application.rb" => "module Dummy\n  class Application < Rails::Application\n" }
    )
    zip_violations = PackageBoundaries.violations_for(
      :zip,
      paths: ["test/dummy/app/views/home/index.html.erb"],
      contents: { "test/dummy/app/views/home/index.html.erb" => "<% dummy_page_nav(title: \"Host\") %>" }
    )

    refute_empty gem_violations
    assert gem_violations.any? { |line| line.include?("dummy host") }
    refute_empty zip_violations
    assert zip_violations.any? { |line| line.include?("dummy host") }
  end

  def test_clean_package_lists_have_no_violations
    gem_paths = %w[
      lib/recording_studio_wordpress_plugin_template.rb
      README.md
      MIT-LICENSE
      Rakefile
    ]
    zip_paths = %w[
      recording-studio-widgets/recording-studio-widget.php
      recording-studio-widgets/readme.txt
      recording-studio-widgets/includes/placeholder.php
      recording-studio-widgets/build/recording-studio-widget/render.php
    ]

    assert_empty PackageBoundaries.violations_for(:gem, paths: gem_paths, contents: {})
    assert_empty PackageBoundaries.violations_for(:zip, paths: zip_paths, contents: {})
  end

  def test_gemspec_file_list_stays_inside_the_engine_allowlist
    spec = Gem::Specification.load(
      File.expand_path("../recording_studio_wordpress_plugin_template.gemspec", __dir__)
    )

    refute_empty spec.files
    spec.files.each do |path|
      assert PackageBoundaries.gem_path_allowed?(path), "gemspec unexpectedly packages #{path}"
    end
    refute spec.files.any? { |path| path.start_with?("wordpress/") }
    refute spec.files.any? { |path| path.include?("test/dummy") }
    refute spec.files.any? { |path| path.start_with?("test/") }
  end

  def test_wordpress_zip_entries_stay_inside_the_plugin_allowlist
    entries = PackageBoundaries.wordpress_zip_entries

    refute_empty entries
    entries.each_key do |zip_path|
      assert PackageBoundaries.zip_path_allowed?(zip_path), "zip allowlist unexpectedly includes #{zip_path}"
    end
    refute entries.keys.any? { |path| path.include?("test/dummy") }
    refute entries.keys.any? { |path| path.end_with?(".rb") }
  end
end
