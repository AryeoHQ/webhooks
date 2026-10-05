<?php

declare(strict_types=1);

namespace Support\Webhooks\CloudEvents\Normalizers;

use CloudEvents\V1\CloudEventImmutable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Normalizer::class)]
final class NormalizerTest extends TestCase
{
    #[Test]
    public function it_serializes_time_the_way_the_date_serializes_to_json(): void
    {
        Date::serializeUsing(fn (DateTimeInterface $date): string => $date->format(DateTime::RFC3339_EXTENDED));

        try {
            $time = new DateTimeImmutable('2026-10-01T02:19:24.245+00:00');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24.245+00:00', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_serializes_an_immutable_carbon_time_without_converting_it(): void
    {
        Date::serializeUsing(fn (DateTimeImmutable $date): string => $date->format(DateTime::RFC3339_EXTENDED));

        try {
            $time = Date::parse('2026-10-01T02:19:24.245+00:00')->toImmutable();

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24.245+00:00', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_serializes_time_with_the_default_json_format(): void
    {
        $time = new DateTimeImmutable('2026-10-01T02:19:24.245123+00:00');

        $normalized = new Normalizer()->normalize($this->event($time), false);

        $this->assertSame('2026-10-01T02:19:24.245123Z', $normalized['time']);
    }

    #[Test]
    public function it_falls_back_to_the_sdk_format_when_the_json_format_is_not_rfc3339(): void
    {
        Date::serializeUsing(fn (DateTimeInterface $date): string => $date->format('m/d/Y H:i'));

        try {
            $time = new DateTimeImmutable('2026-10-01T02:19:24.245+00:00');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24Z', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_keeps_a_non_utc_offset(): void
    {
        Date::serializeUsing(fn (DateTimeInterface $date): string => $date->format(DateTime::RFC3339_EXTENDED));

        try {
            $time = new DateTimeImmutable('2026-10-01T07:49:24.245+05:30');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T07:49:24.245+05:30', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_falls_back_to_the_sdk_format_when_the_json_format_is_not_a_string(): void
    {
        Date::serializeUsing(fn (): array => ['2026-10-01T02:19:24Z']);

        try {
            $time = new DateTimeImmutable('2026-10-01T02:19:24.245+00:00');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24Z', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_serializes_time_with_more_than_six_fractional_digits(): void
    {
        Date::serializeUsing(fn (): string => '2026-10-01T02:19:24.245123000Z');

        try {
            $time = new DateTimeImmutable('2026-10-01T02:19:24.245123+00:00');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24.245123000Z', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_serializes_time_with_less_precision_than_the_date(): void
    {
        Date::serializeUsing(fn (DateTimeInterface $date): string => $date->format(DateTime::RFC3339_EXTENDED));

        try {
            $time = new DateTimeImmutable('2026-10-01T02:19:24.245123+00:00');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24.245+00:00', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[DataProvider('nonCompliantTimes')]
    #[Test]
    public function it_falls_back_to_the_sdk_format_when_time_is_not_strictly_rfc3339(string $serialized): void
    {
        Date::serializeUsing(fn (): string => $serialized);

        try {
            $time = new DateTimeImmutable('2026-10-01T02:19:24.245+00:00');

            $normalized = new Normalizer()->normalize($this->event($time), false);

            $this->assertSame('2026-10-01T02:19:24Z', $normalized['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonCompliantTimes(): array
    {
        return [
            'offset without colon' => ['2026-10-01T02:19:24+0000'],
            'single digit day' => ['2026-10-1T02:19:24+00:00'],
            'missing offset' => ['2026-10-01T02:19:24'],
            'impossible date' => ['2026-02-30T02:19:24Z'],
            'trailing newline' => ["2026-10-01T02:19:24Z\n"],
            'second 60 outside a leap second' => ['2026-10-01T02:19:60Z'],
            'impossible date rolling onto the event date' => ['2026-09-31T02:19:24Z'],
        ];
    }

    #[Test]
    public function it_omits_time_when_absent(): void
    {
        $normalized = new Normalizer()->normalize($this->event(null), false);

        $this->assertArrayNotHasKey('time', $normalized);
    }

    private function event(null|DateTimeImmutable $time): CloudEventImmutable
    {
        return new CloudEventImmutable(id: 'id', source: 'https://example.com', type: 'test', time: $time);
    }
}
