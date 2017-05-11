#!/bin/bash
set -e

echo "Launch pumukit server..."
/opt/sbin/php-fpm --nodaemonize
