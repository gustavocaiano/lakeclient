# Dev Notes: Local Testing Setup

## Test in a local Laravel app without a release

1) Add a Composer path repository to your app

In your Laravel app `composer.json`:
```json
{
  "repositories": [
    { "type": "path", "url": "/Users/gustavocaiano/docs/github/wind-licensor/windclient", "options": { "symlink": true } }
  ],
  "require": {
    "gustavocaiano/windclient": "dev-main"
  }
}
```
Then install/update:
```bash
composer update gustavocaiano/windclient
```

2) Configure environment
```env
WIND_BASE_URL=http://localhost:8080
# Optional fallback only if form is empty
WIND_LICENSE_KEY=""
# Optional: use database storage instead of file
WIND_STORAGE_DRIVER=database
```

3) Publish config and (optional) migrations
```bash
php artisan vendor:publish --tag="windclient-config"
php artisan vendor:publish --tag="windclient-migrations"
php artisan migrate
```

4) Register plugin in Filament panel
```php
->plugins([
    \GustavoCaiano\Windclient\Filament\Plugins\WindClientPlugin::make(),
])
```

5) Protect routes (if needed)
```php
\GustavoCaiano\Windclient\Http\Middleware\EnsureLicensed::class
```

6) Test the flow
- Visit a protected route. You’ll be redirected to the License page if unlicensed.
- Enter the license key in the page.
- Alternatively use CLI:
```bash
php artisan wind:activate {key?}
php artisan wind:heartbeat
php artisan wind:deactivate
```

Notes:
- Include the port in `WIND_BASE_URL`.
- When using database storage, ensure the migration is run.
