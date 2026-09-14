# frozen_string_literal: true

require_relative "configuration_loader"

module RecordingStudioWordpressPluginTemplate
  class Engine < ::Rails::Engine
    isolate_namespace RecordingStudioWordpressPluginTemplate

    class << self
      def apply_model_extensions(target)
        apply_extensions(target, extensions_for(:model, extension_keys_for(target)))
      end

      def apply_controller_extensions(target)
        apply_extensions(target, extensions_for(:controller, extension_keys_for(target)))
      end

      private

      def extensions_for(kind, names)
        hooks = RecordingStudioWordpressPluginTemplate.configuration.hooks
        Array(names).flat_map do |name|
          if kind == :model
            hooks.model_extensions_for(name)
          else
            hooks.controller_extensions_for(name)
          end
        end
      end

      def apply_extensions(target, extensions)
        return unless target

        applied_key = :@recording_studio_wordpress_plugin_template_applied_extensions
        applied = target.instance_variable_get(applied_key) || identity_hash

        extensions.flatten.compact.each do |extension|
          next if applied[extension]

          target.class_eval(&extension)
          applied[extension] = true
        end

        target.instance_variable_set(applied_key, applied)
      end

      def extension_keys_for(target)
        names = [target.name, target.name&.demodulize].compact.uniq
        names.map(&:to_sym)
      end

      def identity_hash
        {}.compare_by_identity
      end
    end

    initializer "recording_studio_wordpress_plugin_template.before_initialize",
                before: "recording_studio_wordpress_plugin_template.load_config" do |_app|
      RecordingStudioWordpressPluginTemplate.configuration.hooks.run(:before_initialize, self)
    end

    initializer "recording_studio_wordpress_plugin_template.load_config" do |app|
      ConfigurationLoader.load_yaml(app)
      ConfigurationLoader.merge_x(app)
      RecordingStudioWordpressPluginTemplate.configuration.hooks.run(
        :on_configuration,
        RecordingStudioWordpressPluginTemplate.configuration
      )
    end

    initializer "recording_studio_wordpress_plugin_template.after_initialize",
                after: "recording_studio_wordpress_plugin_template.load_config" do |_app|
      RecordingStudioWordpressPluginTemplate.configuration.hooks.run(:after_initialize, self)
    end

    initializer "recording_studio_wordpress_plugin_template.apply_model_extensions" do
      config.to_prepare do
        next unless defined?(ActiveRecord::Base)

        ActiveRecord::Base.descendants.each do |model|
          next if model.abstract_class?

          RecordingStudioWordpressPluginTemplate::Engine.apply_model_extensions(model)
        end
      end
    end

    initializer "recording_studio_wordpress_plugin_template.apply_controller_extensions" do
      config.to_prepare do
        next unless defined?(ActionController::Base)

        ActionController::Base.descendants.each do |controller|
          RecordingStudioWordpressPluginTemplate::Engine.apply_controller_extensions(controller)
        end
      end
    end
  end
end
