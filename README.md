# 📃 Laravel Feeds

![the dragon code laravel feeds](https://preview.dragon-code.pro/the-dragon-code/feeds.svg?brand=laravel&mode=dark)

[![Stable Version][badge_stable]][link_packagist]
[![Total Downloads][badge_downloads]][link_packagist]
[![Github Workflow Status][badge_build]][link_build]
[![License][badge_license]][link_license]

**Laravel Feeds** is an easy and fast way of exporting a large amount of data to feeds for marketplaces and other
consumers.

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

### Generate Feeds

To generate feeds, create the classes of feeds and its element, add links to the file `config/feeds.php`, next call the
console command:

```bash
php artisan feed:generate
```

### Feed

Create a feed class. For example:

```php
namespace App\Feeds;

use App\Feeds\Items\UserFeedItem;
use App\Models\User;
use DragonCode\LaravelFeed\Feed;
use DragonCode\LaravelFeed\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>', now()->subYear());
    }

    // You can remove to overwrite of the method,
    // if you do not need to wrap the elements into the general tag.
    public function rootItem(): ?string
    {
        return 'users';
    }

    public function filename(): string
    {
        return 'users-feed.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new UserFeedItem($model);
    }
}
```

### Feed Item

Create a feed item class. For example:

```php
namespace App\Feeds\Items;

use DragonCode\LaravelFeed\FeedItem;

/** @property-read \App\Models\User $model */
class UserFeedItem extends FeedItem
{
    public function attributes(): array
    {
        return [
            'id' => $this->model->id,
            'created_at' => $this->model->created_at->format('Y-m-d'),
        ];
    }

    public function toArray(): array
    {
        return [
            'name'   => $this->model->name,
            'email' => $this->model->email,

            'header' => [
                '@cdata' => '<h1>' . $this->model->name . '</h1>',
            ],

            'names' => [
                'Good guy' => [
                    '@attributes' => [
                        'my-key-1' => 'my value 1',
                        'my-key-2' => 'my value 2',
                    ],

                    'name'   => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],

                'Bad guy' => [
                    'name' => [
                        '@cdata' => '<h1>Sauron</h1>',
                    ],

                    'weapon' => 'Evil Eye',
                ],
            ],
        ];
    }
}
```

According to this example, the XML file with the following contents will be generated as a result:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<users>
    <user id="1" created_at="2025-08-30">
        <name>John Doe</name>
        <email>john.doe@example.com</email>
        <header><![CDATA[<h1>John Doe</h1>]]></header>
        <names>
            <Good_guy my-key-1="my value 1" my-key-2="my value 2">
                <name>Luke Skywalker</name>
                <weapon>Lightsaber</weapon>
            </Good_guy>
            <Bad_guy>
                <name><![CDATA[<h1>Sauron</h1>]]></name>
                <weapon>Evil Eye</weapon>
            </Bad_guy>
        </names>
    </user>
</users>
```

### Laravel Idea Support

You can also easily create the desired classes using the [Laravel Idea](http://laravel-idea.com) plugin
for [PhpStorm](https://www.jetbrains.com/phpstorm/):

![](.github/images/idea.png)

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
