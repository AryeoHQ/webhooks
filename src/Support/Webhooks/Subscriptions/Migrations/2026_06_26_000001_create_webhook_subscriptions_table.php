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

            // Declared manually (over uuidMorphs) in favor of a wider composite manually defined below
            $table->string('subscriber_type');
            $table->uuid('subscriber_id');

            $table->string('event')->index(); // Handles cross-subscriber lookup by event alias
            $table->string('url');
            $table->string('version')->nullable();
            $table->json('headers')->nullable();
            $table->string('secret');
            $table->string('status');
            $table->timestampsTz();

            // Handles the collecting listener use case. Named explicitly because the generated name exceeds MySQL's 64 character limit.
            $table->index(['subscriber_type', 'subscriber_id', 'event', 'status'], 'webhook_subscriptions_lookup_index');
        });
    }
};
