Rails.application.routes.draw do
  devise_for :users

  # RecordingStudio engine is data/API-focused and has no browser root route.
  # Keep legacy links working by redirecting the base path to the app home.
  get "/recording_studio", to: redirect("/"), as: nil
  mount RecordingStudio::Engine, at: "/recording_studio"
  mount RecordingStudioAccessible::Engine, at: "/recording_studio_accessible"
  mount RecordingStudioAccessible::Engine, at: "/admin/access", as: :recording_studio_admin_access
  mount RecordingStudioRootSwitchable::Engine, at: "/recording_studio_root_switchable"
  mount RecordingStudioApi::Engine, at: "/recording_studio_api"
  mount RecordingStudioEmbeddable::Engine, at: "/recording_studio_embeddable"
  mount RecordingStudioOauth::Engine, at: "/recording_studio_oauth"
  mount RecordingStudioAttachable::Engine, at: "/recording_studio_attachable"
  mount RecordingStudioSiteSettings::Engine, at: "/recording_studio_site_settings"

  get "/.well-known/oauth-authorization-server",
      to: "recording_studio_oauth/oauth_discoveries#authorization_server",
      defaults: { api_key: "public" }
  RecordingStudioOauth::ProtectedResourceRegistry.draw_origin_well_known(self)

  recording_studio_admin_for :admin, at: "/admin", root_section: :root

  # Reveal health status on /up that returns 200 if the app boots with no exceptions, otherwise 500.
  # Can be used by load balancers and uptime monitors to verify that the app is live.
  get "up" => "rails/health#show", as: :rails_health_check

  get "docs/install", to: "docs#install", as: :docs_install
  get "docs/config", to: "docs#configuration", as: :docs_config
  get "docs/recordable_types", to: "docs#recordable_types", as: :docs_recordable_types
  get "docs/recordings_tree", to: "docs#recordings_tree", as: :docs_recordings_tree
  get "docs/gem_views", to: "docs#gem_views", as: :docs_gem_views
  get "docs/methods", to: "docs#methods", as: :docs_methods

  root "home#index"
end
