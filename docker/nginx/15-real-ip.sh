#!/bin/sh
set -e

: > /etc/nginx/conf.d/real_ip.conf
[ -n "$PUMUKIT_REAL_IP_FROM" ] || exit 0

for source in $(printf '%s' "$PUMUKIT_REAL_IP_FROM" | tr ',' ' '); do
    printf 'set_real_ip_from %s;\n' "$source" >> /etc/nginx/conf.d/real_ip.conf
done

printf 'real_ip_header X-Forwarded-For;\nreal_ip_recursive on;\n' >> /etc/nginx/conf.d/real_ip.conf
