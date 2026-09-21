# frozen_string_literal: true

module UsersAuthPrimaryButtons
  extend ActiveSupport::Concern

  included do
    before_action :link_users_auth_primary_buttons
  end

  private

  def link_users_auth_primary_buttons
    return if @users_auth_primary_buttons_linked

    @users_auth_primary_buttons_linked = true
    view_context.content_for(
      :head,
      view_context.stylesheet_link_tag("users_auth_primary_buttons", "data-turbo-track": "reload")
    )
  end
end

Rails.application.config.to_prepare do
  next unless defined?(RecordingStudioUser::Auth::BaseController)
  next if RecordingStudioUser::Auth::BaseController.include?(UsersAuthPrimaryButtons)

  RecordingStudioUser::Auth::BaseController.include(UsersAuthPrimaryButtons)
end
