FROM drupal:10.2.2-php8.2-fpm-alpine3.18

# install various utilities we need/want in the container
RUN \
  apk update && \
  apk add --no-cache socat bash patch wget git mariadb-client rsync unzip imagemagick && \
  apk upgrade

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# install composer
RUN \
  wget -q -O - https://getcomposer.org/installer | php && \
  mv composer.phar /usr/local/bin/composer

ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /opt/drupal

# install drupal modules
COPY composer.* .
RUN \
  composer self-update 2.7.1 && \
  composer install

WORKDIR /var/www
RUN mkdir -p backup/ && \
    chown 1001:444 backup

# copy backup/restore scripts
COPY scripts scripts

# make backup/restore scripts executable
RUN \
  chmod +x ./scripts/*

# copy drupal site configuration files
COPY sync sync
RUN chown 1001:444 -R sync

COPY patch patch
RUN bash -c 'cd /opt/drupal && for P in /var/www/patch/* ; do patch -p 1 < $P ; done'

WORKDIR /var/www/html/sites/default
COPY config/site_config ./
RUN \
  cp default.settings.php settings.php && \
  cat append.to.settings.php       >> settings.php && \
  # create mount point for volume with the correct permissions
  mkdir files && \
  adduser -D --uid 1001 drupal-user && \
  chown -R drupal-user ./* && \
  addgroup -g 444 fsgroup && \
  addgroup drupal-user fsgroup

COPY config/php_config /usr/local/etc/php/conf.d
# enable the status page of fpm
RUN echo '' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'pm.status_path = /status' >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
RUN \
    chown -R drupal-user:drupal-user sites modules
USER drupal-user
