# frozen_string_literal: true

require_relative "lib/recording_studio_wordpress_plugin_template/version"

Gem::Specification.new do |spec|
  spec.name        = "recording_studio_wordpress_plugin_template"
  spec.version     = RecordingStudioWordpressPluginTemplate::VERSION
  spec.authors     = ["Bowerbird"]
  spec.homepage    = "https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template"
  spec.summary     = "RecordingStudio WordPress widgets Rails engine"
  spec.description = "Rails engine that hosts RecordingStudio WordPress widgets. " \
                     "The dummy app stays independent. The WordPress plugin is a separate package."
  spec.license     = "MIT"
  spec.required_ruby_version = ">= 3.3.0"

  spec.metadata["homepage_uri"] = spec.homepage
  spec.metadata["source_code_uri"] = spec.homepage
  spec.metadata["changelog_uri"] = "#{spec.homepage}/blob/main/CHANGELOG.md"
  spec.metadata["rubygems_mfa_required"] = "true"

  spec.files = Dir.chdir(File.expand_path(__dir__)) do
    Dir["{app,config,db,lib}/**/*", "MIT-LICENSE", "Rakefile", "README.md"].reject do |path|
      path == ".cursor" || path.start_with?(".cursor/")
    end
  end

  spec.add_dependency "rails", "~> 8.1.0"
  spec.add_dependency "recording_studio", "~> 4.2"
end
