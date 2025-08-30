<?php

declare(strict_types=1);

expect()->extend('toMatchFeedSnapshot', function () {
    $content = file_get_contents(feedPath($this->value . 'Feed'));

    expect($content)->toMatchSnapshot();

    return $this;
});

expect()->extend('toMatchFeedItemSnapshot', function () {
    $content = file_get_contents(feedPath('Items/' . $this->value . 'FeedItem'));

    expect($content)->toMatchSnapshot();

    return $this;
});
