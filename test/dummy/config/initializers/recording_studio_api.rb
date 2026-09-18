# frozen_string_literal: true

require Rails.root.join("lib/wp_plugin_demo/contract")

RecordingStudioApi.configure do |config|
  # No Redis in this dummy. Set on the named API too — inheritance from public at
  # create time is a trap.
  config.rate_limit_api_pre_auth_enabled = false
  config.rate_limit_api_enabled = false
  config.rate_limit_oauth_enabled = false
  config.api_management_authorization_required = false
  config.admin_root_recordable_type_names = ["AdminRoot"]

  config.api WpPluginDemo::Contract::API_KEY do |api|
    api.openapi_title = WpPluginDemo::Contract::OPENAPI_TITLE
    api.openapi_description =
      "Named API for WordPress Plugin Demo clients. Soft GET :embed returns BrowserPayload schema v1."
    api.api_management_authorization_required = false
    api.rate_limit_api_pre_auth_enabled = false
    api.rate_limit_api_enabled = false
    api.rate_limit_oauth_enabled = false
  end
end

RecordingStudioApi.register_recordable_type_api(
  "Workspace",
  api: WpPluginDemo::Contract::API_KEY,
  operations: %i[show],
  serializer: ->(workspace, **) { { name: workspace.name } },
  output_keys: %i[name]
)

RecordingStudioApi.register_recordable_type_api(
  "Page",
  api: WpPluginDemo::Contract::API_KEY,
  operations: %i[index show],
  capability_actions: %i[embed], # allowlist only — no host handler
  serializer: ->(page, **) { { title: page.title } },
  output_keys: %i[title]
)

# Soft-register ran before this file. Re-enter gem registration for the new API.
RecordingStudioApi.register_default_resource_actions!(api: WpPluginDemo::Contract::API_KEY)
RecordingStudioEmbeddable::Api.register_capability_action!
