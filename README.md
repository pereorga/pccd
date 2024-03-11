# Paremiologia catalana comparada digital (PCCD)

This is the source code of [Paremiologia catalana comparada digital](https://pccd.dites.cat/) website. The PCCD database
and media files are not distributed with this repository.

## Installation

1. Copy `.env.sample` to `.env`.

2. Build the container:

```bash
docker-compose up
```

When the database has finished importing, the website should be available at <http://localhost:8092>, depending on your
`.env` file.

Note: If you don't have a database, you can copy `tmp/schema.sql` and `tmp/schema_init_sample.sql` files to
`install/db/`. That will import an empty database and should allow you to browse the website locally.

### Alpine-based image

Alternatively, and Alpine image is also available:

```bash
docker-compose -f docker-compose-alpine.yml up
```

## Updating the repository with a new release

### Option 1: Native + Docker (Linux / macOS)

#### Prerequisites: Linux (Debian-based)

```bash
xargs sudo apt-get install -y < apt-packages.txt
```

You may want to set up Docker to be used with a non-root user.

Some additional packages can be installed using [Homebrew](https://brew.sh/):

```bash
brew bundle install --file=ubuntu.Brewfile
```

The rest of dependencies can be installed using `npm`:

```bash
npm ci
```

#### Prerequisites: macOS (Homebrew)

After installing [Homebrew](https://brew.sh/), run the following from the root directory to install all developer
dependencies:

```bash
brew bundle install && npm ci && pecl install imagick
```

#### Prerequisites: Linux (Nix) / macOS (Nix) untested

```bash
nix-shell
```

#### Procedure

**Part 1**: Update the database, add new images and build the container. Usually, new image files (Cobertes.zip,
Imatges.zip, Obres-VPR.zip) are provided. Put them in the root directory alongside the MS Access database
(database.accdb) before running the following (skip the first 2 commands if images have not been provided):

```bash
npm run decompress:images && npm run optimize:images && npm run convert:db && npm run build:docker
```

**Part 2**: Install (in a separate shell, after the database has been initialized in **Part 1**)

```bash
npm run install:db
```

When this command finishes, the website should be available and run properly.

**Part 3**: Export the database, run tests and generate reports.

```bash
npm run prepare:deploy
```

The code can now be pushed to both private and public repositories for deployment:

```bash
git add . && git commit -m 'new release' && git push
npm run export:code
```

### Option 2: Docker-based (Linux, macOS, Windows)

The whole process of updating a release could be run 100% inside Docker, although this is not regularly tested.

Example on Windows:

```batch
:: Disable .dockerignore to include everything in the build context
ren .dockerignore .dockerignore.disabled
:: Start the build-specific container
docker-compose -f docker-compose-build.yml up
:: Run the processing commands within the build container
docker-compose -f docker-compose-build.yml run build /bin/bash -c "npm run decompress:images && npm run optimize:images && npm run convert:db"
:: Restore .dockerignore
ren .dockerignore.disabled .dockerignore
:: Remove existing container, in case it was already created before
docker-compose down --volumes
:: Start the HTTP and MariaDB servers
docker-compose up --build
:: Execute the installation script inside the web container
docker exec pccd-web scripts/install.sh
:: Export the updated database
docker exec pccd-mysql /usr/bin/mysqldump -uroot -pcontrasenyarootmysql --skip-dump-date --ignore-table=pccd.commonvoice pccd > install\db\db.sql
```

## Local development

### Development requirements

- PHP: 8.2 or later is required.
- Node.js: 18.16 or later is required.
- Docker

### Assets

CSS/JavaScript code resides in `src/js/` and `src/css/`. Assets are built and minified running:

```bash
npm run build:assets
```

### Image conversion and optimization

To compress and convert already converted images, delete them before running `npm run optimize:images`:

```bash
npm run delete:images
```

### Code linting, formatting and static code analysis

Linting and static code analysis:

```bash
npm run check:code
```

Automatic fixing code and formatting:

```bash
npm run fix
```

### Automated tests

```bash
npm test
```

`BASE_URL` environment variable can be overridden in tests that target the web server (e.g. Playwright):

```bash
BASE_URL=https://pccd.dites.cat npm test
```

You may need to run `npm run refresh:test-data` if the data has changed, in order to pass some e2e tests.

For running some tests in **all** pages, run:

```bash
npm run check:sitemap
```

### Profiling

SPX and XHProf profilers are available:

```bash
npm run build:docker:spx
```

```bash
npm run build:docker:xhprof
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
