class User < ApplicationRecord
  # devise_for maps omniauth_callbacks; Devise requires this module even with empty providers.
  devise :database_authenticatable, :registerable,
         :recoverable, :rememberable, :validatable, :omniauthable
end
