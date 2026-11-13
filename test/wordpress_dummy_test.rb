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
  end

  def test_readme_lists_the_wordpress_dummy_steps
    readme = File.read(File.expand_path("../README.md", __dir__))
    dummy_readme = File.read(File.expand_path("../wordpress/dummy/README.md", __dir__))

    assert_includes readme, "bin/wordpress-dummy"
    assert_includes readme, "npm run env start"
    assert_includes readme, "Start the Rails dummy"
    assert_includes readme, "Start WordPress"
    assert_includes readme, "Connect"
    assert_includes readme, "Insert the block"
    assert_includes readme, "View the front end"
    assert_includes readme, "host.docker.internal"
    assert_includes readme, "RECORDING_STUDIO_HOST_BASE_URL"
    assert_includes readme, "RECORDING_STUDIO_CLIENT_ID"
    assert_includes readme, "rsoauth_id_wordpress"
    assert_includes readme, "dev-admin@example.test"
    assert_includes readme, "http://localhost:8888/studio-widget/"
    assert_includes readme, STAND_IN_PAGE_RECORDING_ID
    development_rb = File.read(File.expand_path("dummy/config/environments/development.rb", __dir__))
    assert_includes development_rb, 'config.hosts << "host.docker.internal"'
    assert_includes dummy_readme, "admin"
    assert_includes dummy_readme, "password"
    assert_includes dummy_readme, "dev-admin@example.test"
  end

  def test_ci_boots_the_wordpress_dummy_and_checks_the_rendered_block
    workflow = File.read(File.expand_path("../.github/workflows/ci.yml", __dir__))

    assert_includes workflow, "wordpress-dummy:"
    assert_includes workflow, "bin/wordpress-dummy"
    assert_includes workflow, "bin/wordpress-dummy-smoke"
  end

  def plugin_path(name)
    File.expand_path("../wordpress/recording-studio-widgets/#{name}", __dir__)
  end
end
