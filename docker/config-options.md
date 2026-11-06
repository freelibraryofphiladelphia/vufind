# Options for getting config and secrets into a container (non-Swarm)

Based on the current Docker Compose docs. Options that need Swarm or an external platform are marked.

## 1. Read-only bind mount

```yaml
volumes:
  - ./docker/configs:/run/vufind-config:ro
```

- Mounts a host folder into the container. The files never enter the image.
- Simple. Anyone who can read the host folder can read the files.
- Good for non-secret config, or for secrets when the host folder is locked down.

## 2. Compose `configs:`

Sources, from the docs:

- **`file:`** creates the config from a host file.
- **`content:`** inlines the text in the compose file. Needs Compose 2.23.1 or later.
- **`environment:`** takes the value from a host environment variable. Needs Compose 2.23.1 or later.
- **`external: true`** refers to an object made with `docker config create`. That is a Swarm-style object, so skip it here.

Mounted at `/<name>` by default, read-only, mode `0444`, so every user in the container can read it. Use it for non-secret config only.

## 3. Compose `secrets:`

Sources:

- **`file:`** creates the secret from a host file.
- **`environment:`** takes the value from a host environment variable. Compose only, not `docker stack deploy`.
- **`external: true`** refers to an object made with `docker secret create`. Swarm-style, so skip it here.

Mounted at `/run/secrets/<name>`, and only the services you list can see it. Not part of the image, and not shown in `docker inspect` the way environment variables are. This is the right tool for the database password, the Symphony login and password, and the keys.

## 4. Environment variables (`environment:` or `env_file:`)

- Simple, and visible through `docker inspect` and to any process in the container.
- Fine for non-sensitive values. Avoid for passwords.

## 5. Compose variable substitution (`.env` file)

- `${VAR}` inside `docker-compose.yml` is filled in from `.env` or the shell.
- Compose uses the values itself. Containers only see what a service declares.
- Useful together with `content:` (option 2) or `environment:` (option 3).

## 6. Build-time secrets (BuildKit `--secret`)

- Available only during `docker build`, for things like a private package token.
- Not available at run time, so it doesn't help with the app's config.

## 7. Entrypoint fetches from an external tool

- Examples: 1Password CLI (`op run`), SOPS-encrypted files, Vault, cloud secret managers.
- The container gets secrets from a service at start-up. Needs network access and credentials for that service.
- Strong option if the team already uses one.

## 8. Manual seeding of a named volume

- Copy files in once with `docker compose cp`.
- Works, but nothing is repeatable, and the files are not declared anywhere in the project.

## Suggested combination for this stack

- **`configs:` (`file:`)** for the non-secret settings: `config.ini` and `Symphony.ini`, once the secret values are split out.
- **`secrets:` (`file:` or `environment:`)** for the database password, the Symphony login and password, and the `HMACkey` and ILS encryption key.
- The entrypoint copies the config into `vufind_local` and fills in the secret values from `/run/secrets/`.

## Open questions to test

- Whether Compose updates a running container when a `configs:` or `secrets:` source file changes. The docs don't say.
- Which Compose version is installed. `content:` and `environment:` for configs need 2.23.1 or later.
