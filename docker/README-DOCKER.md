# VuFind 11 Docker Review Stack

A VuFind 11 web app in a container, built from this repository. The app runs PHP 8.3 with Apache. It uses the team MySQL and Solr servers named in `docker/configs/config.ini`. Nothing is written back into your checkout.

---

## Daily Workflow: `docker compose up --watch`

This is the normal way to run the stack. It starts the app and watches the source tree for edits, in the foreground so you can see the sync and rebuild output:

```bash
docker compose up --watch
```

Press Ctrl+C to stop watching. The containers stop with it; use `docker compose stop` to be sure.

`-d` cannot be combined with `--watch`. To run detached, start the stack with `docker compose up -d`, then run `docker compose watch` in a separate terminal.

What happens on each edit (rules are in the `develop.watch` block of `docker-compose.yml`):

| You change | What Compose does | Restart needed? |
| :--- | :--- | :--- |
| Anything under `module/` | Copies the file into the container, then clears `local/cache` | No |
| Anything under `themes/` | Copies the file into the container, then clears `local/cache` | No |
| `composer.json` | Full image rebuild (Composer install runs again) | Automatic |

Watch mode needs Docker Compose v2.22 or later. Compose v5.6.1 is installed on this machine.

**Not yet watched:** `composer.lock`, `config/` (including the Apache vhost), and the Dockerfile. Changes to these need a manual rebuild: `docker compose up -d --build`.

---

## Before You Start

Two files must exist in `docker/configs/` before the stack will start. Compose fails if they are missing:

- `config.ini`: the full VuFind config, copied in by hand.
- `Symphony.ini`: the SymWS driver config, copied in by hand.

Both files are gitignored (`docker/configs/.gitignore` contains `*.ini`). They can contain database passwords, keys, and SymWS credentials, so do not commit them.

---

## Access

| Service | URL | Notes |
| :--- | :--- | :--- |
| VuFind UI | http://localhost:8081/ | Login uses the SymWS patron credentials |

The app is served at the root (`/`), not under `/vufind`.

The stack has only the app container. MySQL and Solr are the team servers configured in `docker/configs/config.ini`, not containers.

The first build takes several minutes (PHP extensions and Composer packages). On start, the entrypoint copies the two config files into the `vufind_local` volume, then starts Apache. The log line `VuFind 11 Review Stack is up.` marks the end of startup.

---

## What Runs Where

| Component | Where | Set in |
| :--- | :--- | :--- |
| Web app (PHP 8.3, Apache) | `vufind11_app` container, port 8081 | `Dockerfile`, `docker-compose.yml` |
| Database | Team MySQL, `[Database] database` | `docker/configs/config.ini` |
| Search index | Team Solr, `[Index] url` | `docker/configs/config.ini` |
| ILS (SymWS) | Team SymWS, `[WebServices] baseURL` | `docker/configs/Symphony.ini` |
| Generated config | `vufind_local` volume, `/usr/local/vufind/local/config/vufind/` | Copied from `docker/configs/` on first start |
| Cache | `vufind_local` volume, `/usr/local/vufind/local/cache/` | Rebuilt by VuFind; cleared by watch on code edits |
| Logs | `vufind_local` volume, `/usr/local/vufind/local/logs/vufind.log` | `[Logging] file` in `config.ini` |

Check the live values with:

```bash
docker compose exec vufind grep -n "^database\|^url\|^driver" /usr/local/vufind/local/config/vufind/config.ini
```

---

## Changing Configuration

The container uses the copies in the `vufind_local` volume, not the files on the host. Copies are made only when the volume's file is missing or empty, so editing `docker/configs/` alone has no effect on a running stack.

To apply a change to either config file:

```bash
docker compose exec vufind rm /usr/local/vufind/local/config/vufind/config.ini
docker compose restart vufind
```

Remove `Symphony.ini` the same way if you changed it. Use `MSYS_NO_PATHCONV=1` in Git Bash, or the path will be rewritten.

Config files are mounted read-only, so the container cannot change them.

---

## Code, Templates, and Rebuilds

**The source tree is not mounted into the container.** Watch mode copies `module/` and `themes/` changes in, so day-to-day edits don't need a rebuild. Anything else needs a rebuild:

```bash
docker compose up -d --build
```

A rebuild re-runs the Composer install step, so it takes a minute or two.

### Theme layout

```text
themes/
├── bootstrap5/          Default Bootstrap 5 theme
├── sandal5/             Flat extension of bootstrap5
├── root/                Base templates
└── local_theme_example/ Example for a local theme
```

---

## Environment

`docker-compose.yml` sets `VUFIND_ENV=production`. The image defaults to `production` as well.

Do not set `VUFIND_ENV=development` for this stack. In development mode, Whoops turns a harmless warning in the Symphony driver into an exception, and every login fails with "We cannot log you in at this time." See `symphony-login-dubugbug.md` in the repository root.

---

## Logs

Application logs go to the volume:

```bash
docker compose exec vufind tail -f /usr/local/vufind/local/logs/vufind.log
```

Apache access and PHP notices go to the container log:

```bash
docker compose logs -f vufind
```

---

## Day-to-Day Commands

```bash
docker compose up --watch        # start in foreground, syncing source edits
docker compose up -d --build     # start, full rebuild (after Dockerfile, lock, or config/ changes)
docker compose stop              # stop, keep data
docker compose down              # stop and remove the container, keep the volume
docker compose ps                # status and ports
docker compose logs -f vufind    # container logs
docker compose exec vufind bash  # shell in the app container
```

### Starting from zero

`down -v` removes the `vufind_local` volume, which also deletes generated config, logs, and cache. The next start re-copies the config files and regenerates the local state:

```bash
docker compose down -v
docker compose up -d --build
```

This does not touch the team database or Solr. Use it only for a full local reset, not for ordinary source edits.

---

## How the Stack Is Built

| File | Purpose |
| :--- | :--- |
| `Dockerfile` | App image: PHP 8.3 and Apache, required extensions (including `xsl`), the repository tree, Composer dependencies, Apache site config |
| `entrypoint.sh` | Startup: copies config files into the volume if missing, then starts Apache |
| `docker-compose.yml` | The app service, port mapping, config mounts, watch rules, and the `vufind_local` volume |
| `docker/configs/` | Hand-copied `config.ini` and `Symphony.ini` (gitignored) |
| `config/vufind/httpd-vufind.conf` | Apache rules for theme assets and routing, applied at image build |
| `.dockerignore` | Keeps `docker/`, local state, and vendor output out of the build |

Named volume: `vufind_local`.

---

## Troubleshooting

**The page does not load at `http://localhost:8081/`.** Check `docker compose ps` and `docker compose logs -f vufind`.

**Compose fails with a missing file under `docker/configs/`.** Copy `config.ini` and `Symphony.ini` into that folder.

**"Session validation failed" in the browser.** The browser holds a `VUFIND_SESSION` cookie from an earlier run. Clear cookies for `localhost` and `127.0.0.1`, or use a private window.

**"We cannot log you in at this time."** Check that `VUFIND_ENV` is `production`. Check that `[WebServices] baseURL` in `Symphony.ini` answers (`curl http://<host>:8080/symws/` should return 200). Check `healthCheckId` in `config.ini` names a record that exists in the team index.

**Search returns no results or errors.** Check `[Index] url` in `config.ini`. The stack does not run its own Solr.

**Record page returns 500 with a SOAP error.** Check `baseURL` in `Symphony.ini` for stray spaces. The Symphony driver fails on `http:// host`.

**Changes do not appear.** If you're running watch, check its terminal for sync errors. For changes outside `module/` and `themes/` (or after editing `composer.lock` or `config/`), rebuild the image. For config, remove the copy in the volume and restart (see Changing Configuration).

**Port already in use.** Something else holds 8081. Change the left-hand port in `docker-compose.yml`.
