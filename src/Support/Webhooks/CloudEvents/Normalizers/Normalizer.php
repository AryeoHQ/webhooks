<?php

declare(strict_types=1);

namespace Support\Webhooks\CloudEvents\Normalizers;

use CloudEvents\Serializers\Normalizers\V1\Normalizer as BaseNormalizer;
use CloudEvents\Serializers\Normalizers\V1\NormalizerInterface;
use CloudEvents\V1\CloudEventInterface;
use Illuminate\Support\Facades\Date;
use JsonSerializable;

/**
 * Serializes `time` the way the date serializes itself to JSON, falling back to the SDK's format when that isn't RFC 3339.
 */
final readonly class Normalizer implements NormalizerInterface
{
    // RFC 3339 section 5.6 `date-time`, minus leap seconds, which PHP dates can't represent.
    private const string RFC3339 = '/^(?<year>\d{4})-(?<month>0[1-9]|1[0-2])-(?<day>0[1-9]|[12]\d|3[01])T([01]\d|2[0-3]):[0-5]\d:[0-5]\d(\.\d+)?(Z|[+-]([01]\d|2[0-3]):[0-5]\d)\z/i';

    private NormalizerInterface $normalizer;

    public function __construct(NormalizerInterface $normalizer = new BaseNormalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(CloudEventInterface $cloudEvent, bool $rawData): array
    {
        $normalized = $this->normalizer->normalize($cloudEvent, $rawData);

        $date = $cloudEvent->getTime();

        $time = match (true) {
            ! data_has($normalized, 'time') => null,
            $date instanceof JsonSerializable => $date->jsonSerialize(),
            default => Date::make($date)?->jsonSerialize(),
        };

        return is_string($time) && $this->isRfc3339($time)
            ? [...$normalized, 'time' => $time]
            : $normalized;
    }

    private function isRfc3339(string $value): bool
    {
        return preg_match(self::RFC3339, $value, $matches) === 1
            && checkdate((int) $matches['month'], (int) $matches['day'], (int) $matches['year']);
    }
}
