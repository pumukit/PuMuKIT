#!/bin/sh
set -e

directory=/etc/nginx/pumukit-dls
output="$directory/validation.conf"

cat > "$directory/http.conf" <<NGINX
fastcgi_cache_path /var/cache/nginx/dls_auth levels=1:2 keys_zone=dls_auth:10m max_size=64m inactive=1m;
proxy_cache_path /var/cache/nginx/dls_auth_http levels=1:2 keys_zone=dls_auth_http:10m max_size=64m inactive=1m;

map \$arg_forcedl \$dls_content_disposition {
    default "";
    "1" "attachment";
}

map \$http_origin \$dls_cors_origin {
    default "";
    "~${PUMUKIT_DLS_CORS_ORIGIN}" \$http_origin;
}

map \$request_uri \$dls_query_string {
    default "";
    "~\?(?<query>.*)\$" \$query;
}
NGINX

if [ -n "$PUMUKIT_DLS_VALIDATION_URL" ]; then
    url="${PUMUKIT_DLS_VALIDATION_URL%/}"
    host="${PUMUKIT_FRONTEND_HOST:-}"
    upstream_host=""
    if [ -n "$host" ]; then
        upstream_host="proxy_set_header Host $host;
        proxy_ssl_server_name on;
        proxy_ssl_name $host;"
    fi
    cat > "$output" <<NGINX
    location = /_validate_token {
        internal;

        rewrite ^ /download/validate-token?\$dls_query_string? break;
        proxy_pass $url;
        proxy_pass_request_body off;
        proxy_set_header Content-Length "";
        proxy_set_header X-Original-URI \$request_uri;
        proxy_set_header X-Forwarded-For \$remote_addr;
        $upstream_host

        proxy_cache dls_auth_http;
        proxy_cache_key \$request_uri;
        proxy_cache_valid 200 401 10s;
        proxy_ignore_headers Cache-Control Expires Set-Cookie;
    }

    location = /_validate_hls {
        internal;

        rewrite ^ /download/validate-hls? break;
        proxy_pass $url;
        proxy_pass_request_body off;
        proxy_set_header Content-Length "";
        proxy_set_header X-Original-URI \$request_uri;
        proxy_set_header X-Forwarded-For \$remote_addr;
        $upstream_host

        proxy_cache dls_auth_http;
        proxy_cache_key \$hls_token\$hls_dir;
        proxy_cache_valid 200 401 10s;
        proxy_ignore_headers Cache-Control Expires Set-Cookie;
    }
NGINX
    exit 0
fi

fpm="${PUMUKIT_DLS_PHP_FPM:-php:9000}"
cat > "$output" <<NGINX
    location = /_validate_token {
        internal;

        fastcgi_pass $fpm;
        fastcgi_pass_request_body off;

        fastcgi_cache dls_auth;
        fastcgi_cache_key \$request_uri;
        fastcgi_cache_valid 200 401 10s;
        fastcgi_ignore_headers Cache-Control Expires Set-Cookie;

        fastcgi_param SCRIPT_FILENAME /srv/pumukit/public/index.php;
        fastcgi_param SCRIPT_NAME /index.php;
        fastcgi_param REQUEST_METHOD GET;
        fastcgi_param REQUEST_URI /download/validate-token?\$dls_query_string;
        fastcgi_param QUERY_STRING \$dls_query_string;
        fastcgi_param CONTENT_LENGTH "";
        fastcgi_param SERVER_NAME ${PUMUKIT_FRONTEND_HOST};
        fastcgi_param SERVER_PORT 443;
        fastcgi_param HTTPS on;
        fastcgi_param REMOTE_ADDR \$remote_addr;
        fastcgi_param HTTP_HOST ${PUMUKIT_FRONTEND_HOST};
        fastcgi_param HTTP_X_FORWARDED_FOR \$remote_addr;
        fastcgi_param HTTP_X_ORIGINAL_URI \$request_uri;
    }

    location = /_validate_hls {
        internal;

        fastcgi_pass $fpm;
        fastcgi_pass_request_body off;

        fastcgi_cache dls_auth;
        fastcgi_cache_key \$hls_token\$hls_dir;
        fastcgi_cache_valid 200 401 10s;
        fastcgi_ignore_headers Cache-Control Expires Set-Cookie;

        fastcgi_param SCRIPT_FILENAME /srv/pumukit/public/index.php;
        fastcgi_param SCRIPT_NAME /index.php;
        fastcgi_param REQUEST_METHOD GET;
        fastcgi_param REQUEST_URI /download/validate-hls;
        fastcgi_param QUERY_STRING "";
        fastcgi_param CONTENT_LENGTH "";
        fastcgi_param SERVER_NAME ${PUMUKIT_FRONTEND_HOST};
        fastcgi_param SERVER_PORT 443;
        fastcgi_param HTTPS on;
        fastcgi_param REMOTE_ADDR \$remote_addr;
        fastcgi_param HTTP_HOST ${PUMUKIT_FRONTEND_HOST};
        fastcgi_param HTTP_X_FORWARDED_FOR \$remote_addr;
        fastcgi_param HTTP_X_ORIGINAL_URI \$request_uri;
    }
NGINX
