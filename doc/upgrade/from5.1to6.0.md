# Migration Guide (From 5.1 to 6.0)

PuMuKIT 6.0 has **two independent parts**:

1. **Symfony core**: `5.4` (end of life) → `7.4` LTS on **PHP 8.5**, plus the dependency bumps it
   unblocked (api-platform 5, MonologBundle 4, knp-menu 3, tus-php 2, pagerfanta 4, DoctrineMongoDBBundle 5,
   etc.).
2. **MongoDB stack**: server `4.x`/`5.0` → `9.0`, `ext-mongodb` `1.14.2` → `2.5.5`,
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
- **Log retention.** Every rotating log, including those of the bundles, keeps `PUMUKIT_LOG_MAX_FILES` daily
  files, 15 by default. Older files are removed at the next rotation. If you keep your own `monolog.yaml`, use
  `max_files: '%env(int:PUMUKIT_LOG_MAX_FILES)%'`.
- **Logging.** MonologBundle 4 removes some options and handler types. If you override the
  `monolog` configuration, replace `excluded_404s` with `excluded_http_codes` and do not use
  the `elasticsearch`, `mongo`, `sentry` or `raven` handler types (see
  [UPGRADE-6.0.md](../../UPGRADE-6.0.md#dependencies)).
- **Interface languages.** The interface is translated into `en`, `es`, `ca`, `gl`, `eu` and `val`. The French and
  Chinese (`zh-CN`, `zh-TW`) translations, 7–14 % complete, are removed: with those locales in `PUMUKIT_LOCALES` the
  interface shows in English. The content in those languages (titles, descriptions) is not touched.
- **Personal series.** They are created with the first upload or recording of the user, not on login, and the
  bundles no longer upload to shared series. Remove `default_series_title` from the `pumukit_lms` and
  `pumukit_poddium` configuration and `shared_series_properties` from `pumukit_personal_recorder`: they are rejected.
  See [B.8](#b8-clean-the-personal-series).
- **Doctrine metadata cache.** `Symfony\Component\Cache\DoctrineProvider` no longer exists. If your
  `config/packages/prod/doctrine_mongodb.yaml` defines `doctrine_mongodb.system_cache_provider` with that class, remove
  the service and point `metadata_cache_driver` straight to the cache pool, as the core does:

  ```yaml
  doctrine_mongodb:
    document_managers:
      default:
        metadata_cache_driver:
          type: service
          id: doctrine_mongodb.system_cache_pool
  ```

  With the old service, PuMuKIT fails as soon as it starts in `prod`.
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
  `docker-compose.yml`, and set in the environment of your deployment:

  ```
  RABBITMQ_DEFAULT_USER=<user>
  RABBITMQ_DEFAULT_PASS=<password>
  RABBITMQ_DEFAULT_VHOST=<vhost>
  MESSENGER_TRANSPORT_DSN=amqp://<user>:<password>@rabbitmq:5672/<vhost>/messages
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

## Security changes in the deployment

- **Real client IP.** `TRUSTED_PROXIES` is now empty by default, and NGINX works out the client IP. PHP
  no longer receives the `X-Forwarded-For` header sent by the client, so nobody can choose the IP used for the
  login lock, the download and chat limits, the statistics and the logs. If there is a balancer or another proxy in
  front of the `proxy` container, set its addresses, separated by commas, in `PUMUKIT_REAL_IP_FROM`:

  ```
  PUMUKIT_REAL_IP_FROM=10.0.0.0/8,2001:db8::/32
  ```

  The proxy in front has to send `X-Forwarded-For` and the original `Host`. Do not set `TRUSTED_PROXIES` to wide
  ranges: `10.0.0.0/0` means every IPv4 address. If you keep your own NGINX configuration, make the same change there,
  in every NGINX the request goes through. With `TRUSTED_PROXIES` empty Symfony ignores `X-Forwarded-Proto`, `Host` and
  `Port`, so also send the real values to PHP-FPM:

  ```nginx
  set_real_ip_from 10.0.0.0/8;
  real_ip_header X-Forwarded-For;
  real_ip_recursive on;

  fastcgi_param HTTP_X_FORWARDED_FOR $remote_addr;
  fastcgi_param HTTPS on;
  fastcgi_param SERVER_PORT 443;
  fastcgi_param HTTP_HOST $host;
  ```

- **Login lock in `/api`.** The integrations that call `/api` with HTTP Basic (`-u user:password`) now go through the login lock of
  the form: three wrong passwords within 5 minutes lock the account for 5 minutes, in the API and in the login form, and more than 5
  failures per minute of a user from the same IP are rejected. A locked account gets a `401`, as with a wrong password. An integration
  with a wrong password locks the account it uses every 5 minutes, and a person who mistypes the password in the login form stops
  the integrations that share that account, so give every integration its own account with only the roles it needs. Nothing changes
  for the backoffice, which calls `/api` with its session, nor for the users that log in with CAS, SAML or the LMS.
- **Password hashes.** `security.yaml` hashes the passwords with `auto` (bcrypt, cost 12) and migrates from `sha512` and
  from `md5` (`iterations: 1`, `encode_as_base64: false`): when a user logs in with a password, through the login form
  or `/api`, the hash is rewritten. Users that never log in again keep their old hash and can still log in. `encoders`
  no longer exists in Symfony 7, so every installation rewrites this block; take the one of the core unless a CAS server
  checks the hashes of PuMuKIT (the CAS file of GalicasterMediaServerBundle or `cas.authn.mongo`). That CAS checks MD5,
  so those installations keep their hasher until the CAS accepts bcrypt:

  ```yaml
  password_hashers:
    Symfony\Component\Security\Core\User\UserInterface:
      algorithm: md5
      iterations: 1
      encode_as_base64: false
  ```

  A bcrypt hash takes about 160 ms to check, against 1.5 ms of `sha512`. The integrations that call `/api` with HTTP
  Basic pay it on every request that does not reuse the session cookie. To roll back to 5.1, set the 5.1 hasher to
  `auto` with `migrate_from: [sha512]` too, or the users that logged in to 6.0 will not log in with their password.
- **Iframes.** Only the backoffice (`/admin`) is restricted: it can be embedded from the same site, from the
  `pumukit_lms.domains` of LmsBundle and from the domains of `PUMUKIT_FRAME_ANCESTORS`, separated by spaces (`*` allows any
  site). The player, the live pages, LTI and `/trackfile` send no new header, and the `X-Frame-Options` that NGINX sent
  on every route is gone. When the browser is going to block an iframe of the backoffice, the origin is written to
  `var/log/frame_ancestors-YYYY-MM-DD.log`. If your proxy adds a `Content-Security-Policy` with `frame-ancestors https://*`, it can stay:
  the browser applies both policies. An `X-Frame-Options` in your proxy has to go if you embed anything from another site.
- **Sample secrets.** In production PuMuKIT does not answer requests or run commands with a value that comes from the
  repository in `PUMUKIT_SECRET`, `PUMUKITPLAYER_SECURE_SECRET`, `PUMUKIT_PASS`, `MONGODB_PASSWORD`, `REDIS_PASSWORD` and
  `RABBITMQ_DEFAULT_PASS`, nor with those passwords in `MONGODB_URL` and `MESSENGER_TRANSPORT_DSN`. The commands that build the image
  (`cache:*`, `assets:*`, `importmap:*`, `asset-map:*`) still run. Add this to your deployment pipeline, so that it fails before going live:

  ```bash
  bin/console pumukit:check:secrets --env=prod
  ```

  It also warns if MongoDB has no user or connects as `root` or `admin`, and if Valkey has no password. Use a different
  `PUMUKIT_SECRET` in every installation. Changing it logs out the "remember me" sessions, invalidates the LmsBundle uploads and LTI launches in
  progress, and makes a visitor count twice in the unique visits of that day. Nothing stored in the database depends on it. `APP_SECRET` was not used and is
  gone.
- **Service credentials.** `docker-compose.yml` starts MongoDB with `--auth` and a user with `readWrite` on the database, Valkey with its own user
  (the `default` user is off), and RabbitMQ with its own user and vhost. Set `MONGODB_USER`, `MONGODB_PASSWORD`, `MONGODB_URL`, `REDIS_USER`,
  `REDIS_PASSWORD` and the RabbitMQ variables above. Encode the special characters of the passwords in the URLs.

  ```
  MONGODB_URL=mongodb://<user>:<password>@db:27017/pumukit?authSource=pumukit
  ```

  The users are only created with an empty volume. For an existing volume, before recreating the containers:

  ```bash
  docker compose exec db mongosh pumukit --eval 'db.createUser({user: "<user>", pwd: "<password>", roles: [{role: "readWrite", db: "pumukit"}]})'
  docker compose exec rabbitmq rabbitmqctl add_vhost <vhost>
  docker compose exec rabbitmq rabbitmqctl set_permissions -p <vhost> <user> '.*' '.*' '.*'
  ```

  PHP now sends `REDIS_USER` and `REDIS_PASSWORD` to Valkey, also for the resumable uploads. If you keep your own compose file and run Valkey or Redis
  without a user, leave both empty.
- **Remote encoder.** `docker/remote-encoder/webserver.php` no longer has fixed credentials: set `PUMUKIT_ENCODER_USER` and `PUMUKIT_ENCODER_PASSWORD` in the web
  server of every encoder machine, with the same values as the `user` and `password` of the CPU in `encoder.yaml`, and update the file. It answers 503 without them or with the old password. The
  `host` of a CPU can now include the scheme, as in `https://encoder.example.org`; without one it is still `http://`, and the password travels unencrypted.

- **Download server.** `/download/validate-token` now needs a token that signs the path of the requested file, and the requested URI in the
  `X-Original-URI` header. Before, the token of any video opened every file of the server. Tracks that are redirected to a server with this validation get the new
  token; the links in progress stop working within `PUMUKITPLAYER_SECURE_DURATION`.

  `docker-compose.yml` serves the files through the `dls` service, on its own host, as the deployments of the projects do: the `proxy` sends the host of
  `PUMUKIT_DLS_HOST` (`dls.localhost` in development) to `dls`, which serves `/pumukit` and `/opencast` after asking PHP, with a 10 second cache. The `.env` sets:

  ```
  PUMUKIT_DLS_HOST=dls.localhost
  PUMUKIT_DOWNLOAD_SERVER_URL=https://${PUMUKIT_DLS_HOST}
  PUMUKIT_DLS_CORS_ORIGIN='^https://([a-z0-9-]+\.)?localhost$'
  ```

  and `pumukit.downloads_url` is `https://<PUMUKIT_DLS_HOST>/pumukit`. `/trackfile` checks its token and redirects the tracks stored under
  `PUMUKIT_DOWNLOAD_SERVER_URL` to the server, with a token for that file. With an empty `PUMUKIT_DOWNLOAD_SERVER_URL` nothing is redirected. New
  tracks take `pumukit.downloads_url`; the URL of the existing ones is in the database, so the tracks transcoded before keep their old URL and stay on `/trackfile`.
  The download server is another origin: `PUMUKIT_DLS_CORS_ORIGIN` is the regular expression of the origins allowed to read the files from JavaScript, which the
  thumbnail generator of the backoffice and the HardVideoEditorBundle do. In a real installation, give the server its own DNS name and certificate, and set
  `PUMUKIT_DLS_REAL_IP_FROM` if the network of the `proxy` is not a private range. The `dls` service publishes no port: only the `proxy` reaches it. HLS playlists and their segments stay on `/trackfile`, because the segments of a playlist
  would not carry the token. An HLS track with no path in PuMuKIT cannot be served from a server that validates.

  The images of `/opencast` do not ask for a token, because PuMuKIT stores them as public URLs. To serve Opencast, also mount its storage on `/mnt/storage/opencast`. With validation,
  a saved link to the server stops working: check first which URLs are used from outside (podcast clients, apps, other websites, LMS).

  If you keep your own NGINX, `auth_request` sends no query string in the subrequest: take it from `$request_uri`. Send a `Host` accepted by `TRUSTED_HOSTS`, and the request in `X-Original-URI`:

  ```nginx
  map $request_uri $query_of_the_request {
      default "";
      "~\?(?<query>.*)$" $query;
  }

  location = /_validate_token {
      internal;
      rewrite ^ /download/validate-token?$query_of_the_request? break;
      proxy_pass http://pumukit-frontend;
      proxy_pass_request_body off;
      proxy_set_header Content-Length "";
      proxy_set_header Host pumukit.example.org;
      proxy_set_header X-Original-URI $request_uri;
  }
  ```

---

## MongoDB stack upgrade

- **MongoDB server**: `4.x`/`5.0` → `9.0`, by dump and restore or one major at a time in place.
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

# 4. Create the database indexes, the base tags, roles and permission profiles, and run the setup of the bundles
docker compose exec php php bin/console pumukit:setup

# 5. Clear cache
docker compose exec php php bin/console cache:clear
```

Continue with the standard first-run setup (`.env.local`, admin user, etc.) as described
in the project README. You are done — skip section B.

---

## B. Upgrade from an existing 5.1.x

**Order matters.** Back up first, switch to the 6.0 code, build the new `php` image, move the
data to a MongoDB 9.0 server and finally rebuild the indexes.

There are two ways to move the data to 9.0 (step **B.3**):

- **[Dump and restore](#b3-option-1-dump-and-restore-recommended) (recommended).** Dump the
  database with the tools of the current server and restore it into an empty 9.0 server. It
  works from any 4.x or 5.0 server in a single step, with any storage engine, and needs no
  intermediate images. It was rehearsed with real PuMuKIT 4.x (MongoDB 4.0) and 5.1 (MongoDB
  5.0) databases: same document count in every collection and the same indexes.
- **[In-place upgrade](#b3-option-2-in-place-upgrade-one-major-at-a-time)**, one major version at
  a time (4.0 → 4.2 → 4.4 → 5.0 → 6.0 → 7.0 → 8.0 → 9.0) on the same data volume.

> **MongoDB 5.0 and later need a CPU with AVX** on x86_64. Check the host before you start:
> `grep -c -w avx /proc/cpuinfo` must print a number greater than 0. Virtual machines must expose
> the host CPU flags.

> **Shell of the old images.** The `mongo:4.x` images only include the legacy `mongo` shell;
> `mongosh` is available from `mongo:5.0`. Use `mongo` instead of `mongosh` in the commands below
> while the `db` service still runs a 4.x image.

### B.0 Backup (mandatory)

The backup is the only rollback path for the server upgrade: Feature Compatibility Version
(FCV) cannot be downgraded once raised, and the dump and restore replaces the data volume.

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
> holds 4.x or 5.0 data and a 9.0 server would refuse to start on it. Leave `db` running on
> its current image until step **B.3**.

Now apply the **[application changes](#application-changes-symfony-74)**: confirm you are
not blocked by SSO, set `MAILER_DSN`, update any ingest scripts using `--profile`, and
review custom `security.yaml` overrides.

### B.2 Upgrade the PHP driver and dependencies

```bash
# Rebuild ONLY the php service (do not touch db yet)
docker compose build php
docker compose up -d php

# Verify the driver
docker compose exec php php --ri mongodb | grep "MongoDB extension version"   # -> 2.5.x

# Install the 6.0 dependencies (mongodb/mongodb 2.5)
docker compose exec php composer install --no-dev --optimize-autoloader
```

Driver `2.5` connects to MongoDB **4.2 and later**. On a 4.2, 4.4 or 5.0 server the 6.0 code
runs before the server upgrade. On a **4.0** server it cannot connect ("reports wire version 7,
but this version of libmongoc requires at least 8"): the application is down from here until
B.3 is finished, and the in-place upgrade must do the 4.2 hop before this step.

### B.3 Option 1: dump and restore (recommended)

Stop the application so that no data changes after the dump. `db` keeps running on its current
image:

```bash
docker compose stop proxy php
```

Dump the database with the tools of the **current** server, and save the number of documents
of every collection to compare it after the restore (replace `pumukit` with your `MONGODB_DB`,
and `mongosh` with `mongo` on a 4.x image):

```bash
docker compose exec db mongodump --db=pumukit --archive=/tmp/pumukit.archive.gz --gzip
docker compose cp db:/tmp/pumukit.archive.gz ./pumukit.archive.gz

docker compose exec -T db mongosh --quiet pumukit --eval \
  'db.getCollectionNames().sort().forEach(function (c) { print(c + " " + db.getCollection(c).countDocuments({})) })' \
  > counts-before.txt
```

Remove the old container and its data volume (the B.0 backup is your copy), and start an empty
9.0 server from the committed `docker-compose.yml`. It creates the user of `MONGODB_USER` and starts with
authentication:

```bash
docker compose rm --stop --force db
docker volume rm "$(docker volume ls -q | grep db-data)"   # <project>_db-data
docker compose up -d db

until docker compose exec db mongosh --quiet --eval 'db.runCommand({ ping: 1 })' >/dev/null 2>&1; do sleep 2; done
```

Restore the dump and compare the counts:

```bash
docker compose cp ./pumukit.archive.gz db:/tmp/pumukit.archive.gz
docker compose exec db sh -c 'mongorestore -u "$MONGODB_USER" -p "$MONGODB_PASSWORD" --authenticationDatabase "$MONGO_INITDB_DATABASE" --archive=/tmp/pumukit.archive.gz --gzip'

docker compose exec -T db mongosh --quiet "mongodb://<user>:<password>@localhost:27017/pumukit?authSource=pumukit" --eval \
  'db.getCollectionNames().sort().forEach(function (c) { print(c + " " + db.getCollection(c).countDocuments({})) })' \
  > counts-after.txt
diff counts-before.txt counts-after.txt && echo "same documents"
```

The restore also recreates the indexes of the dump. A new server already starts with FCV `9.0`.
Start the application again and continue with **B.4**:

```bash
docker compose up -d
```

### B.3 Option 2: in-place upgrade, one major at a time

An existing volume has no user, so remove `command: ["--auth"]` from the `db` service during the upgrade. When it finishes, create
the user as in [Security changes in the deployment](#security-changes-in-the-deployment) and restore that line.

You **cannot skip major versions**. The 6.0 code ships `mongo:9.0`, but to upgrade the existing
data volume you must pass through each intermediate image.

> **From 4.0:** MongoDB 4.2 removed the MMAPv1 storage engine, so a 4.0 volume must use
> WiredTiger (`db.serverStatus().storageEngine.name`); otherwise use option 1. Do the 4.2 hop
> before **B.2** (driver 2.5 needs 4.2).

Pin the current FCV first (the version of your current server, e.g. `4.0`, `4.4` or `5.0`):

```bash
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "5.0" })'
```

Then repeat this cycle for each hop up to `9.0`. Temporarily set the `db` image for the
intermediate hops (the final `9.0` already matches the committed file):

```yaml
  db:
    image: mongo:6.0   # each intermediate hop: 4.2, 4.4, 5.0, 6.0, 7.0, 8.0; leave 9.0 (committed) for the last one
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
# after the hops to 4.2, 4.4 and 5.0 (legacy mongo shell on 4.x images)
docker compose exec db mongo --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "4.2" })'   # then "4.4", then "5.0"

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

### B.8 Clean the personal series

PuMuKIT 6.0 creates the personal series of a user with their first upload or recording, not on every login, and
the `ROLE_AUTO_CREATE_PERSONAL_SERIES` permission is removed. Remove it from the stored profiles and users:

```bash
docker compose exec -T db mongosh --quiet "mongodb://<user>:<password>@localhost:27017/pumukit?authSource=pumukit" --eval '
  db.PermissionProfile.updateMany({}, { $pull: { permissions: "ROLE_AUTO_CREATE_PERSONAL_SERIES" } });
  db.User.updateMany({}, { $pull: { roles: "ROLE_AUTO_CREATE_PERSONAL_SERIES" } })'
```

Then count the empty personal series created by the old logins and, with `--force`, delete them. A user whose
series is deleted gets a new one with their next upload. The series created less than a day ago are kept, because
an upload in progress already has its series but not its video yet; change it with `--older-than=<days>`:

```bash
docker compose exec php php bin/console pumukit:personal-series:clean
docker compose exec php php bin/console pumukit:personal-series:clean --force
```

The shared upload series of the LmsBundle (`My LMS Uploads`), the PersonalRecorderBundle and the PoddiumBundle
(`My Poddium Uploads`) are no longer used. Their videos are not moved automatically: to move the videos of one of
them to the personal series of their owners, check first with `--dry-run`. The videos with no owner, with two or
more owners or whose owner has no user are listed and stay in the series:

```bash
docker compose exec php php bin/console pumukit:personal-series:move <series id> --dry-run
docker compose exec php php bin/console pumukit:personal-series:move <series id>
```

### B.9 Clear the cache

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
- **Database**: the server upgrade is **not reversible** once FCV is raised or the volume is
  replaced. Restore the backup from step B.0 onto a container with your previous image (e.g.
  `mongo:5.0`; use `mongo` instead of `mongosh` on 4.x images):

```bash
docker compose down
# Restore the volume snapshot, or restore the dump:
docker compose up -d db   # with your previous image, e.g. image: mongo:5.0
docker compose cp ./backup-5.1 db:/data/db/backup-5.1
docker compose exec db mongosh --quiet --eval 'db.dropDatabase()'
docker compose exec db mongorestore /data/db/backup-5.1
```
