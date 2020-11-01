#!/bin/bash

SCRIPT_START_SECONDS=$(date +%s)
SCRIPT_START_DATE=$(date +%T)

make deploy

SCRIPT_END_DATE=$(date +%T)

echo "Deploy started  at: ${SCRIPT_START_DATE}"
echo "Deploy finished at: ${SCRIPT_END_DATE}"
echo "Total time elapsed: $(date -ud "@$(($(date +%s) - $SCRIPT_START_SECONDS))" +%T)"
