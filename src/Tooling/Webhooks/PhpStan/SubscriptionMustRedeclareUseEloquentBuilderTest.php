<?php

declare(strict_types=1);

namespace Tooling\Webhooks\PhpStan;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<SubscriptionMustRedeclareUseEloquentBuilder> */
#[CoversClass(SubscriptionMustRedeclareUseEloquentBuilder::class)]
final class SubscriptionMustRedeclareUseEloquentBuilderTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new SubscriptionMustRedeclareUseEloquentBuilder;
    }

    #[Test]
    public function it_passes_when_the_subclass_redeclares_the_attribute(): void
    {
        $this->analyse([$this->getFixturePath('Webhooks/ValidSubscription.php')], []);
    }

    #[Test]
    public function it_passes_when_the_class_does_not_extend_subscription(): void
    {
        $this->analyse([$this->getFixturePath('Webhooks/NotASubscription.php')], []);
    }

    #[Test]
    public function it_fails_when_the_subclass_omits_the_attribute(): void
    {
        $this->analyse([$this->getFixturePath('Webhooks/SubscriptionWithoutUseEloquentBuilder.php')], [
            [
                'A '.class_basename(Subscription::class).' subclass must redeclare #['
                    .class_basename(UseEloquentBuilder::class).']. PHP does not inherit attributes.',
                12,
            ],
        ]);
    }
}
