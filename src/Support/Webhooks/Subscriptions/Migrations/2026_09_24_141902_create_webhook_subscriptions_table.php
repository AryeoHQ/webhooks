<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_endpoint_id')->constrained('webhook_endpoints');
            $table->string('event_log_transportable_id');
            $table->foreign('event_log_transportable_id')->references('id')->on('event_log_transportables');

            $table->timestampsTz();

            $table->unique(['webhook_endpoint_id', 'event_log_transportable_id']);
            $table->index(['event_log_transportable_id', 'webhook_endpoint_id']); // Collecting listener looks up endpoints by transportable
        });
    }
};
