# CI Secrets & Environments

How to configure the credentials the pipeline (`.github/workflows/ci.yml`)
needs to publish images to Docker Hub. Until these are set, the publish steps
are **skipped** and the pipeline stays green.

## Required secrets

| Secret | Value | Used by |
| --- | --- | --- |
| `DOCKERHUB_USERNAME` | Your Docker Hub username | `docker/login-action`, image names |
| `DOCKERHUB_TOKEN` | A Docker Hub **access token** (not your password) | `docker/login-action` |

The workflow guards on `DOCKERHUB_TOKEN`:

```yaml
- name: Log in to Docker Hub
  if: env.DOCKERHUB_TOKEN != ''
  ...
```

So publishing turns on automatically the moment the secret exists — no code
change needed.

## 1. Create a Docker Hub access token

1. Sign in to <https://hub.docker.com>.
2. **Account Settings → Security → New Access Token**.
3. Description: `github-actions-test-sf-ci`.
4. Access permissions: **Read & Write**.
5. **Generate** and copy the token now — it is shown only once.

> Use a scoped access token, never your account password. Revoke it from the
> same screen if it leaks, without touching your password.

## 2. Add the secrets to GitHub

### Option A — Web UI

1. Repo → **Settings → Secrets and variables → Actions**.
2. **New repository secret** → add `DOCKERHUB_USERNAME`.
3. **New repository secret** → add `DOCKERHUB_TOKEN`.

### Option B — GitHub CLI

```bash
gh secret set DOCKERHUB_USERNAME --repo maxdgt/test-sf-ci --body "your-dockerhub-username"
gh secret set DOCKERHUB_TOKEN   --repo maxdgt/test-sf-ci   # prompts for the value (hidden)
```

Verify:

```bash
gh secret list --repo maxdgt/test-sf-ci
```

## 3. (Optional) Use a GitHub Environment

Repository secrets are the simplest setup. Use an **Environment** when you want
per-stage credentials (e.g. `staging` vs `production`) or an approval gate
before publishing.

1. Repo → **Settings → Environments → New environment** (e.g. `production`).
2. Add the same secrets **scoped to that environment**.
3. Optionally add **Required reviewers** to gate the publish step.
4. Reference it in the job:

```yaml
  package:
    name: Package (Docker)
    environment: production   # pulls that environment's secrets + gates
    ...
```

Environment secrets override repository secrets for jobs that declare the
environment. A protected environment pauses the run until an approver clicks
**Approve**, which is useful before pushing `:latest`.

## Security notes

- Secrets are **encrypted** and masked in logs; they are **not** exposed to
  workflows triggered by forked-PRs.
- Rotate the Docker Hub token periodically; update only `DOCKERHUB_TOKEN`.
- Never commit tokens to the repo or `.env` files — CI reads them from secrets
  only.

## Related

- Pipeline cost model: [`pricing.md`](pricing.md)
- Workflow: [`../.github/workflows/ci.yml`](../.github/workflows/ci.yml)
