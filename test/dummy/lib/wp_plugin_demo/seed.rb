# frozen_string_literal: true

module WpPluginDemo
  module Seed
    STUDIO_WORKSPACE_NAME = "Studio Workspace"
    GETTING_STARTED_TITLE = "Getting Started"
    ADMIN_EMAIL = "admin@admin.com"
    RUNBOOK_CLIENT_NAME = "WordPress Plugin Demo runbook"

    RunbookConnection = Data.define(
      :host_base_url,
      :oauth_client_id,
      :oauth_client_secret,
      :page_recording_id,
      :token_path,
      :embed_path
    )

    module_function

    def ensure_studio_embed!
      admin = User.find_by!(email: ADMIN_EMAIL)
      workspace = Workspace.find_by!(name: STUDIO_WORKSPACE_NAME)
      page = Page.find_by!(title: GETTING_STARTED_TITLE)

      root_recording = RecordingStudio.root_recording_for(workspace)
      page_recording = RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil)

      Tree.grant_admin!(root_recording: root_recording, actor: admin)
      Tree.ensure_embed_on!(page_recording, actor: admin)
      page_recording
    end

    def getting_started_page_recording_id
      page = Page.find_by!(title: GETTING_STARTED_TITLE)
      RecordingStudio::Recording.find_by!(recordable: page, trashed_at: nil).id
    end

    def issue_runbook_connection!(host_base_url: "http://localhost:3000")
      page_recording = ensure_studio_embed!
      admin = User.find_by!(email: ADMIN_EMAIL)
      workspace = Workspace.find_by!(name: STUDIO_WORKSPACE_NAME)
      root_recording = RecordingStudio.root_recording_for(workspace)

      oauth_client_id, oauth_client_secret = Provision.issue_named_credentials!(
        access_point_recording: root_recording,
        manager_actor: admin,
        name: RUNBOOK_CLIENT_NAME
      )

      host = host_base_url.to_s.strip.chomp("/")
      RunbookConnection.new(
        host_base_url: host,
        oauth_client_id: oauth_client_id,
        oauth_client_secret: oauth_client_secret,
        page_recording_id: page_recording.id,
        token_path: Contract::TOKEN_PATH,
        embed_path: Contract.actions_embed_path(page_recording.id)
      )
    end

    def print_runbook_connection!(host_base_url: "http://localhost:3000")
      connection = issue_runbook_connection!(host_base_url: host_base_url)
      puts "host_base_url=#{connection.host_base_url}"
      puts "oauth_client_id=#{connection.oauth_client_id}"
      puts "oauth_client_secret=#{connection.oauth_client_secret}"
      puts "page_recording_id=#{connection.page_recording_id}"
      puts "token_url=#{connection.host_base_url}#{connection.token_path}"
      puts "embed_url=#{connection.host_base_url}#{connection.embed_path}"
      connection
    end
  end
end
