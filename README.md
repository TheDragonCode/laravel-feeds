# 📃 Laravel Feeds

<picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://banners.beyondco.de/Laravel%20Feeds.png?pattern=topography&style=style_2&fontSize=100px&md=1&showWatermark=1&theme=dark&packageManager=composer+require&packageName=dragon-code%2Flaravel-feeds&description=Fast+export+of+large+datasets+to+feeds+for+marketplaces+and+services&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg">
    <img src="https://banners.beyondco.de/Laravel%20Feeds.png?pattern=topography&style=style_2&fontSize=100px&md=1&showWatermark=1&theme=light&packageManager=composer+require&packageName=dragon-code%2Flaravel-feeds&description=Fast+export+of+large+datasets+to+feeds+for+marketplaces+and+services&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg" alt="Laravel Feeds">
</picture>

[![Stable Version][badge_stable]][link_packagist]
[![Total Downloads][badge_downloads]][link_packagist]
[![License][badge_license]][link_license]

**Laravel Feeds** is an easy and fast way to export large amounts of data into feeds for marketplaces and other
consumers.

> **🌟 Features**
>
> - Chunked queries to the database
> - Draft mode during processing
> - Easy property mapping
> - Custom Eloquent models for feed registrations
> - Generate feeds, sitemaps, and more

## Installation

You can install the **Laravel Feeds** package via [Composer](https://getcomposer.org):

```Bash
composer require dragon-code/laravel-feeds
```

You should publish the [migration](database/migrations/2025_09_01_231655_create_feeds_table.php) and
the [config/feeds.php](config/feeds.php) file with:

```bash
php artisan vendor:publish --tag="feeds"
```

> [!WARNING]
>
> Before running migrations, verify the database connection settings in [config/feeds.php](config/feeds.php).

Now you can run migrations and proceed to [create feeds](https://feeds.dragon-code.pro/create-feeds/).

## Basic Usage

To create a feed class, use the `make:feed` console command:

```bash
php artisan make:feed User -t
```

As a result of executing the console command, the files `app/Feeds/UserFeed.php` and `app/Feeds/Items/UserFeedItem.php`
will be created.

Check the [operation/migration](https://feeds.dragon-code.pro/create-feeds/) file that was created for you and run
the console command:

```bash
# For Laravel Deploy Operations
php artisan operations

# For Laravel Migrations
php artisan migrate
```

To generate all active feeds, use the console command:

```bash
php artisan feed:generate
```

## Documentation

| Guide | Description |
|-------|-------------|
| [Getting Started](https://feeds.dragon-code.pro/) | Package overview and first steps |
| [Installation](https://feeds.dragon-code.pro/installation/) | Installation, publishing, and migrations |
| [Create Feeds](https://feeds.dragon-code.pro/create-feeds/) | Feed, item, and info classes |
| [Generation](https://feeds.dragon-code.pro/generation/) | Commands, queues, and scheduling |
| [Supported Formats](https://feeds.dragon-code.pro/supported-formats/) | XML, JSON, JSON Lines, CSV, and RSS |
| [Recipes](https://feeds.dragon-code.pro/receipt-sitemap/) | Sitemap, Instagram, Yandex, and RSS examples |

The [documentation site](https://feeds.dragon-code.pro) includes version and language selectors.

## License

This package is licensed under the [MIT License](LICENSE).


[badge_downloads]:      https://img.shields.io/packagist/dt/dragon-code/laravel-feeds.svg?style=flat-square

[badge_license]:        https://img.shields.io/packagist/l/dragon-code/laravel-feeds.svg?style=flat-square

[badge_stable]:         https://img.shields.io/github/v/release/TheDragonCode/laravel-feeds?label=packagist&style=flat-square

[link_license]:         LICENSE

[link_packagist]:       https://packagist.org/packages/dragon-code/laravel-feeds
