# frozen_string_literal: true

find_or_record_child = lambda do |recordable, root_recording, parent_recording|
  RecordingStudio::Recording.find_by(
    root_recording: root_recording,
    parent_recording: parent_recording,
    recordable: recordable,
    trashed_at: nil
  ) || RecordingStudio.record!(
    action: "created",
    recordable: recordable,
    root_recording: root_recording,
    parent_recording: parent_recording
  ).recording
end

grant_or_find_access = lambda do |recording, actor, role|
  existing = RecordingStudioAccessible.access_recordings_for_actor(
    recording: recording,
    actor: actor
  ).first
  return existing if existing.present?

  result = RecordingStudioAccessible.grant_access(
    recording: recording,
    actor: actor,
    role: role,
    manager_actor: actor
  )
  return result.value if result.success?

  bootstrap = RecordingStudioAccessible.bootstrap_owner_access!(
    recording: recording,
    actor: actor
  )
  raise bootstrap.error if bootstrap.failure?

  bootstrap.value
end

user = User.find_or_initialize_by(email: "admin@admin.com")
if user.new_record?
  user.password = "Password"
  user.password_confirmation = "Password"
end
user.save! if user.new_record? || user.changed?

if RecordingStudioUser.profile_for(user).nil?
  RecordingStudioUser.record_profile!(
    user,
    first_name: "Avery",
    last_name: "Admin",
    time_zone: "UTC",
    actor: user
  )
end

workspace = Workspace.find_or_create_by!(name: "Studio Workspace")
accessible_workspace = Workspace.find_or_create_by!(name: "Client Workspace")
private_workspace = Workspace.find_or_create_by!(name: "Private Workspace")
folder = Folder.find_or_create_by!(name: "Product Docs")
page = Page.find_or_create_by!(title: "Getting Started")
admin_root = AdminRoot.find_or_create_by!(name: "Admin")

oauth_client = RecordingStudioOauth::OauthClient.find_or_initialize_by(name: "Seed Demo App")
oauth_client.redirect_uris = ["http://127.0.0.1:3000/callback"]
oauth_client.confidential = false
oauth_client.api_key = "wp_plugin_demo"
oauth_client.save!

previous_actor = Current.actor
Current.actor = user

begin
  root_recording = RecordingStudio.root_recording_for(workspace)
  accessible_root_recording = RecordingStudio.root_recording_for(accessible_workspace)
  private_root_recording = RecordingStudio.root_recording_for(private_workspace)
  admin_recording = RecordingStudio.root_recording_for(admin_root)

  grant_or_find_access.call(root_recording, user, :admin)
  grant_or_find_access.call(accessible_root_recording, user, :admin)
  grant_or_find_access.call(admin_recording, user, :admin)

  folder_recording = find_or_record_child.call(folder, root_recording, root_recording)
  find_or_record_child.call(page, root_recording, folder_recording)

  unless RecordingStudioSiteSettings.name_for(root_recording) == "Studio"
    RecordingStudioSiteSettings.update!(root_recording, name: "Studio", actor: user)
  end
ensure
  Current.actor = previous_actor
end

WpPluginDemo::Seed.ensure_studio_embed!

puts "Seeded: admin@admin.com / Password"
puts "Seeded: Avery Admin profile under the shared People root"
puts "Seeded: Workspace '#{workspace.name}' with root recording ##{root_recording.id}"
puts "Seeded: Workspace '#{accessible_workspace.name}' with root recording ##{accessible_root_recording.id}"
puts "Seeded: Workspace '#{private_workspace.name}' with root recording ##{private_root_recording.id}"
puts "Seeded: Folder '#{folder.name}' and page '#{page.title}'"
puts "Seeded: Admin root for Oauth Admin (Registered apps)"
puts "Seeded: Seed Demo App client_id=#{oauth_client.client_id}"
puts "Seeded: Getting Started embed ready for the WordPress plugin demo"
