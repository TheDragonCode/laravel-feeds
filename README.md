# 📃 Laravel Feeds

![the dragon code laravel feeds](https://preview.dragon-code.pro/the-dragon-code/feeds.svg?brand=laravel&mode=dark)

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
> - Generation of any XML (feeds, sitemaps, etc.)

## Installation

To get the latest version of **Laravel Feeds**, simply require the project
using [Composer](https://getcomposer.org):

```Bash
composer require dragon-code/laravel-feeds
```

After that, publish the configuration file by call the console command:

```bash
php artisan vendor:publish --tag=feeds
```

## Basic Usage

### Create Feeds

```bash
php artisan make:feed User -t
```

As a result of executing the console command, the files `app/Feeds/UserFeed.php` and `app/Feeds/Items/UserFeedItem.php`
will be created.

### Generate Feeds

To generate feeds, create the classes of feeds and its element, add links to the file `config/feeds.php`, next call the
console command:

```bash
php artisan feed:generate
```

## Documentation

[📚 Check out the full documentation to learn everything that Laravel Feeds has to offer.](https://feeds.dragon-code.pro)

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
