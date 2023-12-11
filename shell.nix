let
  nixpkgsVersion = {
    url = "https://github.com/NixOS/nixpkgs/archive/ab3ea20adfe05a2328b4991612912ef7db67af83.tar.gz";
    sha256 = "1l0q15x3l4p7y43jqjvg92nq5wfw7k3ixkp5rqzyvzbz2rcv4rss";
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
        yarn install --frozen-lockfile
      else
        echo "Dependencies already installed. Delete node_modules directory to reinstall or update them."
      fi
    '';
  }
