# Migration Guide (From 5.1 to 6.0)

PuMuKIT 6.0 has **two independent parts**:

1. **Symfony core**: `5.4` (end of life) → `7.4` LTS on **PHP 8.5**, plus the dependency bumps it
   unblocked (api-platform 5, MonologBundle 4, knp-menu 3, tus-php 2, pagerfanta 4, DoctrineMongoDBBundle 5,
   etc.).
2. **MongoDB stack**: server `5.0` → `9.0`, `ext-mongodb` `1.14.2` → `2.5.5`,
   `mongodb/mongodb` `1.13` → `2.5` and Doctrine ODM `2.6` → `2.17`.

This guide describes the **exact, reproducible steps** so it can be replayed on any
deployment. PuMuKIT is **Docker-only** from 6.0; all commands assume the Docker Compose
setup of this repository, with services `php` (PHP-FPM) and `db` (MongoDB).

> Run database commands against the `db` container and application commands inside the
> `php` container.

---

## Application changes (Symfony 7.4)

Checking out 6.0.x gives you the updated `composer.json` and all the internal Symfony 7.4
changes for free. What you have to do is **adapt the customizations and configuration that
are not part of the core**. The items below are the ones that affect a typical 5.1.x
deployment:

- **Mail — Swiftmailer removed.** Mail now goes exclusively through `symfony/mailer`.
  Configure the transport via the **`MAILER_DSN`** environment variable (e.g.
  `smtp://user:pass@host:port` or `null://null` to disable). The old `swiftmailer.yaml`
  and the `PUMUKIT_MAILER_*` variables are no longer used.
- **Ingest command option renamed.** `--profile` collided with Symfony 6.1+'s global
  `--profile` option and was renamed to **`--encoding-profile`** in
  `pumukit:import:inbox` and `pumukit:import:multimedia:file`. Update any cron jobs or
  upload scripts that pass `--profile`.
- **Security configuration format.** The auth layer moved from Guard to the Symfony
  authenticator system. The shipped config needs no action, but if you **customized
  `security.yaml`** you must port it to the new keys (`custom_authenticators`,
  `password_hashers`, `PUBLIC_ACCESS` instead of `guard`, `encoders`,
  `anonymous`/`IS_AUTHENTICATED_ANONYMOUSLY`).
- **Logging.** MonologBundle 4 removes some options and handler types. If you override the
  `monolog` configuration, replace `excluded_404s` with `excluded_http_codes` and do not use
  the `elasticsearch`, `mongo`, `sentry` or `raven` handler types (see
  [UPGRADE-6.0.md](../../UPGRADE-6.0.md#dependencies)).
- **Other local customizations.** Any code or config you maintain on top of the core
  (overridden templates, services, controllers) should be reviewed against the Symfony 7.4 APIs.
  The code-level changes are listed in [UPGRADE-6.0.md](../../UPGRADE-6.0.md).

- **PHP 8.5.** The `php` image is built from `php:8.5-fpm` (it was `8.2`) and is rebuilt
  in step A.2 / B.2. If you mount your own `php.ini`, remove `session.sid_length` and
  `session.sid_bits_per_character` (deprecated) and `disable_classes` (removed in PHP 8.5).
  New session IDs use the PHP defaults (32 characters); existing sessions stay valid.

Informational (no action required):

- **REST API.** The legacy controller API (`/api/media/*`, etc.) is kept as-is. 6.0 adds a
  new versioned, read-only api-platform 5 API under **`/api/v1`** (Swagger/ReDoc at
  `/api/v1/docs`).
- **Frontend assets** are served through Symfony AssetMapper. The Docker image runs
  `assets:install` and `bin/console asset-map:compile`; deployments built outside the image
  must run both. Overridden templates that load core libraries are listed in
  [UPGRADE-6.0.md](../../UPGRADE-6.0.md#frontend-assets). An overridden portal layout
  (`templates/bundles/PumukitWebTVBundle/Layout/template.html.twig`) must render
  `{{ importmap('pumukit') }}` in its `javascripts` block; run `bin/console pumukit:check:assets`
  to find what has to be adapted.

---

## Docker image and services

- **Debian**: `12 (bookworm)` → `13 (trixie)` (`SO_VERSION` of the `Dockerfile`). Debian 12
  only gets LTS updates since June 2026.
- **FFmpeg**: `6.0` → `8.1` (`FFMPEG_VERSION`). The FFmpeg builds from 7.1 on need the C library
  of Debian 13, so both are upgraded together; FFmpeg 9 does not run on Debian 13.
  The encoder profiles and commands of the core give the same results. Review the
  `encoder.yaml` of your project if it defines its own profiles: FFmpeg 7.0 removed the
  `-map_channel` and `-psnr` options, and FFmpeg 7.1 changed the stream specifiers
  (`:m:key:value` needs `\:` for colons in the key or value, and an optional `-map` needs
  `:?` instead of `?`).
- **ImageMagick** (Debian package): `6.9` → `7.1`. The `convert` command still works. A custom
  `policy.xml` moves from `/etc/ImageMagick-6/` to `/etc/ImageMagick-7/`.
- **darktable** (Debian package, RAW pictures): `4.2` → `5.0`, with the same command line.
- **NGINX**: `1.25` → `1.30`, the stable branch (`NGINX_VERSION`; 1.25 has had no fixes since
  May 2024). Upgrade it to the next stable branch every April. The configuration of the image
  (`docker/nginx/default.conf.template`) enables HTTP/2 with `http2 on;` instead of the deprecated
  `listen … http2`. If you keep your own NGINX configuration, check these changes from 1.25:
  TLS 1.0 and 1.1 are disabled by default (1.27.3), the `Host` header and the request line
  are validated as in RFC 3986 (1.29.4), and `proxy_pass` keeps upstream connections alive,
  uses HTTP/1.1 and no longer sends the `Connection` header by default (1.29.7). PuMuKIT talks
  to PHP-FPM through `fastcgi_pass`, which these changes do not affect.
- **Redis** → **Valkey 9.1** (`valkey/valkey:9.1` in `docker-compose.yml`). The `redis` image
  had no version, so every pull could jump to a new major (Redis 8 changed its license to
  AGPLv3/RSALv2/SSPLv1). Valkey is the BSD-licensed continuation of Redis 7.2: same protocol,
  port and `REDIS_*` variables, and its image also ships `redis-server` and `redis-cli`.
  PuMuKIT only keeps the state of resumable uploads in it, without a volume, so there is
  nothing to migrate: change `image: redis` to `image: valkey/valkey:9.1` in your deployment
  compose file and recreate the container outside upload hours (uploads in progress are lost,
  as with any restart). Keeping Redis also works; in that case pin its version.
- **RabbitMQ 4.4** (new, `rabbitmq:4.4-management`): the message broker of Symfony Messenger.
  The core declares no queue yet; the bundles that use it declare their own. Add the service to
  your deployment compose file with a fixed `hostname` and a volume for `/var/lib/rabbitmq`
  (RabbitMQ keeps its data under the node name, which comes from the host name), as in
  `docker-compose.yml`, and set in `.env.local`:

  ```
  RABBITMQ_DEFAULT_USER=<user>
  RABBITMQ_DEFAULT_PASS=<password>
  MESSENGER_TRANSPORT_DSN=amqp://<user>:<password>@rabbitmq:5672/%2f/messages
  ```

  The user is only created on the first start, with an empty volume; change it later with
  `rabbitmqctl`. The management interface (port 15672) is not published. Only the latest
  RabbitMQ minor version gets community fixes, so upgrade it when a new one is released.
- **File delivery through NGINX**: `/trackfile/` still checks the token and the permissions in
  PHP, and then hands the file to NGINX with `X-Accel-Redirect` instead of sending every byte
  from PHP-FPM. NGINX serves it from the internal `/internal-storage/` location (ranges,
  `forcedl=1` downloads and the same headers as before), so a video no longer keeps a PHP-FPM
  worker busy while it plays. The NGINX configuration is now a template,
  `docker/nginx/default.conf.template`, that the `proxy` container renders when it starts:
  the storage path comes from `PUMUKIT_STORAGE_DIR`, the same variable PHP reads from `.env`,
  so a project with its own storage directory needs no change in it. Write the path without a
  trailing slash. The `proxy` container must see the storage at that same path: in production,
  mount the storage in both the `php` and the `proxy` services, as the commented volumes of
  `docker-compose.yml` show. Otherwise NGINX answers 404 for the files, instead of sending them
  slowly from PHP. Files outside `PUMUKIT_STORAGE_DIR` keep being sent by PHP. Projects with
  their own NGINX configuration keep the previous behaviour until they add the internal
  location and these two parameters to the PHP-FPM location, with their storage path:

  ```nginx
  location ^~ /internal-storage/ {
      internal;
      alias /srv/pumukit/public/storage/;
  }

  fastcgi_param HTTP_X_SENDFILE_TYPE X-Accel-Redirect;
  fastcgi_param HTTP_X_ACCEL_MAPPING /srv/pumukit/public/storage/=/internal-storage/;
  ```

  NGINX replaces any `X-Sendfile-Type` or `X-Accel-Mapping` header sent by the client with
  these values. A separate download server (`VOD_HOSTNAME` and the `url_out` of the encoder
  profiles) keeps working as before: those URLs do not go through `/trackfile/`.

Rebuild the image (`docker compose build --pull`); no data has to be migrated.

---

## MongoDB stack upgrade

- **MongoDB server**: `5.0` → `9.0` (must be upgraded one major at a time).
- **PHP driver** (`ext-mongodb`): `1.14.2` → `2.5.5`. The 2.x line is the one officially
  supported on PHP 8.5, and it supports MongoDB servers from 4.2 to 9.0.
- **PHP MongoDB library** (`mongodb/mongodb`): `1.13` → `2.5` (Doctrine ODM `2.6` → `2.17`).
- The denormalized **search text index must be rebuilt** after upgrading existing data.

The 6.0 code already ships the final values (`docker-compose.yml` with `mongo:9.0`, the
`Dockerfile` with `ext-mongodb 2.5.5`). You only need to align your **running
containers and data** with that code.

Pick your scenario:

- **[A. Fresh installation](#a-fresh-installation-no-existing-data)** — no existing data.
- **[B. Upgrade from an existing 5.1.x](#b-upgrade-from-an-existing-51x)** — you have data.

---

## A. Fresh installation (no existing data)

A new install has no data, so there are **no sequential server hops and no text-index
backfill** — you go straight to the values the 6.0 code ships.

```bash
# 1. Get the 6.0 code
git clone <repository-url> pumukit && cd pumukit
git checkout 6.0.x

# 2. Build and start (ships mongo:9.0 and ext-mongodb 2.5.5)
docker compose build
docker compose up -d

# 3. Install dependencies
docker compose exec php composer install

# 4. Create the database schema and indexes
docker compose exec php php bin/console doctrine:mongodb:schema:update

# 5. Clear cache
docker compose exec php php bin/console cache:clear
```

Continue with the standard first-run setup (`.env.local`, admin user, etc.) as described
in the project README. You are done — skip section B.

---

## B. Upgrade from an existing 5.1.x

**Order matters.** Back up first, switch to the 6.0 code, upgrade the **driver** while the
server is still on 5.0 (the old `1.14` driver only supports MongoDB up to 6.0, so it must
be raised to `2.5` before the server reaches 7.0 and later), and only then upgrade the **server**
in hops. Finally rebuild the indexes.

### B.0 Backup (mandatory)

Feature Compatibility Version (FCV) cannot be downgraded once raised, so the backup is the
only rollback path for the server upgrade.

```bash
# Dump the database
docker compose exec db mongodump --out=/data/db/backup-5.1
docker compose cp db:/data/db/backup-5.1 ./backup-5.1

# And/or snapshot the data volume while the stack is stopped
docker compose down
docker run --rm -v "$(docker volume ls -q | grep db-data)":/data -v "$PWD":/backup \
  busybox tar czf /backup/db-data-5.1.tar.gz -C /data .
docker compose up -d db
```

### B.1 Get the 6.0 code

```bash
git fetch origin
git checkout 6.0.x
```

> This updates `docker-compose.yml` to `mongo:9.0` and the `Dockerfile` to
> `ext-mongodb 2.5.5`. **Do not** recreate the `db` container yet: your data volume still
> holds 5.0 data and a 9.0 server would refuse to start on it. Leave `db` running on its
> current 5.0 container until step **B.3**.

Now apply the **[application changes](#application-changes-symfony-74)**: confirm you are
not blocked by SSO, set `MAILER_DSN`, update any ingest scripts using `--profile`, and
review custom `security.yaml` overrides.

### B.2 Upgrade the PHP driver and dependencies (server still on 5.0)

Driver `2.5` is backward compatible with server `5.0`, so the app boots before the server
upgrade.

```bash
# Rebuild ONLY the php service (do not touch db yet)
docker compose build php
docker compose up -d php

# Verify the driver
docker compose exec php php --ri mongodb | grep "MongoDB extension version"   # -> 2.5.x

# Install the 6.0 dependencies (mongodb/mongodb 2.5)
docker compose exec php composer install --no-dev --optimize-autoloader
```

At this point you are running **6.0 code + driver 2.5 + server 5.0**, which is a supported
combination.

### B.3 Upgrade the MongoDB server (5.0 → 6.0 → 7.0 → 8.0 → 9.0)

You **cannot skip major versions**. The 6.0 code ships `mongo:9.0`, but to migrate existing
5.0 data you must pass through each intermediate image. Pin the current FCV first:

```bash
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "5.0" })'
```

Then repeat this cycle for each hop — `6.0`, then `7.0`, then `8.0`, then `9.0`. Temporarily
set the `db` image for the intermediate hops (the final `9.0` already matches the committed
file):

```yaml
  db:
    image: mongo:6.0   # hop 1; then 7.0 and 8.0 for hops 2 and 3; leave 9.0 (committed) for hop 4
```

> **Hosts with Linux kernel 6.19 or newer.** The `mongo:8.0` image (8.0.21 to 8.0.34) refuses
> to start on these kernels ("MongoDB cannot start: Linux kernel versions 6.19 and newer has a
> known incompatibility", [SERVER-121912](https://jira.mongodb.org/browse/SERVER-121912)).
> MongoDB 9.0 is not affected. For the 8.0 hop only, bypass the check by overriding the `db`
> service, e.g. in `docker-compose.override.yml`:
>
> ```yaml
>   db:
>     entrypoint: ["setarch", "--uname-2.6", "docker-entrypoint.sh"]
>     command: ["mongod"]
> ```
>
> Remove the override before the 9.0 hop. Check the kernel with `uname -r`.

```bash
# Recreate only the db container with the new image
docker compose up -d db

# Wait until it accepts connections
until docker compose exec db mongosh --quiet --eval 'db.runCommand({ ping: 1 })' >/dev/null 2>&1; do sleep 2; done
```

Set FCV for the new version. **From 7.0 onwards `setFeatureCompatibilityVersion` requires
`confirm: true`.**

```bash
# after hop to 6.0
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "6.0" })'

# after hop to 7.0
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "7.0", confirm: true })'

# after hop to 8.0
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "8.0", confirm: true })'

# after hop to 9.0
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "9.0", confirm: true })'
```

Verify after each hop (final state must be server `9.0.x`, FCV `9.0`):

```bash
docker compose exec db mongosh --quiet --eval \
  'print(db.version()); db.adminCommand({ getParameter: 1, featureCompatibilityVersion: 1 })'
```

After the last hop, make sure `docker-compose.yml` is back to the committed `image:
mongo:9.0` (no leftover local edit).

### B.4 Update the database indexes

```bash
docker compose exec php php bin/console doctrine:mongodb:schema:update
```

### B.5 Rebuild the search text index

The text search relies on a denormalized `textindex` field. Regenerate it for all existing
Multimedia Objects and Series:

```bash
docker compose exec php php bin/console pumukit:textindex:rebuild
```

### B.6 Create the new base tags

PuMuKIT 6.0 adds the `PLACES` root tag used by the places and precincts menu. The command
only creates the tags that do not exist yet; it does not change the existing ones:

```bash
docker compose exec php php bin/console pumukit:init:repo tag
```

### B.7 Build the daily view statistics

The statistics panel reads daily summaries that every new view updates. Build them once from the
views already stored in `ViewsLog` (after B.4, which creates their indexes). It processes one day
at a time, can run while the platform is in use and, if it is stopped, continues from the last day
built when it is run again:

```bash
docker compose exec php php bin/console pumukit:stats:rollup
```

As a reference, two million views (about 500 MB of `ViewsLog`) take less than a minute.

### B.8 Clear the cache

```bash
docker compose exec php php bin/console cache:clear
```

---

## Verification checklist

- `db.version()` reports `9.0.x` and FCV is `9.0`.
- `php --ri mongodb` reports `2.5.x`.
- The backoffice (`/admin`) and the public portal load correctly.
- Searching existing content by title returns results (text index rebuilt).
- File upload (inbox) and video playback work.

## Rollback (existing installations)

- **Application/driver**: `git checkout` the 5.1 code and rebuild the `php` image
  (`docker compose build php && docker compose up -d php`), then `composer install`.
- **Database**: the server upgrade is **not reversible** once FCV is raised. Restore the
  backup from step B.0 onto a `mongo:5.0` container:

```bash
docker compose down
# Restore the volume snapshot, or restore the dump:
docker compose up -d db   # with image: mongo:5.0
docker compose cp ./backup-5.1 db:/data/db/backup-5.1
docker compose exec db mongosh --quiet --eval 'db.dropDatabase()'
docker compose exec db mongorestore /data/db/backup-5.1
```
