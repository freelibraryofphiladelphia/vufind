#!/bin/bash
set -e

echo "========================================================="
echo " Starting VuFind 11 Review Stack..."
echo "========================================================="

LOCAL_DIR=/usr/local/vufind/local

# 1. Local config: VuFind uses local/config/vufind/*.ini in place of the defaults,
#    so copy the whole files from the Compose config mount the first time.
#    The volume persists them, so later starts keep the existing copies.
mkdir -p "$LOCAL_DIR/cache/public" "$LOCAL_DIR/config/vufind" "$LOCAL_DIR/logs"
touch "$LOCAL_DIR/logs/vufind.log"
chown -R www-data:www-data "$LOCAL_DIR"
for f in config.ini Symphony.ini; do
    if [ ! -s "$LOCAL_DIR/config/vufind/$f" ]; then
        echo "Installing local $f from /run/vufind-config/..."
        cp "/run/vufind-config/$f" "$LOCAL_DIR/config/vufind/$f"
        chown www-data:www-data "$LOCAL_DIR/config/vufind/$f"
    fi
done

echo "========================================================="
echo " VuFind 11 Review Stack is up."
echo " "
echo "   - VuFind Application: http://localhost:8081/"
echo "   - Database and Solr: as configured in docker/configs/config.ini"
echo "========================================================="

exec "$@"
