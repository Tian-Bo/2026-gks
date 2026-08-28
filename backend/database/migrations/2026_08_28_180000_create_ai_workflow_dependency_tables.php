<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiWorkflowDependencyTables extends Migration
{
    public function up(): void
    {
        Schema::create('ai_merchants', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('name', 120);
            $table->string('password');
            $table->unsignedBigInteger('last_shop_id')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('name', 120);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['merchant_id', 'id']);
        });

        Schema::create('ai_merchant_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->index(['merchant_id', 'shop_id']);
        });

        Schema::create('ai_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('type', 32)->default('package');
            $table->string('title', 160);
            $table->string('cover', 2048)->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->integer('stock')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->index(['merchant_id', 'shop_id', 'status']);
        });

        Schema::create('ai_activities', function (Blueprint $table) {
            $table->id();
            $table->string('source_conversation_id', 64)->unique();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('activity_model_id')->default(1);
            $table->string('title', 160);
            $table->string('status', 32)->default('draft');
            $table->string('cover_img', 2048)->nullable();
            $table->string('background_color', 32)->nullable();
            $table->json('components')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
            $table->index(['merchant_id', 'shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_activities');
        Schema::dropIfExists('ai_items');
        Schema::dropIfExists('ai_merchant_access_tokens');
        Schema::dropIfExists('ai_shops');
        Schema::dropIfExists('ai_merchants');
    }
}
