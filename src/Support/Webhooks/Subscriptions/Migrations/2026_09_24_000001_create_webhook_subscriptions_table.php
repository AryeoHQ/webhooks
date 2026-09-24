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

            // Declared manually (over uuidMorphs) in favor of the wider composite defined below
            $table->string('subscriber_type');
            $table->uuid('subscriber_id');

            $table->string('url');
            $table->string('version')->nullable();
            $table->json('headers')->nullable();
            $table->string('secret');
            $table->string('status');
            $table->timestampsTz();

            $table->index(['subscriber_type', 'subscriber_id', 'status'], 'webhook_subscriptions_lookup_index'); // Handles the collecting listener use case
        });
    }
};
