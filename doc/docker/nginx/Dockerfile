ARG NGINX_VERSION=1.15

FROM alpine:latest

RUN apk add --no-cache openssl

# Use this self-generated certificate only in dev, IT IS NOT SECURE!
RUN openssl genrsa -des3 -passout pass:NotSecure -out cert.pass.key 2048
RUN openssl rsa -passin pass:NotSecure -in cert.pass.key -out cert.key
RUN rm cert.pass.key
RUN openssl req -new -passout pass:NotSecure -key cert.key -out cert.csr \
    -subj '/C=ES/ST=PO/L=Vigo/O=PuMuKIT Dev/CN=localhost'
RUN openssl x509 -req -sha256 -days 365 -in cert.csr -signkey cert.key -out cert.crt


FROM nginx:${NGINX_VERSION}-alpine

RUN mkdir -p /etc/nginx/ssl/

COPY --from=0 cert.key cert.crt /etc/nginx/ssl/
COPY conf/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /srv/pumukit

COPY --from=teltek/pumukit:3.2.x /srv/pumukit/web web/
