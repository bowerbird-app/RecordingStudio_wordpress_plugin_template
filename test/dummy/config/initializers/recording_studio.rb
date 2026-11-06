# frozen_string_literal: true

RecordingStudio.configure do |config|
  # Registered delegated_type recordables (strings or classes)
  config.recordable_types = [
    "Workspace",
    "Folder",
    "Page",
    "AdminRoot",
    "RecordingStudioUser::People",
    "RecordingStudioUser::Profile",
    "RecordingStudioEmbeddable::Embed",
    # Publishable + Attachable gem types must be listed for boot even though this
    # host never enables those mixins on Page (Embeddable depends on Publishable;
    # Publishable::Publishable includes Attachable).
    "RecordingStudioPublishable::Publishable",
    "RecordingStudioAttachable::Attachment",
    "RecordingStudioSiteSettings::SiteSetting",
    "RecordingStudio::Access",
    "RecordingStudio::AccessConstraint",
    "RecordingStudio::AccessRequest",
    "RecordingStudio::AccessRule",
    "RecordingStudioApi::ApiClient",
    "RecordingStudioApi::ApiCredential",
    "RecordingStudioApi::ApiAccessToken",
    "RecordingStudioApi::AdminApi"
  ]

  # Require each configured ActiveRecord type to call recording_studio_recordable.
  config.require_recordable_declarations = true

  # Shown in the shared default layout title fallback.
  config.app_name = "WordPress Plugin Demo" if config.respond_to?(:app_name=)

  # Actor resolver for events when no actor is explicitly supplied
  config.actor = -> { Current.actor }

  # Hosts must set authorize_write before Recording Studio core 6.5.0.
  # Writes fail closed: missing authorize_write raises AuthorizationError;
  # missing actor raises ActorRequired. `actor.present?` is not a write policy.
  # Access grant/bootstrap writes go through Accessible's AccessCreationContext
  # (same path as grant_access / bootstrap_owner_access!), not a logged-in check.
  config.authorize_write = lambda { |actor:, root_recording:, recordable:, **|
    next false unless actor.respond_to?(:persisted?) && actor.persisted?
    next false unless RecordingStudioAccessible.configuration.allowed_access_actor_type?(actor)
    next false unless root_recording

    if defined?(RecordingStudio::Access) && recordable.is_a?(RecordingStudio::Access)
      next true if RecordingStudioAccessible::AccessCreationContext.allowed?

      RecordingStudioAccessible.authorized?(
        actor: actor,
        recording: root_recording,
        role: :admin
      )
    else
      RecordingStudioAccessible.authorized?(
        actor: actor,
        recording: root_recording,
        role: :edit
      )
    end
  }

  # Emit ActiveSupport::Notifications events
  config.event_notifications_enabled = true

  # Idempotency behavior for log_event!
  config.idempotency_mode = :return_existing # or :raise

  # Recordable duplication strategy for revisions
  config.recordable_dup_strategy = :dup
end
