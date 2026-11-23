#!/usr/bin/env bash

set -euo pipefail

CATALOG_PATH="/var/www/vufind"
APACHE_PATH="/etc/apache2"
SITE_CONFIG=""
ENVIRONMENT=""
INSTALL=0
UNINSTALL=0
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

usage() {
  cat <<'EOF'
Usage:
  manage-proxy.sh --install [--environment live|dev|test] [--catalog-path /var/www/vufind] [--apache-path /etc/apache2] [--site-config /etc/apache2/sites-available/vufind.conf]
  manage-proxy.sh --uninstall [--catalog-path /var/www/vufind] [--apache-path /etc/apache2] [--site-config /etc/apache2/sites-available/vufind.conf]
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
    --catalog-path) CATALOG_PATH="$2"; shift 2 ;;
    --catalog-path=*) CATALOG_PATH="${1#*=}"; shift ;;
    --apache-path) APACHE_PATH="$2"; shift 2 ;;
    --apache-path=*) APACHE_PATH="${1#*=}"; shift ;;
    --site-config) SITE_CONFIG="$2"; shift 2 ;;
    --site-config=*) SITE_CONFIG="${1#*=}"; shift ;;
    --help) usage; exit 0 ;;
    *) printf 'Unknown option: %s\n' "$1" >&2; usage >&2; exit 1 ;;
  esac
done

if [[ "$INSTALL" -eq 1 && "$UNINSTALL" -eq 1 ]]; then
  printf '%s\n' 'Choose either --install or --uninstall, not both.' >&2
  exit 1
fi

local_path="$CATALOG_PATH/local"
if [[ -z "$SITE_CONFIG" ]]; then
  SITE_CONFIG="$APACHE_PATH/sites-available/vufind.conf"
fi
site_template="$SCRIPT_DIR/banner-proxy/site.conf.template"
site_include="$local_path/banner-proxy-site.conf"
site_directive="Include $site_include"
legacy_module_include="$local_path/banner-proxy-modules.conf"
legacy_module_directive="Include $legacy_module_include"

add_site_directive() {
  local path="$1" directive="$2"
  [[ -f "$path" ]] || return 0
  grep -Fqx "$directive" "$path" && return 0

  if grep -Fq '<VirtualHost' "$path"; then
    local temp_file
    temp_file="$(mktemp)"
    DIRECTIVE="$directive" perl -0pe 's{^(\s*</VirtualHost>)}{"  $ENV{DIRECTIVE}\n$1"}me' "$path" > "$temp_file"
    cat "$temp_file" > "$path"
    rm -f "$temp_file"
  else
    printf '\n%s\n' "$directive" >> "$path"
  fi
}

remove_directive() {
  local path="$1" directive="$2"
  [[ -f "$path" ]] || return 0
  local temp_file
  temp_file="$(mktemp)"
  DIRECTIVE="$directive" perl -0pe 's/^\s*\Q$ENV{DIRECTIVE}\E\s*\r?$//mg' "$path" > "$temp_file"
  cat "$temp_file" > "$path"
  rm -f "$temp_file"
}

if [[ "$INSTALL" -eq 1 ]]; then
  require_root
  if [[ -z "$ENVIRONMENT" ]]; then
    read -r -p 'Banner API environment (live/dev/test): ' ENVIRONMENT
  fi
  api="$(api_base "$ENVIRONMENT")"
  mkdir -p "$local_path"
  sed "s|__API_BASE__|$api|g" "$site_template" > "$site_include"
  a2enmod proxy proxy_http ssl headers rewrite
  if [[ -f "$SITE_CONFIG" ]]; then
    remove_directive "$SITE_CONFIG" "$site_directive"
    add_site_directive "$SITE_CONFIG" "$site_directive"
  else
    remove_directive "$CATALOG_PATH/local/httpd-vufind.conf" "$site_directive"
    add_site_directive "$CATALOG_PATH/local/httpd-vufind.conf" "$site_directive"
  fi
  remove_directive "$APACHE_PATH/sites-available/default-ssl.conf" "$site_directive"
  add_site_directive "$APACHE_PATH/sites-available/default-ssl.conf" "$site_directive"
  printf 'Banner proxy installed using %s (%s). Restart Apache before testing.\n' "$ENVIRONMENT" "$api"
elif [[ "$UNINSTALL" -eq 1 ]]; then
  require_root
  remove_directive "$SITE_CONFIG" "$site_directive"
  remove_directive "$CATALOG_PATH/local/httpd-vufind.conf" "$site_directive"
  remove_directive "$APACHE_PATH/sites-available/default-ssl.conf" "$site_directive"
  remove_directive "$APACHE_PATH/apache2.conf" "$legacy_module_directive"
  rm -f "$site_include"
  rm -f "$legacy_module_include"
  printf '%s\n' 'Banner proxy removed. Restart Apache before testing.'
else
  usage
fi
