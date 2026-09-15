# frozen_string_literal: true

module WpPluginDemo
  module Seed
    STUDIO_WORKSPACE_NAME = "Studio Workspace"
    GETTING_STARTED_TITLE = "Getting Started"
    ADMIN_EMAIL = "admin@admin.com"

    module_function

    # Idempotent. No new User/Workspace. Safe under the existing seed-count test.
    def ensure_studio_embed!
      admin = User.find_by!(email: ADMIN_EMAIL)
      workspace = Workspace.find_by!(name: STUDIO_WORKSPACE_NAME)
      page = Page.find_by!(title: GETTING_STARTED_TITLE)

      root_recording = RecordingStudio.root_recording_for(workspace)
      page_recording = RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil)

      Tree.grant_admin!(root_recording: root_recording, actor: admin)
      Tree.ensure_embed_on!(page_recording, actor: admin)
    end
  end
end
