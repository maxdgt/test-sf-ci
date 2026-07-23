# syntax=docker/dockerfile:1

# ──────────────────────────────────────────────────────────────
# Web image — nginx mainline. Serves static files from public/ and
# proxies PHP requests to the FPM container (service "app":9000).
# ──────────────────────────────────────────────────────────────
FROM nginx:1.31.3

# curl for the container healthcheck.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Static assets nginx serves directly. PHP files are proxied to FPM,
# so only public/ is needed here.
COPY public /app/public

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost:80/ || exit 1
