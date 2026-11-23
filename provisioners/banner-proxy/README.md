# Banner Proxy Provisioning

These scripts proxy the same-origin banner feed through Apache to the selected announcements API.

## Windows

```powershell
.\provisioners\manage-proxy.ps1 -Install -Environment live
.\provisioners\manage-proxy.ps1 -Uninstall
```

Use `-CatalogPath` and `-ApachePath` when the default `C:\vufind` and `C:\apache24` paths differ. Restart Apache after either operation.

## Linux

```bash
sudo ./provisioners/manage-proxy.sh --install --environment live
sudo ./provisioners/manage-proxy.sh --uninstall
```

Use `--catalog-path` and `--apache-path` for non-default locations. The Linux script expects Ubuntu 24.04 and enables the required Apache modules when `a2enmod` is available.

The proxy fixes `types=2,3`, `sort=publish_at`, and `direction=asc`. It forwards only the `expired_after` and `published_before` date filters and marks responses non-cacheable.

Available environments are `live`, `dev`, and `test`.
