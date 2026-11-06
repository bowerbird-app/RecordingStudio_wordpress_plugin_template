# frozen_string_literal: true

require "test_helper"

class UsersProfileSeedTest < ActiveSupport::TestCase
  test "seed records Avery Admin profile with owner access and leaves People unowned" do
    load Rails.root.join("db/seeds.rb").to_s

    user = User.find_by!(email: "admin@admin.com")
    profile = RecordingStudioUser.profile_for(user)
    profile_recording = RecordingStudioUser.profile_recording_for(user)
    people_root = RecordingStudioUser.people_root

    assert_equal "Avery", profile.first_name
    assert_equal "Admin", profile.last_name
    assert_equal "UTC", profile.time_zone
    assert RecordingStudioAccessible.authorized?(
      actor: user,
      recording: profile_recording,
      role: :admin
    )
    refute RecordingStudioAccessible.authorized?(
      actor: user,
      recording: people_root,
      role: :view
    )
    refute RecordingStudio.capability_enabled?(:accessible, for: "RecordingStudioUser::People")
    refute_includes RecordingStudio.configuration.recordable_types, "User"
  end

  test "record_first_profile! uses Users record_profile! and bootstraps Accessible on the Profile" do
    actor = User.find_or_create_by!(email: "dummy-first-profile-#{SecureRandom.hex(4)}@example.com") do |record|
      record.password = "Password"
      record.password_confirmation = "Password"
    end
    people_root = RecordingStudioUser.people_root

    recording = WpPluginDemo::Seed.record_first_profile!(
      actor,
      first_name: "Pat",
      last_name: "Profile",
      time_zone: "UTC"
    )

    assert RecordingStudioAccessible.authorized?(actor: actor, recording: recording, role: :admin)
    refute RecordingStudioAccessible.authorized?(actor: actor, recording: people_root, role: :view)
    refute_nil RecordingStudio.configuration.authorize_write
    refute RecordingStudio.configuration.allow_unauthorized_writes
  end
end
