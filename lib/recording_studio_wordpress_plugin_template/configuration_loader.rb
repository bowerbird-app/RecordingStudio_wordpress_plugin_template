# frozen_string_literal: true

module RecordingStudioWordpressPluginTemplate
  # Loads YAML and config.x overrides into the engine configuration.
  module ConfigurationLoader
    module_function

    def load_yaml(app)
      return unless app.respond_to?(:config_for)

      yaml = fetch_yaml(app)
      return unless yaml.respond_to?(:each)

      RecordingStudioWordpressPluginTemplate.configuration.merge!(yaml)
    rescue StandardError
      # Host apps can override via an initializer if YAML load fails.
    end

    def merge_x(app)
      xcfg = x_config(app)
      return unless xcfg

      hash = to_hash(xcfg)
      RecordingStudioWordpressPluginTemplate.configuration.merge!(hash) if hash.any?
    rescue StandardError
      # Ignore OrderedOptions conversion failures.
    end

    def fetch_yaml(app)
      app.config_for(:recording_studio_wordpress_plugin_template)
    rescue StandardError
      nil
    end
    private_class_method :fetch_yaml

    def x_config(app)
      return unless app.config.respond_to?(:x)
      return unless app.config.x.respond_to?(:recording_studio_wordpress_plugin_template)

      app.config.x.recording_studio_wordpress_plugin_template
    end
    private_class_method :x_config

    def to_hash(xcfg)
      return xcfg.to_h if xcfg.respond_to?(:to_h)

      hash = {}
      xcfg.each_pair { |key, value| hash[key] = value } if xcfg.respond_to?(:each_pair)
      hash
    end
    private_class_method :to_hash
  end
end
