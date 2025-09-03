<?php

declare(strict_types=1);

expect()->extend('toMatchFileSnapshot', function () {
    $content = file_get_contents($this->value);

    expect($content)->toMatchSnapshot();

    return $this;
});

expect()->extend('toMatchFeedSnapshot', function () {
    $path = feedPath($this->value . 'Feed');

    expect($path)->toMatchFileSnapshot();

    return $this;
});

expect()->extend('toMatchFeedItemSnapshot', function () {
    $path = feedPath('Items/' . $this->value . 'FeedItem');

    expect($path)->toMatchFileSnapshot();

    return $this;
});

expect()->extend('toMatchFeedInfoSnapshot', function () {
    $path = feedPath('Info/' . $this->value . 'FeedInfo');

    expect($path)->toMatchFileSnapshot();

    return $this;
});

expect()->extend('toMatchGeneratedFeed', function () {
    $path = app($this->value->class)->path();

    expect($path)->toBeFile();

    return $this;
});
