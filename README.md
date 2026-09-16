# HEX PROTOCOL

HEX PROTOCOL is a PHP 8.3 license-generation website designed for deployment through the included Apache Dockerfile and Railway configuration.

## Local run

Use PHP 8.3 or newer with the cURL extension enabled:

```bash
php -S 127.0.0.1:8080
```

Open `http://127.0.0.1:8080/` to use the generator. The application creates its JSON data files inside `data/` on first request.

## Deployment variables

Set these values in Railway rather than committing secrets:

- `VPLINK_API_TOKEN`: the rotated VPLINK API token.
- `HEX_PUBLIC_BASE_URL`: the final public HTTPS URL used to create secure handoff links.
- `PORT`: supplied by Railway automatically.

The application currently stores mutable state in JSON files. Use a persistent Railway volume at `/var/www/html/data` for a temporary single-instance deployment, or migrate the data layer to a managed database before enabling replicas.

## Administrator access

Open `/admin.php` or use the Admin Login link. Change the bootstrap administrator password immediately after the first sign-in. Do not keep a known default password or API token in source control.

## UI update

The public pages use the HEX PROTOCOL name and the existing HEX PROTOCOL logo. The visual system was updated to a premium dark interface with responsive cards, animated status elements, compact navigation, mobile layouts, and consistent generator, download, handoff, result, and administrator-login screens.
