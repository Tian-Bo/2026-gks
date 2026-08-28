<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopAiPointTables extends Migration
{
    public function up(): void
    {
        Schema::create('shop_ai_point_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->comment('店铺ID');
            $table->unsignedBigInteger('merchant_id')->default(0)->comment('商户ID');
            $table->unsignedInteger('balance')->default(0)->comment('可用灵点总余额');
            $table->unsignedInteger('monthly_grant_remaining')->default(0)->comment('本月月赠剩余（用于月末清理）');
            $table->unsignedTinyInteger('trial_activity_remaining')->default(0)->comment('免费版活动创建体验剩余次数');
            $table->unsignedTinyInteger('trial_poster_remaining')->default(0)->comment('免费版海报体验剩余次数');
            $table->timestamps();

            $table->unique('shop_id');
            $table->index('merchant_id');
        });

        Schema::create('shop_ai_point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->comment('店铺ID');
            $table->unsignedBigInteger('merchant_id')->default(0)->comment('商户ID');
            $table->string('direction', 16)->comment('credit/debit');
            $table->unsignedInteger('amount')->comment('点数（正数）');
            $table->unsignedInteger('balance_after')->comment('变动后余额');
            $table->string('source', 32)->comment('monthly_grant/admin_adjust/consume/monthly_expire/trial_consume');
            $table->string('billing_item', 64)->nullable()->comment('价目或体验项');
            $table->string('idempotency_key', 128)->comment('幂等键');
            $table->string('ref_type', 64)->nullable()->comment('关联类型');
            $table->string('ref_id', 64)->nullable()->comment('关联ID');
            $table->json('meta')->nullable()->comment('扩展信息');
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['shop_id', 'id']);
            $table->index('source');
        });

        Schema::create('shop_ai_point_monthly_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->comment('店铺ID');
            $table->string('grant_month', 7)->comment('YYYY-MM');
            $table->unsignedInteger('amount')->default(0)->comment('发放点数');
            $table->unsignedBigInteger('shop_model_id')->nullable()->comment('发放时版本');
            $table->timestamps();

            $table->unique(['shop_id', 'grant_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_ai_point_monthly_grants');
        Schema::dropIfExists('shop_ai_point_ledgers');
        Schema::dropIfExists('shop_ai_point_accounts');
    }
}
