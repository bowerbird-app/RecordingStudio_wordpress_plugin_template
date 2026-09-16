# frozen_string_literal: true

if Rails.env.development?
  Rails.application.config.hosts << ".trycloudflare.com"
end
