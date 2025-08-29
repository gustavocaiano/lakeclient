# Dev Notes: Local Testing Setup

## Test in a local Laravel app without a release

1) Add a Composer path repository to your app

In your Laravel app `composer.json`:
```json
{
  "repositories": [
    { "type": "path", "url": "/Users/gustavocaiano/docs/github/lake-licensor/lakeclient", "options": { "symlink": true } }
  ],
  "require": {
    "gustavocaiano/lakeclient": "dev-main"
  }
}
```
Then install/update:
```bash
composer update gustavocaiano/lakeclient
```

2) Configure environment
```env
LAKE_BASE_URL=http://localhost:8080
# Optional fallback only if form is empty
LAKE_LICENSE_KEY=""
# Optional: use database storage instead of file
LAKE_STORAGE_DRIVER=database
```

3) Publish config and (optional) migrations
```bash
php artisan vendor:publish --tag="lakeclient-config"
php artisan vendor:publish --tag="lakeclient-migrations"
php artisan migrate
```

4) Register plugin in Filament panel
```php
->plugins([
    \GustavoCaiano\Lakeclient\Filament\Plugins\LakeClientPlugin::make(),
])
```

5) Protect routes (if needed)
```php
\GustavoCaiano\Lakeclient\Http\Middleware\EnsureLicensed::class
```

6) Test the flow
- Visit a protected route. You’ll be redirected to the License page if unlicensed.
- Enter the license key in the page.
- Alternatively use CLI:
```bash
php artisan lake:activate {key?}
php artisan lake:heartbeat
php artisan lake:deactivate
```

Notes:
- Include the port in `LAKE_BASE_URL`.
- When using database storage, ensure the migration is run.
