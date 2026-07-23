# Deployment

How to run the images the pipeline publishes to Docker Hub
(`<username>/symfony-app` and `<username>/symfony-web`) on a server.

The CI publishes two tags per image on every push to `main`:

- `:<git-sha>` — immutable, tied to one commit. **Deploy this.**
- `:latest` — moves with `main`. Handy, but not pinnable.

Always deploy a specific `:<git-sha>` so a host restart can never silently pull
a different build.

## Prerequisites

- Docker Engine + Compose plugin on the server.
- Access to the images. If the Docker Hub repos are **private**, log in first:

  ```bash
  docker login -u <username>            # paste a Read-only access token
  ```

## Production compose file

Create `compose.prod.yaml` on the server. Unlike the build-time `compose.yaml`,
this one **pulls prebuilt images** instead of building:

```yaml
services:
  app:
    image: <username>/symfony-app:${APP_VERSION:?set APP_VERSION to a git sha}
    restart: unless-stopped
    environment:
      APP_ENV: prod
      APP_DEBUG: "0"
      APP_SECRET: ${APP_SECRET:?set APP_SECRET}
    # No published ports: only web talks to FPM over the internal network.

  web:
    image: <username>/symfony-web:${APP_VERSION:?set APP_VERSION to a git sha}
    restart: unless-stopped
    depends_on:
      - app
    ports:
      - "80:80"        # front with a TLS-terminating reverse proxy in real prod
```

Provide the values via a `.env` file next to it (never commit this):

```dotenv
APP_VERSION=6a63ead0000000000000000000000000000000
APP_SECRET=<a-long-random-string>
```

> Generate a secret: `php -r 'echo bin2hex(random_bytes(16));'` or
> `openssl rand -hex 16`.

## Deploy

```bash
docker compose -f compose.prod.yaml pull
docker compose -f compose.prod.yaml up -d
```

Verify:

```bash
docker compose -f compose.prod.yaml exec -T app php bin/console about
curl -fsS http://localhost/health      # {"status":"ok"}
```

## Update to a new release

The `app` and `web` images share the same `:<git-sha>` per commit, so one
variable bumps both:

```bash
# set APP_VERSION to the new commit sha in .env, then:
docker compose -f compose.prod.yaml pull
docker compose -f compose.prod.yaml up -d      # recreates changed containers only
```

## Roll back

Point `APP_VERSION` back at the previous good sha and re-run the two commands
above. Because tags are immutable, the old image is exactly what shipped before.

```bash
# .env: APP_VERSION=<previous-good-sha>
docker compose -f compose.prod.yaml up -d
```

## Notes

- **TLS**: the `web` image serves plain HTTP on :80. Terminate TLS at a reverse
  proxy (Traefik, Caddy, nginx, or a cloud load balancer) in front of it.
- **Secrets**: inject `APP_SECRET` at runtime (env / secrets manager). Never bake
  it into the image or commit it.
- **Zero-downtime**: `up -d` recreates containers with a brief blip. For no blip,
  run behind a proxy and roll instances, or use an orchestrator.

## Related

- Publishing setup: [`secrets.md`](secrets.md)
- Cost model: [`pricing.md`](pricing.md)
- Build-time stack: [`../compose.yaml`](../compose.yaml)
