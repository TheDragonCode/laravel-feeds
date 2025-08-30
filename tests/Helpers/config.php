<?php

declare(strict_types=1);

function setPrettyXml(bool $enabled): void
{
    config()?->set('feeds.pretty', $enabled);
}
