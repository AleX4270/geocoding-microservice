#!/usr/bin/env bash
set -e

PGUSER=${POSTGRES_USER}
PGDB=${POSTGRES_DB}

psql --dbname "$PGDB" --user "$PGUSER" <<-EOSQL
    CREATE USER geocoding_microservice_test WITH PASSWORD 'TsKKMdExVayG';
    CREATE DATABASE geocoding_microservice_test;
    GRANT ALL PRIVILEGES ON DATABASE geocoding_microservice_test TO geocoding_microservice_test;
EOSQL

psql --dbname "geocoding_microservice_test" --user "$PGUSER" <<-'EOSQL'
    GRANT ALL ON SCHEMA public TO geocoding_microservice_test;
    CREATE EXTENSION IF NOT EXISTS postgis;
EOSQL