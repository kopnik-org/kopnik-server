#!/bin/bash
psql -Upostgres kopnik < ./docker-entrypoint-initdb.d/database.sql
