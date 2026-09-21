# frozen_string_literal: true

require "test_helper"

class DummySeedOauthTest < ActiveSupport::TestCase
  test "seeds load admin root and studio access without error" do
    load Rails.root.join("db/seeds.rb").to_s

    user = User.find_by!(email: "admin@admin.com")
    admin_root = AdminRoot.find_by!(name: "Admin")
    assert_predicate admin_root, :present?

    admin_recording = RecordingStudio.root_recording_for(admin_root)
    access = RecordingStudioAccessible.access_recordings_for_actor(
      recording: admin_recording,
      actor: user
    )
    assert_predicate access, :any?, "expected admin access on Admin root"

    workspace = Workspace.find_by!(name: "Studio Workspace")
    studio_root = RecordingStudio.root_recording_for(workspace)
    studio_access = RecordingStudioAccessible.access_recordings_for_actor(
      recording: studio_root,
      actor: user
    )
    assert_predicate studio_access, :any?

    client = RecordingStudioOauth::OauthClient.find_by(name: "Seed Demo App")
    assert_predicate client, :present?

    assert_nil RecordingStudioOauth::OauthClient.find_by(name: "WordPress")
    assert_nil RecordingStudioOauth::OauthClient.find_by(name: "WordPress Plugin Demo")
    assert_nil RecordingStudioOauth::OauthClient.find_by(client_id: WpPluginDemo::Seed::CONNECT_CLIENT_ID)

    refute RecordingStudio.capability_enabled?(:accessible, for: Folder)
    folder = Folder.find_by!(name: "Product Docs")
    folder_recording = RecordingStudio::Recording.find_by!(recordable: folder)
    folder_access = RecordingStudioAccessible.access_recordings_for_actor(
      recording: folder_recording,
      actor: user
    )
    assert_empty folder_access
  end
end
