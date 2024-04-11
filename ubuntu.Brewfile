# Homebrew dev packages for Ubuntu 24.04 (packages not available via apt-get).
# From this list, Oxipng is the only real dev dependency we miss in Ubuntu/Debian official repositories and that prevent
# us from removing Homebrew.

# See https://github.com/Homebrew/homebrew-bundle/issues/1150.
brew "bzip2", link: false  # Required by hadolint
brew "sqlite", link: false # Required by hadolint
brew "unzip", link: false  # Required by hadolint
brew "xz", link: false     # Required by hadolint

brew "dotenv-linter"
# See https://github.com/hadolint/hadolint/issues/1005.
brew "hadolint"
# See https://github.com/shssoichiro/oxipng/issues/69.
brew "oxipng"
brew "statix"
brew "yamlfmt"
