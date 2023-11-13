let
  nixpkgsVersion = {
    url =
      "https://github.com/NixOS/nixpkgs/archive/81327627c05d231679b049835fb22ee5bb284974.tar.gz";
    sha256 = "120s7z9s4gyxx1xmsd2g2ah8nl245m00yfpn7d26ya7kgwzaxw88";
  };

  pinnedPkgs = import (fetchTarball nixpkgsVersion) { };
in pinnedPkgs.mkShell {
  buildInputs = with pinnedPkgs; [
    (php82.withExtensions ({ all, ... }:
      with all; [
        curl
        dom
        filter
        gd
        imagick
        intl
        mbstring
        opcache
        openssl
        pdo
        pdo_mysql
        simplexml
        tokenizer
        xmlwriter
        zlib
      ]))
    _7zz
    curl
    docker-compose
    dotenv-linter
    file
    gifsicle
    gnupg
    hadolint
    icu
    jpeginfo
    jpegoptim
    jq
    libwebp
    libxml2
    maven
    mdbtools
    nodejs
    optipng
    oxipng
    pngcheck
    pngquant
    shellcheck
    shfmt
    statix
    unar
    unzip
    yamlfmt
    yamllint
  ];

  shellHook = ''
    if [ ! -d "./node_modules" ]; then
      echo "Installing dependencies..."
      yarn install
    else
      echo "Dependencies already installed. Delete node_modules directory to reinstall or update them."
    fi
  '';
}
