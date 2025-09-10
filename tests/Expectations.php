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

expect()->pipe('toMatchSnapshot', function (Closure $next) {
    if (! is_string($this->value)) {
        return $this->value;
    }

    $this->value = preg_replace(
        pattern    : '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}Z)/',
        replacement: '2025-09-04T04:08:12.000000Z',
        subject    : $this->value
    );

    return $next();
});

expect()->extend('toBeJsonLines', function () {
    foreach (explode("\n", $this->value) as $line) {
        expect($line)->toBeJson();
    }

    return $this;
});

expect()->extend('toBeCsv', function () {
    $delimiter = config('feeds.converters.csv.delimiter');

    foreach (explode("\n", $this->value) as $line) {
        expect($line)->toContain($delimiter);

        expect(
            explode($delimiter, $line)
        )->toBeArray()->not->toBeEmpty();
    }

    return $this;
});
