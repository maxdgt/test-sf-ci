# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Symfony **7.4 LTS** application (skeleton) running on **PHP 8.4**. The
interesting part of this repo is not the app code (a handful of JSON
controllers) but the **production Docker packaging and the GitHub Actions
pipeline** — that is where most work and most of the design decisions live.

## Common commands

```bash
# Dependencies
composer install

# List all routes / inspect the app
php bin/console debug:router
php bin/console about

# Lint (what CI runs)
composer validate --strict --no-check-publish   # --no-check-publish: app, not a package
php bin/console lint:yaml config --parse-tags
php bin/console lint:container

# Tests — no suite is installed yet. To enable PHPUnit:
composer require --dev symfony/test-pack
vendor/bin/phpunit                               # all
vendor/bin/phpunit --filter testName             # single test

# Run the full production stack locally (nginx + php-fpm), serves on :8080
docker compose up --build -d
curl localhost:8080/health                        # {"status":"ok"}
docker compose down
```

> **Local PHP quirk:** the host PHP prints `amqp.so` / `yaml.so` "Unable to
> load dynamic library" warnings on every command — these are broken PECL
> entries in the host config, unrelated to this project. Ignore them; grep them
> out when parsing command output.

## Architecture

**Two-image production topology** (not a single container):

- **`Dockerfile`** builds the *app* image from `php:8.4.3-fpm-bookworm`. It is
  multi-stage: `base` (extensions + composer) → `builder` (installs prod-only
  deps, dumps a classmap-authoritative autoloader, warms the prod cache) →
  `runtime` (minimal, non-root `www-data`, serves FastCGI on `:9000`).
- **`docker/nginx/Dockerfile`** builds the *web* image from `nginx:1.31.3`. It
  serves static files from `public/` and proxies PHP to the app container.
- **`compose.yaml`** wires them: `web` (published `:8080→80`) in front of `app`
  (internal only). `docker/nginx/default.conf` sends PHP to `app:9000` and
  hardcodes `SCRIPT_FILENAME /app/public/...` — the path as seen **inside the
  FPM container**, not on the nginx side. Both containers must agree on
  `/app/public`.
- **Base images are pinned to exact patch versions** on purpose (reproducible
  builds). Bumping them re-seeds the CI layer cache, so the next build is slow.
- **OPcache preload** is enabled (`docker/php/conf.d/app.prod.ini` →
  `config/preload.php`) with `validate_timestamps=0`, assuming an immutable
  image. `preload_user` must stay `www-data`.

**Routing:** `config/routes.yaml` auto-imports `#[Route]` attributes from
`src/Controller/`. Controllers extend `AbstractController` and return
`JsonResponse`. Add a controller there and its routes register automatically —
verify with `php bin/console debug:router`.

## CI pipeline (`.github/workflows/ci.yml`)

Flow: **Push → Test → Package → Publish**. Key behaviors to preserve when
editing:

- **`Package` (Docker) runs only on `push` to `main`** (`if:
  github.event_name == 'push'`); PRs run `Test` alone to save minutes.
- **`bake` builds both images with a per-image GHA layer cache** (`scope=app` /
  `scope=web`) — do not collapse the scopes or they clobber each other.
- **Publishing to Docker Hub is guarded** on `if: env.DOCKERHUB_TOKEN != ''`, so
  the pipeline stays green until the `DOCKERHUB_USERNAME` / `DOCKERHUB_TOKEN`
  secrets are set. It pushes `:latest` and `:<git-sha>` for both images, only
  after the smoke test passes.
- **Cost controls:** `concurrency: cancel-in-progress`, and docs-only pushes are
  skipped via `paths-ignore` (`**.md`, `docs/**`). A `[skip ci]` / `[ci skip]`
  tag in a commit message skips any run manually.

The `runtime` image is smoke-tested in CI by booting the stack and asserting
`/` returns 200 or 404 (both prove the app booted and routed) plus
`bin/console about`.

## Docs

`docs/` documents the operational side: `pricing.md` (CI cost model),
`secrets.md` (configuring Docker Hub credentials / GitHub Environments),
`deploy.md` (running published images in prod via a pull-based
`compose.prod.yaml`, deploy/rollback by immutable sha). Update these when
changing the pipeline or deployment model.
