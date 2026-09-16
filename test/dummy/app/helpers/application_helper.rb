module ApplicationHelper
  # Single registry for signed-in host chrome. Keep labels everyday (not gem jargon).
  DummyHostNavItem = Data.define(:key, :text, :icon, :href_name)

  DUMMY_HOST_NAV = [
    DummyHostNavItem.new(key: :home, text: "Home", icon: :home, href_name: :root_path),
    DummyHostNavItem.new(
      key: :recordings_tree,
      text: "Recordings tree",
      icon: :folder,
      href_name: :docs_recordings_tree_path
    ),
    DummyHostNavItem.new(
      key: :plugin_credentials,
      text: "Plugin credentials",
      icon: :key,
      href_name: :plugin_credentials_path
    )
  ].freeze

  def dummy_host_nav_items
    DUMMY_HOST_NAV.map do |item|
      href = main_app.public_send(item.href_name)
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

  def dummy_host_nav_active?(key, href)
    case key
    when :home
      current_page?(main_app.root_path)
    when :recordings_tree
      current_page?(main_app.docs_recordings_tree_path)
    when :plugin_credentials
      current_page?(main_app.plugin_credentials_path)
    else
      current_page?(href)
    end
  end
end
