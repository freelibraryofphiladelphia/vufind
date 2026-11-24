# Banner Proxy Provisioning

These scripts proxy the same-origin banner feed through Apache to the selected announcements API.

## Linux

```bash
sudo ./provisioners/manage-proxy.sh --install --environment live
sudo systemctl restart apache2

sudo ./provisioners/manage-proxy.sh --uninstall
sudo systemctl restart apache2
```

Use `--catalog-path`, `--apache-path`, and `--site-config` for non-default locations. The script targets Debian/Ubuntu Apache installations with `a2enmod`. The site include is written to `/etc/apache2/sites-available/vufind.conf` by default so the HTTP vhost loads the proxy; use `--site-config` when the active vhost has another path. If an SSL vhost exists at `conf/extra/httpd-ssl.conf`, it receives the same include.

Run the script as root.

The proxy fixes `types=2,3`, `sort=publish_at`, and `direction=asc`. It forwards only the `expired_after` and `published_before` date filters and marks responses non-cacheable.

The browser consumes the virtual same-origin endpoint `/assets/json/banner.json`; no physical JSON asset is created. Apache proxies that route to the selected announcements API.

Available environments are `live`, `dev`, and `test`.
