class User < ApplicationRecord
  # :omniauthable is required for devise_for(omniauth_callbacks:) even when
  # RecordingStudioUser.config.omniauth_providers stays empty on this host.
  devise :database_authenticatable, :registerable,
         :recoverable, :rememberable, :validatable, :omniauthable
end
