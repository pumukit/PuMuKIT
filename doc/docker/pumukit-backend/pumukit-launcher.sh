#!/bin/bash
set -e


php app/console doctrine:mongodb:schema:create

echo "Launch pumukit server..."
/opt/sbin/php-fpm --nodaemonize
