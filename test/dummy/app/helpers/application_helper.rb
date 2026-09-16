module ApplicationHelper
  # Single registry for signed-in host chrome. Keep labels everyday (not gem jargon).
  DummyHostNavItem = Data.define(:key, :text, :icon)

  DUMMY_HOST_NAV = [
    DummyHostNavItem.new(key: :home, text: "Home", icon: :home),
    DummyHostNavItem.new(key: :recordings_tree, text: "Recordings tree", icon: :folder),
    DummyHostNavItem.new(key: :api_keys, text: "API Keys", icon: :lock),
    DummyHostNavItem.new(key: :pages, text: "Pages", icon: :document_text)
  ].freeze

  def dummy_host_nav_items
    DUMMY_HOST_NAV.map do |item|
      href = dummy_host_nav_href(item.key)
      {
        key: item.key,
        text: item.text,
        icon: item.icon,
        href: href,
        active: dummy_host_nav_active?(item.key, href)
      }
    end
  end

  def dummy_page_nav(title:, back_url: nil, back_label: "Home")
    content_for :title, title
  end

  private

  def dummy_host_nav_href(key)
    case key
    when :home
      main_app.root_path
    when :recordings_tree
      main_app.docs_recordings_tree_path
    when :api_keys
      recording_studio_api.api_clients_path
    when :pages
      main_app.pages_path
    else
      raise ArgumentError, "unknown nav key: #{key}"
    end
  end

  def dummy_host_nav_active?(key, href)
    case key
    when :home
      current_page?(main_app.root_path)
    when :recordings_tree
      current_page?(main_app.docs_recordings_tree_path)
    when :api_keys
      request.path.start_with?(recording_studio_api.api_clients_path)
    when :pages
      request.path.start_with?("/pages")
    else
      current_page?(href)
    end
  end
end
