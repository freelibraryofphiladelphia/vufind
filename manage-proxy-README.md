# Banner Proxy Management

This script installs the same-origin banner endpoint used by the catalog:

```text
/assets/json/banner.json
```

Apache proxies that virtual route to the selected announcements API. No physical JSON asset is created.

## Install

Run from the repository root on Debian/Ubuntu:

```bash
sudo ./manage-proxy.sh --install --environment live
sudo systemctl restart apache2
```

Available environments are `live`, `dev`, and `test`.

The script writes `/etc/apache2/conf-available/vufind-banner-proxy.conf`, enables it with `a2enconf`, and uses `a2enmod` for the required proxy, SSL, headers, and rewrite modules.

Use `--apache-path` for a non-default Apache configuration directory.

## Uninstall

```bash
sudo ./manage-proxy.sh --uninstall
sudo systemctl restart apache2
```

Uninstall disables the `vufind-banner-proxy` Apache configuration and removes its generated file. It does not modify VuFind’s install, override, or application directories.

The proxy fixes `types=2,3`, `sort=publish_at`, and `direction=asc`. It forwards only the `expired_after` and `published_before` date filters and marks responses non-cacheable.
