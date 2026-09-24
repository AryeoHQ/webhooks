<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscription_topics', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_subscription_id')->constrained('webhook_subscriptions');
            $table->string('event_log_transportable_id');
            $table->foreign('event_log_transportable_id')->references('id')->on('event_log_transportables');

            $table->timestampsTz();

            $table->unique(['webhook_subscription_id', 'event_log_transportable_id']);
            $table->index(['event_log_transportable_id', 'webhook_subscription_id']); // Collecting listener looks up subscriptions by transportable
        });
    }
};
