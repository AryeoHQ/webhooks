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
            $table->uuidMorphs('subscriber');
            $table->string('event')->index();
            $table->string('url');
            $table->string('version')->nullable();
            $table->json('headers')->nullable();
            $table->string('secret');
            $table->string('status');
            $table->timestampsTz();
        });
    }
};
