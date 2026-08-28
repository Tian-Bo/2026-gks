<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopAiChatTables extends Migration
{
    public function up(): void
    {
        Schema::create('shop_ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id', 64)->unique()->comment('会话业务ID');
            $table->unsignedBigInteger('merchant_id')->comment('商户ID');
            $table->unsignedBigInteger('shop_id')->nullable()->comment('店铺ID');
            $table->string('scene', 64)->default('merchant_assistant')->comment('场景标识');
            $table->string('title', 120)->nullable()->comment('会话标题');
            $table->string('status', 32)->default('active')->comment('状态：active/archived');
            $table->json('meta')->nullable()->comment('扩展信息');
            $table->dateTime('latest_message_at')->nullable()->comment('最近消息时间');
            $table->timestamps();

            $table->index(['merchant_id']);
            $table->index(['shop_id']);
            $table->index(['scene']);
            $table->index(['latest_message_at']);
        });

        Schema::create('shop_ai_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_record_id')->comment('会话表主键ID');
            $table->string('message_id', 64)->unique()->comment('消息业务ID');
            $table->unsignedBigInteger('merchant_id')->comment('商户ID');
            $table->unsignedBigInteger('shop_id')->nullable()->comment('店铺ID');
            $table->string('conversation_id', 64)->comment('会话业务ID');
            $table->string('role', 32)->comment('角色：user/assistant');
            $table->string('status', 32)->comment('状态');
            $table->longText('content')->nullable()->comment('消息内容');
            $table->json('attachments')->nullable()->comment('附件');
            $table->json('component_result')->nullable()->comment('组件提交结果');
            $table->json('meta')->nullable()->comment('扩展信息');
            $table->string('error_code', 64)->nullable()->comment('错误码');
            $table->string('error_message', 500)->nullable()->comment('错误信息');
            $table->dateTime('started_at')->nullable()->comment('开始时间');
            $table->dateTime('completed_at')->nullable()->comment('完成时间');
            $table->dateTime('stopped_at')->nullable()->comment('停止时间');
            $table->timestamps();

            $table->index(['conversation_record_id']);
            $table->index(['merchant_id']);
            $table->index(['shop_id']);
            $table->index(['conversation_id']);
            $table->index(['role']);
            $table->index(['status']);
            $table->foreign('conversation_record_id')->references('id')->on('shop_ai_conversations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_ai_messages');
        Schema::dropIfExists('shop_ai_conversations');
    }
}
