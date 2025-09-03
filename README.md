# 📃 Laravel Feeds

![the dragon code laravel feeds](docs/images/social-logo.png#gh-light-mode-only)
![the dragon code laravel feeds](docs/images/social-logo_dark.png#gh-dark-mode-only)

[![Stable Version][badge_stable]][link_packagist]
[![Total Downloads][badge_downloads]][link_packagist]
[![Github Workflow Status][badge_build]][link_build]
[![License][badge_license]][link_license]

**Laravel Feeds** is an easy and fast way of exporting a large amount of data to feeds for marketplaces and other
consumers.

> **🌟 Features**
>
> - Chunked queries to database
> - Draft mode for a process
> - Easy property mapping
> - Generation of any feeds, sitemaps, etc.

## Installation

You can install the **Laravel Feeds** package via [Composer](https://getcomposer.org):

```Bash
composer require dragon-code/laravel-feeds
```

You should publish
the [migration](database/migrations/2025_09_01_231655_create_feeds_table.php)
and the [config/feeds.php](config/feeds.php) file with:

```bash
php artisan vendor:publish --tag="feeds"
```

> [!WARNING]
>
> Before running migrations, check the database connection settings in the [config/feeds.php](config/feeds.php) file.

Now you can run migrations and proceed to [create feeds](https://feeds.dragon-code.pro/create-feeds.html).

## Basic Usage

To create a feed class, use the `make:feed` console command:

```bash
php artisan make:feed User -t
```

As a result of executing the console command, the files `app/Feeds/UserFeed.php` and `app/Feeds/Items/UserFeedItem.php`
will be created.

> [!TIP]
> When creating a feed, an operation/migration will also be created to add it to the database.
>
> If the project uses the [Laravel Deploy Operations](https://deploy-operations.dragon-code.pro), then an operation
> class will be created, otherwise a migration class will be created.
>
> This is necessary to add and manage information about feeds in the database.

Check the [operation/migration](https://feeds.dragon-code.pro/create-feeds.html) file that was created for you and run
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

📚 You will find full documentation on the dedicated [documentation](https://feeds.dragon-code.pro) site.

## License

This package is licensed under the [MIT License](LICENSE).


[badge_build]:          https://img.shields.io/github/actions/workflow/status/TheDragonCode/laravel-feeds/tests.yml?style=flat-square

[badge_downloads]:      https://img.shields.io/packagist/dt/dragon-code/laravel-feeds.svg?style=flat-square

[badge_license]:        https://img.shields.io/packagist/l/dragon-code/laravel-feeds.svg?style=flat-square

[badge_stable]:         https://img.shields.io/github/v/release/TheDragonCode/laravel-feeds?label=packagist&style=flat-square

[link_build]:           https://github.com/TheDragonCode/laravel-feeds/actions

[link_license]:         LICENSE

[link_packagist]:       https://packagist.org/packages/dragon-code/laravel-feeds

[link_website]:         https://deploy-operations.dragon-code.pro
