<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;

test('formats DateTimeInterface to ATOM in UTC by default', function (DateTimeInterface $value, string $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('datetime default');

test('respects custom date format from config', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.format', 'H_i_s : Y-d-m');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('datetime format');

test('applies custom timezone from config when formatting', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.timezone', '+12:00');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('datetime timezone');

test('allows only DateTimeInterface values', function (mixed $value, bool $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with('datetime allow');

test('supports subclasses whose constructor does not call the parent', function () {
    $transformer = new class extends DateTimeTransformer {
        public function __construct() {}
    };
    $date = new DateTimeImmutable('2026-07-28T12:30:00+00:00');

    expect($transformer->transform($date))->toBe('2026-07-28T12:30:00+00:00');

    config()->set('feeds.date.format', 'Y-m-d');

    expect($transformer->transform($date))->toBe('2026-07-28');
});

test('resolves configuration once per transformation', function () {
    $resolutions = 0;

    app()->beforeResolving('config', function () use (&$resolutions) {
        $resolutions++;
    });

    $transformer = new DateTimeTransformer;
    $date        = new DateTimeImmutable('2026-07-28T12:30:00+03:00');

    expect($resolutions)->toBe(0);

    expect($transformer->transform($date))->toBe('2026-07-28T09:30:00+00:00')
        ->and($resolutions)->toBe(1);

    expect($transformer->transform($date))->toBe('2026-07-28T09:30:00+00:00')
        ->and($resolutions)->toBe(2);
});

test('reads updated configuration without changing input dates', function (string $class) {
    $transformer = new DateTimeTransformer;
    $date        = new $class('2026-07-28T23:30:00+03:00');
    $before      = $date->format('Y-m-d\TH:i:s.uP e');

    expect($transformer->transform($date))->toBe('2026-07-28T20:30:00+00:00');

    config()->set('feeds.date', ['format' => 'Y-m-d H:i', 'timezone' => '+12:00']);

    expect($transformer->transform($date))->toBe('2026-07-29 08:30')
        ->and($date->format('Y-m-d\TH:i:s.uP e'))->toBe($before);
})->with([DateTime::class, DateTimeImmutable::class]);

test('reads a replaced configuration repository on the next transformation', function (string $binding) {
    $application   = app();
    $transformer   = new DateTimeTransformer;
    $itemFormatter = new DateTimeTransformer('H:i');
    $date          = new DateTimeImmutable('2026-07-28T23:30:00+03:00');
    $replacement   = new Repository(config()->all());

    expect($transformer->transform($date))->toBe('2026-07-28T20:30:00+00:00')
        ->and($itemFormatter->transform($date))->toBe('20:30');

    $replacement->set('feeds.date', ['format' => 'Y-m-d H:i', 'timezone' => '+12:00']);

    if ($binding === 'forgotten instance') {
        $application->forgetInstance('config');
        $application->instance('config', $replacement);
    } elseif ($binding === 'instance') {
        $application->instance('config', $replacement);
    } else {
        $application->{$binding}('config', fn (): Repository => $replacement);
    }

    expect($transformer->transform($date))->toBe('2026-07-29 08:30')
        ->and($itemFormatter->transform($date))->toBe('08:30');
})->with(['instance', 'singleton', 'bind', 'forgotten instance']);

test('reads configuration from the current container', function () {
    $application = Container::getInstance();
    $transformer = new DateTimeTransformer;
    $date        = new DateTimeImmutable('2026-07-28T23:30:00+03:00');
    $replacement = new Container;
    $repository  = new Repository(config()->all());

    expect($transformer->transform($date))->toBe('2026-07-28T20:30:00+00:00');

    $repository->set('feeds.date', ['format' => 'Y-m-d H:i', 'timezone' => '+12:00']);
    $replacement->instance('config', $repository);

    try {
        Container::setInstance($replacement);

        expect($transformer->transform($date))->toBe('2026-07-29 08:30');
    } finally {
        Container::setInstance($application);
    }

    expect($transformer->transform($date))->toBe('2026-07-28T20:30:00+00:00');
});

test('resolves configuration after the date resolution extension', function () {
    $repository = new Repository(config()->all());
    $repository->set('feeds.date', ['format' => 'Y-m-d H:i', 'timezone' => '+12:00']);

    $transformer = new class ($repository) extends DateTimeTransformer {
        public function __construct(private readonly Repository $repository) {}

        protected function resolve(mixed $date): Carbon
        {
            app()->instance('config', $this->repository);

            return parent::resolve($date);
        }
    };

    expect($transformer->transform(new DateTimeImmutable('2026-07-28T23:30:00+03:00')))
        ->toBe('2026-07-29 08:30');
});

test('supports format and timezone extensions without a configuration binding', function () {
    $application = Container::getInstance();
    $transformer = new class extends DateTimeTransformer {
        protected function format(): string
        {
            return 'Y-m-d';
        }

        protected function timezone(): string
        {
            return '+12:00';
        }
    };

    try {
        Container::setInstance(new Container);

        expect($transformer->transform(new DateTimeImmutable('2026-07-28T23:30:00+03:00')))
            ->toBe('2026-07-29');
    } finally {
        Container::setInstance($application);
    }
});

test('reads current configuration through protected extensions after a failed transformation', function () {
    $transformer = new class extends DateTimeTransformer {
        public function settings(): array
        {
            return [$this->format(), $this->timezone()];
        }
    };
    $date        = new DateTimeImmutable('2026-07-28T23:30:00+03:00');
    $replacement = new Repository(config()->all());

    expect($transformer->settings())->toBe([DATE_ATOM, 'UTC']);

    config()->set('feeds.date.timezone', 'Invalid/Timezone');

    expect(fn () => $transformer->transform($date))->toThrow(InvalidArgumentException::class);

    $replacement->set('feeds.date', ['format' => 'Y-m-d H:i', 'timezone' => '+12:00']);
    app()->instance('config', $replacement);

    expect($transformer->settings())->toBe(['Y-m-d H:i', '+12:00'])
        ->and($transformer->transform($date))->toBe('2026-07-29 08:30');

    app()->instance('config', new Repository(['feeds' => ['date' => ['format' => 'Y', 'timezone' => 'UTC']]]));

    expect($transformer->settings())->toBe(['Y', 'UTC']);
});

test('restores the active repository after a nested transformation', function () {
    $transformer = new class extends DateTimeTransformer {
        public ?string $nestedResult = null;

        protected bool $nested = false;

        protected function timezone(): string
        {
            $timezone = parent::timezone();

            if (! $this->nested) {
                $this->nested = true;

                $repository  = config();
                $replacement = new Repository($repository->all());
                $replacement->set('feeds.date', ['format' => 'H:i', 'timezone' => '+12:00']);
                app()->instance('config', $replacement);

                try {
                    $this->nestedResult = $this->transform(new DateTimeImmutable('2026-07-28T23:30:00+03:00'));
                } finally {
                    app()->instance('config', $repository);
                }
            }

            return $timezone;
        }
    };

    expect($transformer->transform(new DateTimeImmutable('2026-07-28T23:30:00+03:00')))
        ->toBe('2026-07-28T20:30:00+00:00')
        ->and($transformer->nestedResult)->toBe('08:30');
});
