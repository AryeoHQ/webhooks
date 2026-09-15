<?php

declare(strict_types=1);

namespace Tooling\Webhooks\PhpStan;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Webhooks\Subscriptions\Subscription;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class SubscriptionMustRedeclareUseEloquentBuilder extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $node->namespacedName?->toString() !== Subscription::class
            && $this->inherits($node, Subscription::class)
            && $this->doesNotHaveAttribute($node, UseEloquentBuilder::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'A '.class_basename(Subscription::class).' subclass must redeclare #['.class_basename(UseEloquentBuilder::class).']. PHP does not inherit attributes.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'webhooks.Subscription.UseEloquentBuilder.required',
        );
    }
}
