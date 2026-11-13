# frozen_string_literal: true

require "test_helper"

class WordpressDummyTest < Minitest::Test
  STAND_IN_PAGE_RECORDING_ID = "11111111-1111-4111-8111-111111111111"

  def test_wp_env_mounts_the_plugin_and_points_at_the_rails_dummy
    env = JSON.parse(File.read(plugin_path(".wp-env.json")))

    assert_equal ["."], env.fetch("plugins")
    assert_equal 8888, env.fetch("port")
    assert_equal 8889, env.fetch("testsPort")
    assert_equal false, env.fetch("testsEnvironment")
    assert_equal "https://wordpress.org/wordpress-7.1.4.zip", env.fetch("core")
    assert_equal "8.3", env.fetch("phpVersion")
    assert_equal "http://host.docker.internal:3000", env.dig("config", "RECORDING_STUDIO_HOST_BASE_URL")
    assert_equal "rsoauth_id_wordpress", env.dig("config", "RECORDING_STUDIO_CLIENT_ID")
    assert_includes env.dig("lifecycleScripts", "afterStart"), "dummy/bin/seed.sh"
  end

  def test_seed_creates_the_dev_admin_and_the_sample_block
    seed = File.read(File.expand_path("../wordpress/dummy/bin/seed.sh", __dir__))

    assert_includes seed, "user_pass=password"
    assert_includes seed, "dev-admin@example.test"
    assert_includes seed, "recording-studio-widgets"
    assert_includes seed, "studio-widget"
    assert_includes seed, "recording-studio/recording-studio-widget"
    assert_includes seed, STAND_IN_PAGE_RECORDING_ID
    assert_includes seed, "show_on_front"
    assert_includes seed, "button-contrast"
    assert_includes seed, "fp-button"
  end

  def test_development_allows_the_docker_host
    development_rb = File.read(File.expand_path("dummy/config/environments/development.rb", __dir__))
    assert_includes development_rb, 'config.hosts << "host.docker.internal"'
  end

  def test_ci_boots_the_wordpress_dummy_and_checks_the_rendered_block
    workflow = File.read(File.expand_path("../.github/workflows/ci.yml", __dir__))

    assert_includes workflow, "wordpress-dummy:"
    assert_includes workflow, "bin/wordpress-dummy"
    assert_includes workflow, "bin/wordpress-dummy-smoke"
    refute_includes workflow, "WORDPRESS_DUMMY_CHECK_RAILS"
  end

  def test_smoke_cleans_temp_files_and_checks_button_contrast
    smoke = File.read(File.expand_path("../bin/wordpress-dummy-smoke", __dir__))

    assert_includes smoke, "trap 'rm -f \"$body\" \"$body.home\"' EXIT"
    assert_includes smoke, "WORDPRESS_DUMMY_CHECK_RAILS"
    assert_includes smoke, "wordpress-dummy-contrast.mjs"
    assert_includes smoke, "button-contrast"
    assert_includes smoke, "restore_block_page"
    assert_includes smoke, '"$page_url"'
    assert_includes File.read(File.expand_path("../bin/wordpress-dummy-contrast.mjs", __dir__)), "4.5"
    package = JSON.parse(File.read(plugin_path("package.json")))
    assert_match(/\A\d+\.\d+\.\d+\z/, package.dig("devDependencies", "playwright-core").to_s)
    refute_match(/^WORDPRESS_DUMMY_CHECK_RAILS=1/m, smoke)
  end

  def plugin_path(name)
    File.expand_path("../wordpress/recording-studio-widgets/#{name}", __dir__)
  end
end
