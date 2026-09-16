# frozen_string_literal: true

# Root Switchable enhances tailwindcss:build with link_tailwind_sources, but that
# enhance is skipped when its rake file loads before tailwindcss-rails defines the
# task. Re-declare the dependency here so Cloud Agent / CI builds always link first.
if Rake::Task.task_defined?("tailwindcss:build")
  Rake::Task["tailwindcss:build"].enhance(["recording_studio_root_switchable:link_tailwind_sources"])
end

if Rake::Task.task_defined?("tailwindcss:watch")
  Rake::Task["tailwindcss:watch"].enhance(["recording_studio_root_switchable:link_tailwind_sources"])
end
