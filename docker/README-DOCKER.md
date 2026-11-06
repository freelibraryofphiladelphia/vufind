# VuFind 11 Docker Review Stack

A VuFind 11 web app in a container, built from this repository. The app runs PHP 8.3 with Apache. It uses the team MySQL and Solr servers named in `docker/configs/config.ini`. Nothing is written back into your checkout.

---

## Daily Workflow: `docker compose up --watch`

Run all commands from the repository root. The compose file lives in `docker/`, so every command takes `-f docker/docker-compose.yml`.

**First time, or after changing the Dockerfile, entrypoint, or `composer.json`:** build the image before watching. Watch does not rebuild for these files.

```powershell
docker compose -f docker/docker-compose.yml build
docker compose -f docker/docker-compose.yml up --watch
```

This is the normal way to run the stack after that. It starts the app and watches for edits, in the foreground so you can see the sync and rebuild output:

```powershell
docker compose -f docker/docker-compose.yml up --watch
```

Press Ctrl+C to stop watching. The containers stop with it; use `docker compose -f docker/docker-compose.yml stop` to be sure.

`-d` cannot be combined with `--watch`. To run detached, start the stack with `docker compose -f docker/docker-compose.yml up -d`, then run `docker compose -f docker/docker-compose.yml watch` in a separate terminal.

What happens on each edit (rules are in the `develop.watch` block of `docker/docker-compose.yml`):

| You change | What Compose does | Restart needed? |
| :--- | :--- | :--- |
| Anything under `module/` | Copies the file into the container, then clears `local/cache` | No |
| Anything under `themes/` | Copies the file into the container, then clears `local/cache` | No |
| `composer.json` | Full image rebuild (Composer install runs again) | Automatic |
| Anything under `docker/configs/` | Clears `local/cache` only. The edited file is read live through the bind mount | No |

Watch mode needs Docker Compose v2.22 or later. Compose v5.6.1 is installed on this machine.

**Not watched:** `composer.lock`, `config/` (including the Apache vhost), `docker/entrypoint.sh`, and `docker/Dockerfile`. Changes to these need a manual rebuild: `docker compose -f docker/docker-compose.yml up -d --build`.

---

## Before You Start

Two files must exist in `docker/configs/` before the stack will start:

- `config.ini`: the full VuFind config, copied in by hand.
- `Symphony.ini`: the SymWS driver config, copied in by hand.

Both files are gitignored (`docker/configs/.gitignore` contains `*.ini`). They contain database passwords, keys, and SymWS credentials, so do not commit them.

This is a local-only stack. The image does not contain these files, because `.dockerignore` excludes `docker/configs`. They reach the container only through the bind mount, so do not push or share the image for any other reason.

---

## Access

| Service | URL | Notes |
| :--- | :--- | :--- |
| VuFind UI | http://localhost:8081/ | Bound to 127.0.0.1 only. Login uses the SymWS patron credentials |

The app is served at the root (`/`), not under `/vufind`.

The stack has only the app container. MySQL and Solr are the team servers configured in `docker/configs/config.ini`, not containers.

The first build takes several minutes (PHP extensions and Composer packages). The log line `VuFind 11 Review Stack is up.` marks the end of startup.

---

## What Runs Where

| Component | Where | Set in |
| :--- | :--- | :--- |
| Web app (PHP 8.3, Apache) | `vufind11_app` container, `127.0.0.1:8081` | `docker/Dockerfile`, `docker/docker-compose.yml` |
| Database | Team MySQL, `[Database] database` | `docker/configs/config.ini` |
| Search index | Team Solr, `[Index] url` | `docker/configs/config.ini` |
| ILS (SymWS) | Team SymWS, `[WebServices] baseURL` | `docker/configs/Symphony.ini` |
| Config the app reads | Host `docker/configs/`, bind-mounted read-only at `/usr/local/vufind/local/config/vufind/` | `docker/docker-compose.yml` |
| Cache | `vufind_local` volume, `/usr/local/vufind/local/cache/` | Rebuilt by VuFind; cleared by watch on edits |
| Logs | `vufind_local` volume, `/usr/local/vufind/local/logs/vufind.log` | `[Logging] file` in `config.ini` |

Check the live values with:

```powershell
docker compose -f docker/docker-compose.yml exec vufind grep -n "^database\|^url\|^driver" /usr/local/vufind/local/config/vufind/config.ini
```

---

## Changing Configuration

The app reads `docker/configs/*.ini` directly through a read-only bind mount. There is one copy of each file, on the host. Edit it there.

Watch clears the cache when a file under `docker/configs/` changes. Config is cached, so the cache clear is what makes the edit take effect. If you edit with watch stopped, clear the cache by hand:

```powershell
docker compose -f docker/docker-compose.yml exec vufind sh -c "rm -rf /usr/local/vufind/local/cache/*"
```

Config files are mounted read-only, so the container cannot change them.

**Stale copies:** earlier versions of this stack copied the config into the `vufind_local` volume. The bind mount hides those files, but they remain in the volume until `down -v`. They are not read.

---

## Code, Templates, and Rebuilds

**The source tree is not mounted into the container.** Watch mode copies `module/` and `themes/` changes in, so day-to-day edits don't need a rebuild. Anything else needs a rebuild:

```bash
docker compose -f docker/docker-compose.yml up -d --build
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

`docker/docker-compose.yml` sets `VUFIND_ENV=production`. The image defaults to `production` as well.

Do not set `VUFIND_ENV=development` for this stack. In development mode, Whoops turns a harmless warning in the Symphony driver into an exception, and every login fails with "We cannot log you in at this time." See `symphony-login-dubugbug.md` in the repository root.

---

## Logs

Application logs go to the volume:

```bash
docker compose -f docker/docker-compose.yml exec vufind tail -f /usr/local/vufind/local/logs/vufind.log
```

Apache access and PHP notices go to the container log:

```powershell
docker compose -f docker/docker-compose.yml logs -f vufind
```

---

## Day-to-Day Commands

```powershell
docker compose -f docker/docker-compose.yml up --watch        # start in foreground, syncing edits
docker compose -f docker/docker-compose.yml up -d --build     # start, full rebuild (after Dockerfile, lock, entrypoint, or config/ changes)
docker compose -f docker/docker-compose.yml stop              # stop, keep data
docker compose -f docker/docker-compose.yml down              # stop and remove the container, keep the volume
docker compose -f docker/docker-compose.yml ps                # status and ports
docker compose -f docker/docker-compose.yml logs -f vufind    # container logs
docker compose -f docker/docker-compose.yml exec vufind bash  # shell in the app container
```

### Starting from zero

`down -v` removes the `vufind_local` volume, which deletes cache, logs, and the stale config copies from earlier versions. The next start regenerates the local state. It does not touch `docker/configs/`:

```powershell
docker compose -f docker/docker-compose.yml down -v
docker compose -f docker/docker-compose.yml up -d --build
```

This does not touch the team database or Solr. Use it only for a full local reset, not for ordinary source edits.

---

## How the Stack Is Built

| File | Purpose |
| :--- | :--- |
| `docker/Dockerfile` | App image: PHP 8.3 and Apache, required extensions (including `xsl`), the repository tree, Composer dependencies, Apache site config |
| `docker/entrypoint.sh` | Startup: prepares writable local state (cache, logs), then starts Apache |
| `docker/docker-compose.yml` | The app service, port mapping, the config bind mount, watch rules, and the `vufind_local` volume |
| `docker/configs/` | Hand-copied `config.ini` and `Symphony.ini` (gitignored). Bind-mounted into the container |
| `config/vufind/httpd-vufind.conf` | Apache rules for theme assets and routing, applied at image build |
| `.dockerignore` | Keeps `docker/configs`, `docker/*.md`, local state, and vendor output out of the build |

Named volume: `vufind_local`.

The image does not contain `docker/configs/`, because `.dockerignore` excludes it. The config reaches the container only through the bind mount.

---

## Troubleshooting

**The page does not load at `http://localhost:8081/`.** Check `docker compose -f docker/docker-compose.yml ps` and `docker compose -f docker/docker-compose.yml logs -f vufind`.

**Compose fails with a missing file under `docker/configs/`.** Copy `config.ini` and `Symphony.ini` into that folder.

**"Session validation failed" in the browser.** The browser holds a `VUFIND_SESSION` cookie from an earlier run. Clear cookies for `localhost` and `127.0.0.1`, or use a private window.

**"An error has occurred" with a Library Reference contact.** Production mode hides details. Check `local/logs/vufind.log` for the exception. Also check that the browser's cookies don't point at a theme that isn't in this checkout. Clearing cookies fixed this once.

**"We cannot log you in at this time."** Check that `VUFIND_ENV` is `production`. Check that `[WebServices] baseURL` in `Symphony.ini` answers (`curl http://<host>:8080/symws/` should return 200). Check `healthCheckId` in `config.ini` names a record that exists in the team index.

**Search returns no results or errors.** Check `[Index] url` in `config.ini`. The stack does not run its own Solr.

**Record page returns 500 with a SOAP error.** Check `baseURL` in `Symphony.ini` for stray spaces. The Symphony driver fails on `http:// host`.

**Config change does not appear.** Check that watch is running and that its terminal shows a sync for `docker/configs`. Restart watch if you edited the compose file, since watch reads it at startup. If the page still shows the old value, clear the cache by hand (see Changing Configuration).

**Changes to code do not appear.** Check the watch terminal for sync errors. For changes outside `module/` and `themes/`, or after editing `composer.lock`, `config/`, `docker/entrypoint.sh`, or `docker/Dockerfile`, rebuild the image.

**Port already in use.** Something else holds 8081. Change the left-hand port in `docker/docker-compose.yml`.
