# frozen_string_literal: true

require "fileutils"
require "open3"
require "pathname"
require "rubygems/package"
require "tmpdir"

# Shared allowlists and dummy-host fingerprints for gem and WordPress ZIP packages.
module PackageBoundaries
  REPO_ROOT = File.expand_path("..", __dir__)
  PLUGIN_DIR = File.join(REPO_ROOT, "wordpress", "recording-studio-widgets")
  PLUGIN_SLUG = "recording-studio-widgets"
  PKG_DIR = File.join(REPO_ROOT, "pkg")

  GEM_ROOT_FILES = %w[MIT-LICENSE Rakefile README.md].freeze
  GEM_PREFIXES = %w[app/ config/ db/ lib/].freeze
  GEM_FORBIDDEN_EXTENSIONS = %w[.php .js .scss .jsx .ts .tsx].freeze

  ZIP_ROOT_FILES = %w[recording-studio-widget.php readme.txt].freeze
  ZIP_PREFIXES = %w[includes/ build/].freeze
  ZIP_FORBIDDEN_EXTENSIONS = %w[.rb .gemspec .erb].freeze
  ZIP_FORBIDDEN_BASENAMES = %w[
    Gemfile
    Gemfile.lock
    Rakefile
    package.json
    package-lock.json
    .wp-env.json
    .gitignore
    .editorconfig
    composer.json
    composer.lock
  ].freeze

  # Baked SDK files the installable ZIP must ship (copied into build/sdk/ by npm run build).
  REQUIRED_SDK_FILES = %w[
    build/sdk/recording-studio-plugin-sdk.js
    build/sdk/recording-studio-plugin-sdk.css
  ].freeze

  DUMMY_PATH_FRAGMENTS = %w[
    test/dummy
    dummy/app/
    dummy/config/
    dummy/db/
    dummy/test/
    dummy/bin/
  ].freeze

  DUMMY_SOURCE_MARKERS = [
    "module Dummy",
    "Dummy::Application",
    "dummy_page_nav"
  ].freeze

  module_function

  def gem_path_allowed?(path)
    normalized = normalize_path(path)
    return false if dummy_path?(normalized)
    return false if GEM_FORBIDDEN_EXTENSIONS.include?(File.extname(normalized))
    return true if GEM_ROOT_FILES.include?(normalized)

    GEM_PREFIXES.any? { |prefix| normalized.start_with?(prefix) }
  end

  def zip_path_allowed?(path)
    normalized = strip_zip_prefix(normalize_path(path))
    return false if dummy_path?(normalized)
    return false if ZIP_FORBIDDEN_EXTENSIONS.include?(File.extname(normalized))
    return false if ZIP_FORBIDDEN_BASENAMES.include?(File.basename(normalized))
    return true if ZIP_ROOT_FILES.include?(normalized)

    ZIP_PREFIXES.any? { |prefix| normalized.start_with?(prefix) }
  end

  def dummy_path?(path)
    normalized = normalize_path(path)
    DUMMY_PATH_FRAGMENTS.any? { |fragment| normalized.include?(fragment) }
  end

  def dummy_content?(text)
    return false if text.nil?

    DUMMY_SOURCE_MARKERS.any? { |marker| text.include?(marker) }
  end

  def violations_for(kind, paths:, contents: {})
    allowed = kind == :gem ? method(:gem_path_allowed?) : method(:zip_path_allowed?)
    violations = []

    paths.each do |path|
      violations << "path not allowed in #{kind}: #{path}" unless allowed.call(path)
      violations << "dummy host path in #{kind}: #{path}" if dummy_path?(path)
      if dummy_content?(contents[path])
        violations << "dummy host source in #{kind} file #{path}"
      end
    end

    violations.uniq
  end

  def build_gem!(destination = default_gem_destination)
    FileUtils.mkdir_p(File.dirname(destination))
    stdout, stderr, status = Open3.capture3(
      "gem", "build", "recording_studio_wordpress_plugin_template.gemspec",
      chdir: REPO_ROOT
    )
    built = Dir.glob(File.join(REPO_ROOT, "recording_studio_wordpress_plugin_template-*.gem")).max_by do |path|
      File.mtime(path)
    end
    raise "gem build failed: #{stdout}#{stderr}" unless status.success? && built

    FileUtils.mv(built, destination)
    destination
  end

  def build_wordpress_zip!(destination = default_zip_destination, compile: true)
    raise "plugin dir missing: #{PLUGIN_DIR}" unless File.directory?(PLUGIN_DIR)

    compile_plugin_assets! if compile
    assert_sdk_present!

    entries = wordpress_zip_entries
    raise "WordPress ZIP allowlist is empty" if entries.empty?
    assert_zip_entries_include_sdk!(entries)

    FileUtils.mkdir_p(File.dirname(destination))
    write_zip(entries, destination)
    destination
  end

  # Runs npm ci && npm run build so the ZIP is reproducible from a clean tree.
  def compile_plugin_assets!
    raise "plugin dir missing: #{PLUGIN_DIR}" unless File.directory?(PLUGIN_DIR)
    raise "package-lock.json missing in #{PLUGIN_DIR}" unless File.file?(File.join(PLUGIN_DIR, "package-lock.json"))

    [
      %w[npm ci],
      %w[npm run build]
    ].each do |command|
      stdout, stderr, status = Open3.capture3(*command, chdir: PLUGIN_DIR)
      next if status.success?

      raise "#{command.join(' ')} failed in #{PLUGIN_DIR}: #{stdout}#{stderr}"
    end
  end

  def assert_sdk_present!
    missing = REQUIRED_SDK_FILES.reject { |relative| File.file?(File.join(PLUGIN_DIR, relative)) }
    return if missing.empty?

    raise "WordPress plugin SDK missing after build: #{missing.join(', ')}. Run npm run build in #{PLUGIN_DIR}."
  end

  def assert_zip_entries_include_sdk!(entries)
    missing = REQUIRED_SDK_FILES.reject { |relative| entries.key?("#{PLUGIN_SLUG}/#{relative}") }
    return if missing.empty?

    raise "WordPress ZIP omit SDK files: #{missing.join(', ')}"
  end

  def wordpress_zip_entries
    entries = {}

    ZIP_ROOT_FILES.each do |name|
      source = File.join(PLUGIN_DIR, name)
      next unless File.file?(source)

      entries["#{PLUGIN_SLUG}/#{name}"] = source
    end

    ZIP_PREFIXES.each do |prefix|
      Dir.glob(File.join(PLUGIN_DIR, prefix, "**", "*"), File::FNM_DOTMATCH).each do |source|
        next unless File.file?(source)

        relative = Pathname.new(source).relative_path_from(PLUGIN_DIR).to_s
        next unless zip_path_allowed?(relative)

        entries["#{PLUGIN_SLUG}/#{relative}"] = source
      end
    end

    entries
  end

  def inspect_gem(gem_path)
    raise "gem missing: #{gem_path}" unless File.file?(gem_path)

    paths = []
    contents = {}

    Dir.mktmpdir("gem-inspect-") do |tmp|
      Gem::Package.new(gem_path).extract_files(tmp)
      Dir.glob(File.join(tmp, "**", "*"), File::FNM_DOTMATCH).each do |file|
        next unless File.file?(file)

        relative = Pathname.new(file).relative_path_from(tmp).to_s
        paths << relative
        contents[relative] = File.read(file)
      end
    end

    { paths: paths, contents: contents, violations: violations_for(:gem, paths: paths, contents: contents) }
  end

  def inspect_zip(zip_path)
    raise "zip missing: #{zip_path}" unless File.file?(zip_path)

    listing = zip_listing(zip_path)
    contents = zip_contents(zip_path)
    {
      paths: listing,
      contents: contents,
      violations: violations_for(:zip, paths: listing, contents: contents)
    }
  end

  def default_gem_destination
    version = File.read(File.join(REPO_ROOT, "lib/recording_studio_wordpress_plugin_template/version.rb"))
      .match(/VERSION\s*=\s*"([^"]+)"/)[1]
    File.join(PKG_DIR, "recording_studio_wordpress_plugin_template-#{version}.gem")
  end

  def default_zip_destination
    File.join(PKG_DIR, "#{PLUGIN_SLUG}.zip")
  end

  def normalize_path(path)
    path.to_s.tr("\\", "/").delete_prefix("./")
  end

  def strip_zip_prefix(path)
    path.delete_prefix("#{PLUGIN_SLUG}/")
  end

  def write_zip(entries, destination)
    mapping = entries.map { |zip_path, source| "#{source}\t#{zip_path}" }.join("\n")
    script = <<~'PY'
      import sys
      import zipfile

      dest = sys.argv[1]
      with zipfile.ZipFile(dest, "w", zipfile.ZIP_DEFLATED) as archive:
          for line in sys.stdin.read().splitlines():
              source, name = line.split("\t", 1)
              archive.write(source, name)
    PY
    stdout, stderr, status = Open3.capture3("python3", "-c", script, destination, stdin_data: mapping)
    raise "zip write failed: #{stdout}#{stderr}" unless status.success?
  end

  def zip_listing(zip_path)
    script = <<~'PY'
      import sys
      import zipfile

      with zipfile.ZipFile(sys.argv[1]) as archive:
          print("\n".join(archive.namelist()))
    PY
    stdout, stderr, status = Open3.capture3("python3", "-c", script, zip_path)
    raise "zip list failed: #{stdout}#{stderr}" unless status.success?

    stdout.split("\n").reject(&:empty?)
  end

  def zip_contents(zip_path)
    script = <<~'PY'
      import sys
      import zipfile

      with zipfile.ZipFile(sys.argv[1]) as archive:
          for name in archive.namelist():
              if name.endswith("/"):
                  continue
              data = archive.read(name)
              sys.stdout.buffer.write(name.encode() + b"\0")
              sys.stdout.buffer.write(str(len(data)).encode() + b"\0")
              sys.stdout.buffer.write(data)
    PY
    stdout, stderr, status = Open3.capture3("python3", "-c", script, zip_path)
    raise "zip read failed: #{stderr}" unless status.success?

    contents = {}
    buffer = stdout.b
    until buffer.empty?
      name, buffer = buffer.split("\0", 2)
      length_s, buffer = buffer.split("\0", 2)
      length = Integer(length_s)
      data = buffer.byteslice(0, length)
      buffer = buffer.byteslice(length..) || "".b
      contents[name] = data.to_s
    end
    contents
  end
end
