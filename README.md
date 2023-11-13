# Paremiologia catalana comparada digital (PCCD)

This is the source code of [Paremiologia catalana comparada digital](https://pccd.dites.cat/) website. The PCCD database
and media files are not distributed with this repository.

## Installation

1. Copy `.env.sample` to `.env`.

2. Build the container:

```bash
docker-compose up --build
```

When the database has finished importing, the website should be available at both <http://localhost:8091> (Varnish) and
<http://localhost:8092> (Apache), depending on your `.env` file.

Note: If you don't have a database, you can copy `tmp/schema.sql` and `tmp/schema_init_sample.sql` files to
`install/db/`. That will import an empty database and should allow you to browse the website locally.

## Updating the repository with a new release

### Installation using Nix (Linux, macOS) - WIP

```bash
nix-shell
```

### Installation on Linux (Debian-based)

```bash
xargs sudo apt-get install -y < apt-packages.txt
```

You may want to set up Docker to be used with a non-root user.

Some additional packages can be installed using [Homebrew](https://brew.sh/):

```bash
brew bundle install --file=ubuntu.Brewfile
```

The rest of dependencies can be installed using [Yarn](https://yarnpkg.com/):

```bash
npm install --global yarn && yarn install
```

### Installation on macOS (using Homebrew)

After installing [Homebrew](https://brew.sh/), run the following from the root directory to install all developer
dependencies:

```bash
brew bundle install && npm install --global yarn && yarn install && pecl install imagick
```

### Procedure

**Part 1**: Update files and build. Usually, new image files (Cobertes.zip, Imatges.zip, Obres-VPR.zip) and the database
(database.accdb) are provided. Put them in the root directory before running the following:

```bash
yarn decompress:images && yarn optimize:images && yarn convert:db && yarn build:docker
```

**Part 2**: Install (in a separate shell, after the database has been initialized in **Part 1**)

```bash
yarn install:db
```

When this command finishes, the website should be available and run properly.

**Part 3**: Export the database for future deployments, run tests and generate reports.

```bash
yarn prepare:deploy
git add . && git commit -m 'new release' && git push
yarn export:code
```

## Local development

### Development requirements

- PHP: 8.2 or later is required.
- Node.js: 18.16 or later is required.

### Assets

CSS/JavaScript code resides in `src/js/` and `src/css/`. Assets are built and minified running:

```bash
yarn build:assets
```

### Image conversion and optimization

To compress and convert already converted images, delete them before running `yarn optimize:images`:

```bash
yarn delete:images
```

### Code linting, formatting and static code analysis

Linting and static code analysis:

```bash
yarn check:code
```

Automatic fixing code and formatting:

```bash
yarn fix
```

### Automated tests

Note: e2e tests require the website to be running.

```bash
yarn test
```

You may need to run `yarn refresh:test-data` if the data has changed, in order to pass the tests.

For running some tests in **all** pages, run:

```bash
yarn check:sitemap
```

### Profiling

SPX and XHProf profilers are available:

```bash
yarn build:docker:spx
```

```bash
yarn build:docker:xhprof
```

Profiler reports can be accessed in `/admin/`, alongside the other reports (web admin password is set in the `.env`
file).

## Contributing

For details on contributing to this repository, see the contributing guidelines:

- [English version](CONTRIBUTING.md)
- [Versió en català](CONTRIBUTING_ca.md)

## TODO

- Compliance: consider start using GA conditionally when the cookie dialog has been accepted, or better, consider
  removing Google Tag Manager or switching to a lighter Google Analytics alternative (see
  <https://news.ycombinator.com/item?id=32068539>)
- Infra: Consider migrating to Postgres
- Infra: Consider switching to PHP-FPM, and maybe Nginx
- Build: Consider switching to pnpm, latest yarn or going back to npm
- UX: Consider adding search functionality on every page

## License

Copyright (c) Pere Orga Esteve <pere@orga.cat>, 2020.

Copyright (c) Víctor Pàmies i Riudor <vpamies@gmail.com>, 2020.

Use of this source code is governed by the GNU Affero General Public License v3.0 found in the [LICENSE](LICENSE) file
or at <https://www.gnu.org/licenses/agpl-3.0.html>.

File [scripts/common-voice-export/pccd.txt](scripts/common-voice-export/pccd.txt)
is Copyright (c) Víctor Pàmies i Riudor and is made available under the
[Creative Commons Zero 1.0 Universal license](https://creativecommons.org/publicdomain/zero/1.0/) (CC0 1.0).

For more details about PCCD, visit <https://pccd.dites.cat/>.

This repository includes [phive](https://phar.io/), a [BSD-licensed](tools/LICENSE.txt) tool for managing phar files.
