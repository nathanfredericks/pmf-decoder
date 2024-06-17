# syntax=docker/dockerfile:1.4

FROM --platform=$BUILDPLATFORM php:8.3-apache as builder

RUN a2enmod rewrite

CMD ["apache2-foreground"]

FROM builder as dev-envs

RUN a2enmod rewrite

RUN <<EOF
apt-get update
apt-get install -y --no-install-recommends git
EOF

RUN <<EOF
useradd -s /bin/bash -m vscode
groupadd docker
usermod -aG docker vscode
EOF
# install Docker tools (cli, buildx, compose)
COPY --from=gloursdocker/docker / /

CMD ["apache2-foreground"]
