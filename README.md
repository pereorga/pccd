# Paremiologia catalana comparada digital (PCCD)

This is the source code of [Paremiologia catalana comparada digital](https://pccd.dites.cat/). Please visit
<https://pccd.dites.cat> website and its [credits](https://pccd.dites.cat/credits) page for more information.

Note: The PCCD database and the media files are covered by different terms and are not distributed with this repository.

## Installation

1. Copy `.env.sample` to `.env`.

2. Build the container:

```bash
docker-compose up --build
```

When the database has finished importing, the website should be available at both <http://localhost:8091> (default
Varnish port) and <http://localhost:8092> (default Apache port).

Note: If you don't have a database, you can copy `tmp/schema.sql` and `tmp/schema_init_sample.sql` files to
`install/db/`. That will import an empty database and should allow you to browse the website locally.

## Updating the repository with a new release

Note: tools in `tools/` can be downloaded by running `phive install`. See <https://phar.io/> for more details.

### Installation on Linux (Debian-based, tested on Ubuntu 22.04)

```bash
xargs sudo apt-get install -y < apt-packages.txt
```

You may want to set up Docker to be used with a non-root user.

For additional lossless compression of PNG images, [oxipng](https://github.com/shssoichiro/oxipng) can be installed. See
also [hadolint](https://github.com/hadolint/hadolint) for linting Docker files, and [shfmt](https://github.com/mvdan/sh)
for prettifying shell scripts. Also, some development scripts require at least [Node.js](https://nodejs.org/) v18. At
this time (on Ubuntu 22.04) the packages and versions above can also be installed using [Homebrew](https://brew.sh/):

```bash
brew bundle install --file=ubuntu.Brewfile
```

And then the yarn/composer dependencies:

```bash
tools/composer install && npm install --global yarn && yarn install
```

### Installation on macOS (and Linux, if using Homebrew)

After installing [Homebrew](https://brew.sh/), run the following in this directory to install all developer
dependencies:

```bash
brew bundle install && tools/composer install && npm install --global yarn && yarn install
```

### Procedure

**Part 1**: Update files and build. Usually, new image files (Cobertes.zip, Imatges.zip, Obres-VPR.zip) and the database
(database.accdb) are provided. Put them in this directory before running the following:

```bash
rm -r src/images/cobertes src/images/paremies
unar -e IBM-850 Cobertes.zip && mv Cobertes/ src/images/cobertes
unar -e IBM-850 Imatges.zip && mv Imatges/ src/images/paremies
unar -e IBM-850 Obres-VPR.zip && mv -n Obres-VPR/* src/images/cobertes && rm -r Obres-VPR/
chmod 644 src/images/paremies/* src/images/cobertes/*
php scripts/image-convertor/app.php
scripts/convert_db.sh database.accdb
scripts/docker_build.sh
```

**Part 2**: Install (in a separate shell, after the database has been initialized in **Part 1**)

```bash
scripts/install.sh
```

When this command finishes, the website should be available and run properly at both <http://localhost:8091> (Varnish)
and <http://localhost:8092> (Apache).

**Part 3**: Export the database for future deployments, run tests and generate reports.

```bash
docker exec pccd-mysql /usr/bin/mysqldump -uroot -pcontrasenyarootmysql --no-data pccd > tmp/schema.sql
docker exec pccd-mysql /usr/bin/mysqldump -uroot -pcontrasenyarootmysql pccd > install/db/db.sql
yarn test
scripts/generate_reports.sh
git add . && git commit -m 'new release' && git push
```

## Local development

### Development requirements

- PHP: 8.1 or later is required.
- Node.js: 18 or later is required.

### Assets

CSS/JavaScript code resides in `src/js/` and `src/css/`. Assets are built and minified running:

```bash
yarn build:assets
```

### Image conversion and optimization

To compress and convert already converted images, delete them before running `scripts/image-convertor/app.php`:

```bash
rm -f docroot/img/imatges/* docroot/img/obres/*
```

### Code linting, formatting and static code analysis

```bash
yarn check:code
```

### Automated tests

Note: functional tests require the website to be running.

```bash
yarn test
```

For running some tests in **all** pages, run:

```bash
yarn check:sitemap
```

### Profiling

SPX and XHProf profilers can be used to pass the profiler argument to docker-compose:

```bash
scripts/docker_build.sh --build-arg profiler=spx
```

```bash
scripts/docker_build.sh --build-arg profiler=xhprof
```

Profiler reports can be accessed in the `/admin/` path alongside the other reports (password is set in the `.env` file).

## TODO

### Short-term

- Understand Apache configuration: <https://stackoverflow.com/q/72966421/1391963>

### Long term

- Consider start using GA conditionally when the cookie dialog has been accepted
- Or better, consider removing Google Tag Manager or switching to a lighter Google Analytics alternative. See <https://news.ycombinator.com/item?id=32068539>
- Consider migrating to PostgreSQL
- Consider switching to PHP-FPM and Nginx
- UX: Consider adding search functionality on every page
- UX: Consider adding zoom icons for increasing font size

## License

Copyright (c) Pere Orga Esteve <pere@orga.cat>, 2020.

Copyright (c) Víctor Pàmies i Riudor <vpamies@gmail.com>, 2020.

Use of this source code is governed by the GNU Affero General Public License v3.0 found in the LICENSE file or at
<https://www.gnu.org/licenses/agpl-3.0.html>.

File `scripts/common-voice-export/pccd.txt` is Copyright (c) Víctor Pàmies i Riudor and is made available under the
[Creative Commons Zero 1.0 Universal license](https://creativecommons.org/publicdomain/zero/1.0/) (CC0 1.0).

For more details about PCCD, visit <https://pccd.dites.cat/>.
