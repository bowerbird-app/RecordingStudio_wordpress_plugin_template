# frozen_string_literal: true

module WpPluginDemo
  module Seed
    STUDIO_WORKSPACE_NAME = "Studio Workspace"
    GETTING_STARTED_TITLE = "Getting Started"
    ADMIN_EMAIL = "admin@admin.com"
    RUNBOOK_CLIENT_NAME = "WordPress Plugin Demo runbook"
    CONNECT_CLIENT_NAME = "WordPress"
    LEGACY_CONNECT_CLIENT_NAME = "WordPress Plugin Demo"
    CONNECT_CLIENT_ID = "rsoauth_id_wordpress"

    GETTING_STARTED_LEAD =
      "Connect the WordPress Plugin Demo block to this host and paste this page's id."

    GENERIC_PAGE_BODY_HTML = <<~HTML.squish
      <p>This page is part of the WordPress Plugin Demo host. Use a seeded Getting Started page recording id for the runbook block.</p>
    HTML

    RunbookConnection = Data.define(
      :host_base_url,
      :oauth_client_id,
      :oauth_client_secret,
      :page_recording_id,
      :token_path,
      :embed_path
    )

    ConnectClient = Data.define(
      :host_base_url,
      :connect_client_id,
      :connect_url,
      :token_url,
      :relay_redirect_uri,
      :return_to,
      :page_recording_id
    )

    module_function

    def connect_redirect_uris(host_base_url: "http://localhost:3000")
      hosts = [host_base_url, host_base_url.to_s.sub("localhost", "127.0.0.1")].uniq
      hosts.map { |host| RecordingStudioOauth.central_relay_callback_url(base_url: host) }
    end

    def example_return_to
      "http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_callback"
    end

    def embed_body_html_for(_page)
      GENERIC_PAGE_BODY_HTML
    end

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

      build_runbook_connection(
        host_base_url: host_base_url,
        oauth_client_id: oauth_client_id,
        oauth_client_secret: oauth_client_secret,
        page_recording_id: page_recording.id
      )
    end

    def present_runbook_connection(host_base_url:, oauth_client_secret: nil)
      page_recording = ensure_studio_embed!
      credential = latest_runbook_credential

      build_runbook_connection(
        host_base_url: host_base_url,
        oauth_client_id: credential&.token_public_id,
        oauth_client_secret: oauth_client_secret,
        page_recording_id: page_recording.id
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

    def ensure_connect_client!
      client = RecordingStudioOauth::OauthClient.find_by(name: CONNECT_CLIENT_NAME)
      client ||= RecordingStudioOauth::OauthClient.find_by(name: LEGACY_CONNECT_CLIENT_NAME)
      client ||= RecordingStudioOauth::OauthClient.new(name: CONNECT_CLIENT_NAME)
      client.name = CONNECT_CLIENT_NAME
      client.use_central_relay = true
      client.redirect_uris = connect_redirect_uris
      client.allowed_return_patterns = [
        "https://*/wp-admin/admin-post.php?action=recording_studio_oauth_callback",
        "http://*/wp-admin/admin-post.php?action=recording_studio_oauth_callback"
      ]
      client.exact_return_urls = []
      client.confidential = false
      client.api_key = Contract::API_KEY.to_s
      client.client_id = CONNECT_CLIENT_ID if client.client_id.blank?
      client.save!
      client
    end

    def present_connect_client(host_base_url: "http://localhost:3000")
      page_recording = ensure_studio_embed!
      client = ensure_connect_client!
      host = host_base_url.to_s.strip.chomp("/")
      ConnectClient.new(
        host_base_url: host,
        connect_client_id: client.client_id,
        connect_url: RecordingStudioOauth.central_relay_connect_url(base_url: host),
        token_url: "#{host}#{Contract::CONNECT_TOKEN_PATH}",
        relay_redirect_uri: RecordingStudioOauth.central_relay_callback_url(base_url: host),
        return_to: example_return_to,
        page_recording_id: page_recording.id
      )
    end

    def print_connect_client!(host_base_url: "http://localhost:3000")
      connection = present_connect_client(host_base_url: host_base_url)
      puts "host_base_url=#{connection.host_base_url}"
      puts "connect_client_id=#{connection.connect_client_id}"
      puts "connect_url=#{connection.connect_url}"
      puts "token_url=#{connection.token_url}"
      puts "relay_redirect_uri=#{connection.relay_redirect_uri}"
      puts "return_to=#{connection.return_to}"
      puts "page_recording_id=#{connection.page_recording_id}"
      connection
    end

    def latest_runbook_credential
      client = RecordingStudioApi::ApiClient
        .where(name: RUNBOOK_CLIENT_NAME, api_key: Contract::API_KEY.to_s)
        .order(created_at: :desc, id: :desc)
        .first
      return if client.nil?

      client.credentials.active.order(created_at: :desc, id: :desc).first
    end
    private_class_method :latest_runbook_credential

    def build_runbook_connection(host_base_url:, oauth_client_id:, oauth_client_secret:, page_recording_id:)
      host = host_base_url.to_s.strip.chomp("/")
      RunbookConnection.new(
        host_base_url: host,
        oauth_client_id: oauth_client_id,
        oauth_client_secret: oauth_client_secret,
        page_recording_id: page_recording_id,
        token_path: Contract::TOKEN_PATH,
        embed_path: Contract.actions_embed_path(page_recording_id)
      )
    end
    private_class_method :build_runbook_connection
  end
end
