FROM postgres:18.3

RUN apt-get update && apt-get install -y --no-install-recommends \
    postgresql-$PG_MAJOR-postgis-3 \
    && rm -rf /var/lib/apt/lists/*

COPY docker/database/initdb-postgis.sh /docker-entrypoint-initdb.d/01-postgis.sh
