#!/usr/bin/env bash

set -e
set -u

docker-compose down -v
docker-compose build php
docker-compose build nginx
docker-compose up -d db

until docker-compose exec db /opt/bitnami/scripts/mariadb/healthcheck.sh 1>/dev/null 2>&1;
do
  echo "MariaDB is unavailable - sleeping"
  sleep 1
done
sleep 5

docker-compose run --rm php /mnt/scripts/restore
docker-compose run --rm php /mnt/scripts/install
docker-compose up -d
docker-compose exec -d php nohup socat TCP-LISTEN:10000,fork TCP4:azure_storage:10000

docker-compose logs -f