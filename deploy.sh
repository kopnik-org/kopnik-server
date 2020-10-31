#!/bin/bash

SCRIPT_START_TIME=$(date +%s)

make deploy
date

echo "Total time elapsed: $(date -ud "@$(($(date +%s) - $SCRIPT_START_TIME))" +%T)"
