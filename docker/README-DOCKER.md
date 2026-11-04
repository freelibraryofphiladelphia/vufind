# VuFind 11 Docker Review Stack

A self-contained VuFind 11 stack for reviewing the UI, templates, and configuration. It runs PHP 8.3 with Apache, Solr 9.8.1, MariaDB 10.11, and Mailpit, and it is built entirely from the repository. Nothing is written back into your checkout.

---

## Quick Start

From the repository root (`C:\projects\vufind`):

```bash
docker compose up -d --build
```

The first build takes several minutes (PHP extensions and Composer packages). On first start the app generates its config, creates the database schema, and indexes 343 sample MARC records. Watch progress with `docker compose logs -f vufind`; the message `VuFind 11 Review Stack is fully operational!` marks the end of startup.

### Access points

| Service | URL | Notes |
| :--- | :--- | :--- |
| VuFind UI | http://localhost:8081/ | Demo user: `demo` / `password` |
| Solr admin | http://localhost:8083/solr | Cores: `biblio`, `authority`, `reserves`, `website` |
| Mailpit (mail UI) | http://localhost:8025/ | Catches all outgoing mail |
| MariaDB | `localhost:8082` | User `vufind` / `vufindpass`, database `vufind` |

The app is served at the root (`/`), not under `/vufind`.

---

## Working on Themes and Templates

**The source tree is not mounted into the containers.** The app runs the code baked into its image, so a change to a template, stylesheet, or script needs an image rebuild to appear:

```bash
docker compose up -d --build vufind
```

Then reload the page. Any change to the tree re-runs the Composer install step, so rebuilds take a minute or two.

Solr configs (`solr/vufind/`) are baked into the Solr image, so they need their own rebuild:

```bash
docker compose up -d --build solr
```

### Theme layout

```text
themes/
├── bootstrap5/          Default Bootstrap 5 theme
│   ├── templates/       PHTML templates (search, record, header, footer, ...)
│   ├── scss/            SCSS sources
│   ├── css/             Compiled CSS
│   └── js/              JavaScript modules
├── sandal5/             Flat extension of bootstrap5
├── root/                Base templates
└── local_theme_example/ Example for a local theme
```

### Creating a theme

Generate a new theme inside the container. The output goes into the container's image layer, not your checkout, so copy it out if you want to keep it:

```bash
docker compose exec vufind php public/index.php generate/theme MyTheme
docker compose cp vufind:/usr/local/vufind/themes/MyTheme ./themes/MyTheme
```

Then choose the theme in the footer dropdown, or set `theme = "MyTheme"` in the `[Site]` section of `docker/config-overrides.ini` and rebuild.

### Debug output

Debug mode is on (`debug = true` in `[System]`). It shows template paths and timing in the page footer.

---

## Configuration

VuFind reads `local/config/vufind/config.ini` in place of the default `config/vufind/config.ini`, so the review stack writes a complete merged copy on first start:

1. The default `config/vufind/config.ini`
2. The review-stack overrides in `docker/config-overrides.ini` (hostnames `db`, `solr`, `mailpit`, debug on, file sessions, Demo ILS driver)
3. A random `HMACkey`, generated once

That file lives in the `vufind_local` volume. To change settings:

- **Permanent change:** edit `docker/config-overrides.ini`, then rebuild and delete the generated file so it is regenerated:
  ```bash
  docker compose up -d --build vufind
  docker compose exec vufind rm /usr/local/vufind/local/config/vufind/config.ini
  docker compose restart vufind
  ```
- **Quick experiment:** edit the generated file directly inside the container (`docker compose exec vufind bash`). It is lost when the volume is removed.

---

## Sample Data

The stack indexes `tests/data/*.mrc` on first start if the `biblio` core is empty. The data includes books, journals, hierarchies, geolocated titles, and Dewey browse fixtures.

To index your own MARC file, copy it into the container and run the importer. The `solr.hosturl` override is needed because the tracked `import/import.properties` points at `localhost`:

```bash
docker compose cp yourfile.mrc vufind:/tmp/yourfile.mrc
docker compose exec -e EXTRA_SOLRMARC_SETTINGS="-Dsolr.hosturl=http://solr:8983/solr/biblio/update" vufind ./import-marc.sh /tmp/yourfile.mrc
```

---

## Day-to-Day Commands

```bash
docker compose up -d --build     # start, rebuilding after changes
docker compose stop              # stop, keep data
docker compose down              # stop and remove containers (keeps volumes)
docker compose ps                # status and ports
docker compose logs -f vufind    # app logs
docker compose exec vufind bash  # shell in the app container
docker compose exec vufind php public/index.php list   # list CLI commands
```

### Starting from zero

`down -v` removes the database, Solr index, and generated local config. The next `up` rebuilds all of them, including a new `HMACkey`:

```bash
docker compose down -v
docker compose up -d --build
```

---

## How the Stack Is Built

| File | Purpose |
| :--- | :--- |
| `Dockerfile` | App image: PHP 8.3 + Apache, the repository tree, Composer dependencies, SolrJ for SolrMarc, Apache site config |
| `solr/Dockerfile` | Solr image: stock Solr 9.8.1 with the VuFind cores and analysis-extras jars |
| `entrypoint.sh` | Startup: generates the local config, waits for MariaDB, imports the schema, indexes sample data |
| `docker/merge-config.php` | Builds the merged local config (default + overrides + HMACkey) |
| `docker/config-overrides.ini` | Review-stack settings applied on top of the default config |
| `config/vufind/httpd-vufind.conf` | Apache rules for theme assets and routing, applied at image build |
| `docker-compose.yml` | Services, ports, and named volumes |
| `.dockerignore`, `solr/.dockerignore` | Keep local state, vendor output, and Solr index data out of the build |

Named volumes: `db_data`, `vufind_local`, `solr_biblio_index`, `solr_biblio_tlog`, `solr_authority_index`.

---

## Troubleshooting

**The page does not load at `http://localhost:8081/`.** Check `docker compose ps`. The app starts Apache only after its startup script finishes, which includes waiting for MariaDB. Check `docker compose logs -f vufind`.

**Search fails with a Solr error.** Check the `biblio` core: `docker compose exec vufind curl -s http://solr:8983/solr/biblio/admin/ping`. Solr reports a core load failure in `docker compose logs solr`. Solr does not block the app; the app starts without it and retries on each request.

**Search results are empty.** The sample index only loads on first start. Re-run the import from the sample data section, or reset with `down -v`.

**`ServiceNotCreatedException` mentioning `HMACkey`.** The generated config is missing or empty. Delete `local/config/vufind/config.ini` in the container and restart the app so it regenerates.

**Changes to templates or CSS do not appear.** Rebuild the app image: `docker compose up -d --build vufind`. Then clear the browser cache if the stylesheet is cached.

**Port already in use.** Another stack is holding 8081, 8082, 8083, 8025, or 1025. Change the left-hand port in `docker-compose.yml`.

**Start over completely.** `docker compose down -v`, then `docker compose up -d --build`.
