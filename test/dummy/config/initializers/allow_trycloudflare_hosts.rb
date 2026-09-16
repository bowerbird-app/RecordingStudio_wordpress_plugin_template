# frozen_string_literal: true

if Rails.env.local?
  Rails.application.config.hosts << ".trycloudflare.com"
end
