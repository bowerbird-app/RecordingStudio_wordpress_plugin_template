# frozen_string_literal: true

require "test_helper"

class AuthorizeWriteTest < ActiveSupport::TestCase
  test "dummy authorize_write is configured and is not actor presence" do
    initializer = File.read(Rails.root.join("config/initializers/recording_studio.rb"))

    assert_includes initializer, "config.authorize_write"
    assert_includes initializer, "RecordingStudioAccessible.authorized?"
    assert_includes initializer, "AccessCreationContext.allowed?"
    refute_match(/authorize_write\s*=\s*->\(actor:, \*\*\) \{ actor\.present\? \}/, initializer)
    refute_includes initializer, "allow_unauthorized_writes = true"
    refute_includes initializer, "shared_root?"
  end

  test "dummy authorize_write requires Accessible edit on the root" do
    authorizer = RecordingStudio.configuration.authorize_write

    refute_nil authorizer

    actor = User.find_or_create_by!(email: "dummy-write-authorizer-#{SecureRandom.hex(4)}@example.com") do |record|
      record.password = "Password"
      record.password_confirmation = "Password"
    end
    workspace = Workspace.create!(name: "Write policy #{SecureRandom.hex(4)}")
    root_recording = RecordingStudio.root_recording_for(workspace)
    folder = Folder.new(name: "Write policy folder")

    refute authorizer.call(actor: nil, root_recording: root_recording, recordable: folder)
    refute authorizer.call(actor: actor, root_recording: root_recording, recordable: folder)

    result = RecordingStudioAccessible.bootstrap_owner_access!(
      recording: root_recording,
      actor: actor
    )
    raise result.error if result.respond_to?(:failure?) && result.failure?

    assert authorizer.call(actor: actor, root_recording: root_recording, recordable: folder)
  end

  test "dummy authorize_write denies a Profile snapshot without Accessible edit on People" do
    authorizer = RecordingStudio.configuration.authorize_write
    actor = User.find_or_create_by!(email: "dummy-profile-authorizer-#{SecureRandom.hex(4)}@example.com") do |record|
      record.password = "Password"
      record.password_confirmation = "Password"
    end
    people_root = RecordingStudioUser.people_root
    profile = RecordingStudioUser::Profile.new(user_id: actor.id, first_name: "Pat", last_name: "Profile")

    refute authorizer.call(actor: nil, root_recording: people_root, recordable: profile)
    refute authorizer.call(actor: actor, root_recording: people_root, recordable: profile)
  end

  test "dummy authorize_write allows Access grants during AccessCreationContext" do
    authorizer = RecordingStudio.configuration.authorize_write
    actor = User.find_or_create_by!(email: "dummy-access-authorizer-#{SecureRandom.hex(4)}@example.com") do |record|
      record.password = "Password"
      record.password_confirmation = "Password"
    end
    workspace = Workspace.create!(name: "Access write policy #{SecureRandom.hex(4)}")
    root_recording = RecordingStudio.root_recording_for(workspace)
    access = RecordingStudio::Access.new(role: "admin")

    refute authorizer.call(actor: actor, root_recording: root_recording, recordable: access)

    RecordingStudioAccessible::AccessCreationContext.allow do
      assert authorizer.call(actor: actor, root_recording: root_recording, recordable: access)
    end
  end
end
