#!/usr/bin/env bash

set -euo pipefail

APACHE_PATH="/etc/apache2"
ENVIRONMENT=""
INSTALL=0
UNINSTALL=0
CONF_NAME="vufind-banner-proxy"

usage() {
  cat <<'EOF'
Usage:
  manage-proxy.sh --install [--environment live|dev|test] [--apache-path /etc/apache2]
  manage-proxy.sh --uninstall [--apache-path /etc/apache2]
EOF
}

require_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    printf '%s\n' 'The banner proxy installer must be run as root (use sudo).' >&2
    exit 1
  fi
}

api_base() {
  case "$1" in
    live) printf '%s\n' 'http://api-1.freelibrary.org/api/v1' ;;
    dev) printf '%s\n' 'http://dev-api.freelibrary.org/api/v1' ;;
    test) printf '%s\n' 'http://test-api.freelibrary.org/api/v1' ;;
    *) printf 'Unknown API environment: %s\n' "$1" >&2; exit 1 ;;
  esac
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --install) INSTALL=1; shift ;;
    --uninstall) UNINSTALL=1; shift ;;
    --environment) ENVIRONMENT="$2"; shift 2 ;;
    --environment=*) ENVIRONMENT="${1#*=}"; shift ;;
    --apache-path) APACHE_PATH="$2"; shift 2 ;;
    --apache-path=*) APACHE_PATH="${1#*=}"; shift ;;
    --help) usage; exit 0 ;;
    *) printf 'Unknown option: %s\n' "$1" >&2; usage >&2; exit 1 ;;
  esac
done

if [[ "$INSTALL" -eq 1 && "$UNINSTALL" -eq 1 ]]; then
  printf '%s\n' 'Choose either --install or --uninstall, not both.' >&2
  exit 1
fi

conf_available="$APACHE_PATH/conf-available"
generated_config="$conf_available/$CONF_NAME.conf"

write_config() {
  local api="$1"
  cat > "$generated_config" <<EOF
<IfModule mod_ssl.c>
    SSLProxyEngine On
</IfModule>

<IfModule mod_headers.c>
    <Location "/assets/json/banner.json">
        Header always set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
        Header always set Pragma "no-cache"
        Header always set Expires "0"
    </Location>
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{QUERY_STRING} ^expired_after=([^&]+)&published_before=([^&]+)
    RewriteRule ^/?assets/json/banner\.json$ ${api}/announcements?types=2,3&sort=publish_at&direction=asc&expired_after=%1&published_before=%2 [P,END,NE]

    RewriteRule ^/?assets/json/banner\.json$ ${api}/announcements?types=2,3&sort=publish_at&direction=asc [P,END,NE,QSD]
</IfModule>
EOF
}

if [[ "$INSTALL" -eq 1 ]]; then
  require_root
  [[ -d "$conf_available" ]] || {
    printf 'Apache conf-available directory not found: %s\n' "$conf_available" >&2
    exit 1
  }
  if [[ -z "$ENVIRONMENT" ]]; then
    read -r -p 'Banner API environment (live/dev/test): ' ENVIRONMENT
  fi
  export APACHE_CONFDIR="$APACHE_PATH"
  write_config "$(api_base "$ENVIRONMENT")"
  a2enmod proxy proxy_http ssl headers rewrite
  a2enconf "$CONF_NAME"
  printf 'Banner proxy installed using %s. Restart Apache before testing.\n' "$ENVIRONMENT"
elif [[ "$UNINSTALL" -eq 1 ]]; then
  require_root
  export APACHE_CONFDIR="$APACHE_PATH"
  a2disconf "$CONF_NAME" >/dev/null 2>&1 || true
  rm -f "$generated_config"
  printf '%s\n' 'Banner proxy removed. Restart Apache before testing.'
else
  usage
fi
