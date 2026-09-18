# frozen_string_literal: true

module OauthConnectHelpers
  def pkce_pair
    verifier = "V#{SecureRandom.urlsafe_base64(32)}".ljust(43, "a")
    {
      verifier: verifier,
      challenge: RecordingStudioOauth::Pkce.s256_challenge(verifier)
    }
  end

  def studio_workspace_access_recording!(user)
    workspace = Workspace.find_by!(name: "Studio Workspace")
    root_recording = RecordingStudio.root_recording_for(workspace)
    access = RecordingStudioAccessible.access_recordings_for_actor(
      recording: root_recording,
      actor: user
    ).first
    return access if access.present?

    result = RecordingStudioAccessible.grant_access(
      recording: root_recording,
      actor: user,
      role: :admin,
      manager_actor: user
    )
    raise result.error if result.failure?

    result.value
  end

  def approve_delegated_oauth(oauth_client:, user:, access_recording:, redirect_uri:, pkce:, role: "view")
    result = RecordingStudioOauth::Services::CreateOauthAuthorization.call(
      oauth_client: oauth_client,
      manager_actor: user,
      access_recording: access_recording,
      role: role,
      redirect_uri: redirect_uri,
      code_challenge: pkce.fetch(:challenge),
      code_challenge_method: "S256"
    )
    raise result.error unless result.success?

    result.value.merge(pkce: pkce, redirect_uri: redirect_uri)
  end
end
