# frozen_string_literal: true

module OauthAdminHelpers
  REGISTERED_APPS_PATH = "/admin/screens/oauth_clients"

  def switch_to_admin_root!
    admin_root = AdminRoot.find_by!(name: "Admin")
    admin_recording = RecordingStudio.root_recording_for(admin_root)

    patch recording_studio_root_switchable.root_switch_path(scope: "all_workspaces"), params: {
      root_switch: {
        root_recording_id: admin_recording.id,
        return_to: "/"
      }
    }
    follow_redirect! if response.redirect?
  end
end
