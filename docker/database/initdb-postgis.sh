#!/usr/bin/env bash
set -e

psql --dbname "$POSTGRES_DB" --username "$POSTGRES_USER" <<-'EOSQL'
    CREATE EXTENSION IF NOT EXISTS postgis;
EOSQL
