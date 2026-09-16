# frozen_string_literal: true

class PluginCredentialsController < ApplicationController
  def show
    @connection = WpPluginDemo::Seed.present_runbook_connection(
      host_base_url: request.base_url,
      oauth_client_secret: flash[:oauth_client_secret]
    )
  end

  def create
    connection = WpPluginDemo::Seed.issue_runbook_connection!(host_base_url: request.base_url)
    flash[:oauth_client_secret] = connection.oauth_client_secret
    flash[:notice] = "Credentials ready. Copy the secret now. It will not show again."
    redirect_to plugin_credentials_path
  end
end
