#!/bin/bash
set -e

echo "========================================================="
echo " Starting VuFind 11 Stack..."
echo "========================================================="

LOCAL_DIR=/usr/local/vufind/local

# 1. Local config comes from the host bind mount at local/config/vufind (read-only),
#    so nothing is copied here. Only the writable local state is prepared.
mkdir -p "$LOCAL_DIR/cache/public" "$LOCAL_DIR/config/vufind" "$LOCAL_DIR/logs"
touch "$LOCAL_DIR/logs/vufind.log"
chown -R www-data:www-data "$LOCAL_DIR/cache" "$LOCAL_DIR/logs"

echo "========================================================="
echo " VuFind 11 Stack is up."
echo " "
echo "   - VuFind Application: http://localhost:8081/"
echo "   - Database and Solr: as configured in docker/configs/config.ini"
echo "========================================================="

exec "$@"
