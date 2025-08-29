# lakeclient

## Licenses and Activations with Key Encryption

## client side of lake-licensor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gustavocaiano/lakeclient.svg?style=flat-square)](https://packagist.org/packages/gustavocaiano/lakeclient)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/gustavocaiano/lakeclient/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/gustavocaiano/lakeclient/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/gustavocaiano/lakeclient/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/gustavocaiano/lakeclient/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/gustavocaiano/lakeclient.svg?style=flat-square)](https://packagist.org/packages/gustavocaiano/lakeclient)
<!--delete-->
---
This repo can be used to scaffold a Laravel package. Follow these steps to get started:

1. Press the "Use this template" button at the top of this repo to create a new repo with the contents of this lakeclient.
2. Run "php ./configure.php" to run a script that will replace all placeholders throughout all the files.
3. Have fun creating your package.
4. If you need help creating a package, consider picking up our <a href="https://laravelpackage.training">Laravel Package Training</a> video course.
---
<!--/delete-->
This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/lakeclient.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/lakeclient)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require gustavocaiano/lakeclient
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="lakeclient-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="lakeclient-config"
```

Base URL can include a port (e.g., http://localhost:8080):

```env
LAKE_BASE_URL=http://localhost:8080
```

License key from env is optional. The Filament page accepts input and will be used if provided there:

```env
LAKE_LICENSE_KEY=""
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="lakeclient-views"
```

## Usage

### Configure

Set the server base URL (with port if needed):

```env
LAKE_BASE_URL=http://localhost:8080
```

Optional: choose storage driver (file is default, or database):

```env
LAKE_STORAGE_DRIVER=database
```

Then publish and run the migration if using database storage:

```bash
php artisan vendor:publish --tag="lakeclient-migrations"
php artisan migrate
```

### Filament Panel

- Add the plugin to your Filament panel provider:

```php
->plugins([
    \GustavoCaiano\Lakeclient\Filament\Plugins\LakeClientPlugin::make(),
])
```

- Protect routes with the middleware (optional if applied at panel level):

```php
\GustavoCaiano\Lakeclient\Http\Middleware\EnsureLicensed::class
```

- Open the License page and input the license key. The env `LAKE_LICENSE_KEY` is optional and used only as a fallback if the form is empty.

### CLI

```bash
php artisan lake:activate {key?}
php artisan lake:heartbeat
php artisan lake:deactivate
```

### Scheduler (recommended)

Schedule heartbeats frequently; the server TTL dictates when renewals occur. The command will renew only when near expiry (server-driven) and supports jitter to avoid thundering herd.

Examples:

```php
// For short TTLs (e.g., 1–2 minutes), schedule every minute
$schedule->command('lake:heartbeat')->everyMinute();

// For moderate TTLs, schedule every 5 minutes
$schedule->command('lake:heartbeat')->everyFiveMinutes();
```

Optional jitter (bounded so it never delays beyond expiry):

```env
LAKE_HEARTBEAT_JITTER_SECONDS=30
```
LAKE_HEARTBEAT_RENEW_THRESHOLD_SECONDS=15

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [gustavocaiano](https://github.com/gustavocaiano)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
