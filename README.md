# Paremiologia catalana comparada digital (PCCD)

This is the source code of [Paremiologia catalana comparada digital](https://pccd.dites.cat/) website. The PCCD database
and media files are not distributed with this repository.

## Installation

1. Copy `.env.sample` to `.env`.

2. Build the container using the default Debian-based image:

```bash
docker-compose up
```

When the database has finished importing, the website should be available at <http://localhost:8092>, depending on your
`.env` file.

Note: If you don't have a database, you can copy `tmp/schema.sql` and `tmp/schema_init_sample.sql` files to
`install/db/`. That will import an empty database and should allow you to browse the website locally.

### Alpine-based image

An Alpine-based image is used in production, and is also available locally:

```bash
docker-compose -f docker-compose-alpine.yml up
```

Or:

```bash
docker system prune -f
docker-compose -f docker-compose-alpine.yml -f docker-compose-alpine.override.sample.yml up --build
```

## Updating the content and creating a new release

For detailed instructions on updating the content and pushing a new release, please see the [Content Update and Release Guide](docs/Content_Update_and_Release_Guide.md).

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

This repository includes:

- [phive](https://phar.io/), a [BSD-licensed](tools/LICENSE.txt) tool for managing phar files.
- [simple-datatables](https://github.com/fiduswriter/simple-datatables), an
  [LGPL-licensed](https://github.com/fiduswriter/simple-datatables/blob/main/LICENSE) JavaScript library to enhance HTML tables.
