# CI Pricing — GitHub Actions

Cost reference for this project's CI (`.github/workflows/ci.yml`). Rates
verified against the [GitHub Actions billing docs](https://docs.github.com/en/billing/managing-billing-for-your-products/about-billing-for-github-actions).

## Included free minutes (per account, per month)

| Plan | Minutes |
| --- | --- |
| Free | 2,000 |
| Pro | 3,000 |
| Team | 3,000 |
| Enterprise Cloud | 50,000 |

The allowance is **per account, shared across all private repos** — not per repo.

## Standard runner rates (after the allowance, private repos only)

| Runner | Per minute |
| --- | --- |
| Linux 2-core (x64) | $0.006 |
| Linux 2-core (arm64) | $0.005 |
| Windows 2-core | $0.010 |
| macOS 3–4 core | $0.062 |

- **Public repositories: free and unlimited** on standard runners.
- **Larger runners are billed even on public repos** — we use standard
  `ubuntu-latest` (x64), so this does not apply.
- Standard **Actions cache is free up to 10 GB per repo** (LRU-evicted, no
  overage billing).

## Cost of this pipeline

Billed at roughly **3 minutes per push to `main`** (Test ~1 min + Package ~2 min),
each rounded up per job. Pull requests run **Test only (~1 min)**.

| Total private minutes / month | Overage | Cost / month (x64) |
| --- | --- | --- |
| ≤ 3,000 | 0 | $0 |
| 10,000 | 7,000 | ~$42 |
| 30,000 | 27,000 | ~$162 |

Overage = `(minutes − 3,000) × $0.006`. Add the base plan (Pro $4/mo, or
Team $4/user/mo) that grants the 3,000 minutes.

## Cost controls in place

- **`concurrency: cancel-in-progress`** — superseded runs are cancelled, not billed.
- **`Package` job runs only on push to `main`** — PRs skip the Docker build.
- **GHA layer cache** — unchanged `composer install` / base layers are restored,
  not rebuilt (free, within the 10 GB cache limit).
- **`[skip ci]`** in a commit message skips the whole run (0 minutes) — a
  manual, per-commit escape hatch.
- **`paths-ignore`** on the push trigger (`**.md`, `docs/**`) skips docs-only
  pushes to `main` automatically — no commit-message tag needed.

## Manual step (private repos)

GitHub defaults the Actions **spending limit to $0**, which blocks runs once the
free minutes are exhausted. Raise it deliberately (e.g. $50) under
**Settings → Billing → Spending limits** to allow paid minutes with a safe cap.
