# Migration Guide (From 5.0 to 5.1)

## Configuration Changes

### 1. Generate a secure secret

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 2. Add to `.env` or `.env.local`

```bash
###> pumukit/base-player-bundle ###
PUMUKITPLAYER_SECURE_SECRET=your-generated-secret-here
PUMUKITPLAYER_SECURE_DURATION=3600
PUMUKITPLAYER_WHEN_DISPATCH_VIEW_EVENT=on_play
###< pumukit/base-player-bundle ###
```

### 3. Clear cache

```bash
php bin/console cache:clear
```

**Note**: Never use the default `changeMe!` in production.

