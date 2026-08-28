<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_inspirations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->comment('poster/activity');
            $table->unsignedBigInteger('activity_id')->nullable()->comment('活动灵感关联活动');
            $table->unsignedBigInteger('shop_id')->nullable()->comment('作者店铺');
            $table->string('title')->default('')->comment('标题');
            $table->string('image_url', 500)->nullable()->comment('海报图或封面图');
            $table->text('prompt')->nullable()->comment('详情提示词');
            $table->string('quick_prompt', 500)->nullable()->comment('快捷提示词描述');
            $table->unsignedInteger('sort')->default(100);
            $table->tinyInteger('is_online')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_online', 'sort'], 'ai_inspirations_list_idx');
            $table->index('activity_id', 'ai_inspirations_activity_idx');
            $table->index('shop_id', 'ai_inspirations_shop_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_inspirations');
    }
};
