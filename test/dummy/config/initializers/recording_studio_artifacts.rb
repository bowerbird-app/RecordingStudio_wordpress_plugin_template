# frozen_string_literal: true

# Artifacts is an Embeddable hard dependency. Host-wide CDN usage stays off via
# RecordingStudioEmbeddable's default `config.artifacts_enabled = false`.
# MemoryStorage keeps local/test boots from needing real R2 credentials.
RecordingStudioArtifacts.configure do |config|
  config.cdn_public_base_url =
    ENV.fetch("ARTIFACT_CDN_PUBLIC_BASE_URL", nil).presence ||
    "https://artifacts.example.test"
  config.cdn_path_prefix =
    ENV.fetch("ARTIFACT_CDN_PATH_PREFIX", nil).presence ||
    "recording_studio_artifacts"
  config.cdn_publish_queue = :default

  if Rails.env.test? || Rails.env.development?
    storage = RecordingStudioArtifacts::Cdn::MemoryStorage.new
    config.cdn_storage = storage
    config.cdn_purger = storage
  end
end
