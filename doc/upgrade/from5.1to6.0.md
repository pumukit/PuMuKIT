# Migration Guide (From 5.1 to 6.0)

PuMuKIT 6.0 upgrades the whole MongoDB stack. This guide describes the **exact,
reproducible steps** so it can be replayed on any deployment.

What changes in 6.0 (MongoDB side):

- **MongoDB server**: `5.0` → `8.0` (must be upgraded one major at a time).
- **PHP driver** (`ext-mongodb`): `1.14.2` → `1.21.0` (stays on the 1.x line).
- **PHP MongoDB library** (`mongodb/mongodb`): `1.13` → `1.21` (Doctrine ODM stays on `2.6`).
- The denormalized **search text index must be rebuilt** after upgrading existing data.

The 6.0 code already ships the final values (`docker-compose.yml` with `mongo:8.0`, the
`Dockerfile` with `ext-mongodb 1.21.0`). You only need to align your **running
containers and data** with that code.

> The commands assume the Docker Compose setup of this repository, with services
> `php` (PHP-FPM) and `db` (MongoDB). Run database commands against the `db` container
> and application commands inside the `php` container. Adapt the prefixes if your
> deployment differs.

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

# 2. Build and start (ships mongo:8.0 and ext-mongodb 1.21.0)
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
be raised to `1.21` before the server reaches 7.0/8.0), and only then upgrade the **server**
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

> This updates `docker-compose.yml` to `mongo:8.0` and the `Dockerfile` to
> `ext-mongodb 1.21.0`. **Do not** recreate the `db` container yet: your data volume still
> holds 5.0 data and an 8.0 server would refuse to start on it. Leave `db` running on its
> current 5.0 container until step **B.3**.

### B.2 Upgrade the PHP driver and dependencies (server still on 5.0)

Driver `1.21` is backward compatible with server `5.0`, so the app boots before the server
upgrade.

```bash
# Rebuild ONLY the php service (do not touch db yet)
docker compose build php
docker compose up -d php

# Verify the driver
docker compose exec php php --ri mongodb | grep "MongoDB extension version"   # -> 1.21.x

# Install the 6.0 dependencies (mongodb/mongodb 1.21)
docker compose exec php composer install --no-dev --optimize-autoloader
```

At this point you are running **6.0 code + driver 1.21 + server 5.0**, which is a supported
combination.

### B.3 Upgrade the MongoDB server (5.0 → 6.0 → 7.0 → 8.0)

You **cannot skip major versions**. The 6.0 code ships `mongo:8.0`, but to migrate existing
5.0 data you must pass through each intermediate image. Pin the current FCV first:

```bash
docker compose exec db mongosh --quiet --eval \
  'db.adminCommand({ setFeatureCompatibilityVersion: "5.0" })'
```

Then repeat this cycle for each hop — `6.0`, then `7.0`, then `8.0`. Temporarily set the
`db` image for the intermediate hops (the final `8.0` already matches the committed file):

```yaml
  db:
    image: mongo:6.0   # hop 1; then 7.0 for hop 2; leave 8.0 (committed) for hop 3
```

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
```

Verify after each hop (final state must be server `8.0.x`, FCV `8.0`):

```bash
docker compose exec db mongosh --quiet --eval \
  'print(db.version()); db.adminCommand({ getParameter: 1, featureCompatibilityVersion: 1 })'
```

After the last hop, make sure `docker-compose.yml` is back to the committed `image:
mongo:8.0` (no leftover local edit).

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

### B.6 Clear the cache

```bash
docker compose exec php php bin/console cache:clear
```

---

## Verification checklist

- `db.version()` reports `8.0.x` and FCV is `8.0`.
- `php --ri mongodb` reports `1.21.x`.
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
