# 快灵 AI Laravel 服务

这是参赛项目的独立 Laravel 12 后端，只承载 AI 对话、AI 灵点和 AI 灵感推荐。它复用原项目 `../apis` 的 MySQL AI 数据表，不依赖活动、商品、员工或核销等业务代码。

## 方案

```text
frontend (Vue, :4173)
        |
        | /merchant/v1/shop/ai/*
        v
backend (Laravel, :4311)
        |
        v
原项目 MySQL
shop_ai_conversations / shop_ai_messages
shop_ai_point_accounts / shop_ai_point_ledgers
ai_inspirations
```

`backend/legacy-node` 保留了此前的 Node 版本，方便回退和比对；Laravel 是当前主后端。

## 启动

本机需要 PHP 8.2+、Composer 和 `pdo_mysql` 扩展。

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
composer serve
```

服务地址为 `http://127.0.0.1:4311`，健康检查为 `GET /merchant/v1/health`。

将原后端 `../apis/.env` 中的 `DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD` 填入本项目 `backend/.env`，即可读写同一数据库。`AI_MERCHANT_ID` 与 `AI_SHOP_ID` 是独立演示模式下的上下文；接入原商户登录后，应由认证中间件替换这两个固定值。

原 MySQL 在本机未启动时，可临时设置 `DB_CONNECTION=sqlite`，并将 `DB_DATABASE` 指向 `backend/database/ai.sqlite`，随后执行迁移创建本地 AI 演示数据表。该回退库仅用于独立开发和演示，不会同步原商户系统的数据。

> 原库已有 AI 表时，**不要执行** `php artisan migrate`。本目录的三个迁移文件仅用于全新、独立的空数据库。

## 接口

| 方法 | 地址 | 用途 |
| --- | --- | --- |
| GET | `/merchant/v1/health` | 服务与原 MySQL 连接检查 |
| GET | `/merchant/v1/shop/ai/config` | 对话页风格、尺寸、模型配置 |
| GET | `/merchant/v1/shop/ai/prompt-tips?type=activity\|poster` | AI 提示技巧 |
| GET | `/merchant/v1/shop/ai/conversations` | 历史会话分页 |
| GET | `/merchant/v1/shop/ai/conversations/{conversationId}/messages` | 单会话消息分页 |
| POST | `/merchant/v1/shop/ai/messages` | 创建用户消息和待生成助手消息 |
| GET | `/merchant/v1/shop/ai/messages/{messageId}/stream` | SSE 流式生成 |
| POST | `/merchant/v1/shop/ai/messages/{messageId}/stop` | 停止生成 |
| GET | `/merchant/v1/shop/ai/points` | 灵点余额与体验次数 |
| GET | `/merchant/v1/shop/ai/points/ledgers` | 灵点流水分页 |
| GET | `/merchant/v1/shop/ai/inspirations` | AI 灵感推荐分页 |
| GET | `/merchant/v1/shop/ai/inspirations/{id}` | 灵感详情 |

`POST /messages` 请求字段：`content` 必填；可选 `conversation_id`、`user_message_id`、`shop_id`、`scene`、`attachments[]`、`component_result{}`、`options{style,aspect_ratio,activity_model,image_model,thinking_mode}`。响应给出 `conversation`、`user_message`、`assistant_message` 和 `stream_url`。

SSE 事件与原接口一致：`connected`、`message_start`、`thinking_delta`、`message_delta`、`message_card`、`message_completed`、`done`。

## 字段映射

| API 字段 | 数据表字段 | 说明 |
| --- | --- | --- |
| `conversation_id` | `shop_ai_conversations.conversation_id` | 会话业务 ID |
| `title`、`scene`、`status` | `shop_ai_conversations.title/scene/status` | 会话展示与状态 |
| `current_selection` | `shop_ai_conversations.meta.current_selection` | 风格、尺寸和模型选择 |
| `message_id` | `shop_ai_messages.message_id` | 消息业务 ID |
| `content`、`role`、`status` | `shop_ai_messages.content/role/status` | 对话正文和状态 |
| `attachments` | `shop_ai_messages.attachments` | 用户附件 JSON |
| `components`、`poster`、`activity` | `shop_ai_messages.meta` | AI 结果卡片 JSON |
| `balance` | `shop_ai_point_accounts.balance` | 可用灵点 |
| `trial.*_remaining` | `shop_ai_point_accounts.trial_*_remaining` | 免费体验次数 |
| 灵感 `title/image_url/prompt` | `ai_inspirations` 同名字段 | 已上架灵感内容 |

## 迁移来源

- `../apis/app/Http/Controllers/v1/Merchant/ShopAiChatsController.php`
- `../apis/app/Http/Controllers/v1/Merchant/ShopAiPointsController.php`
- `../apis/app/Http/Controllers/v1/Merchant/AiInspirationsController.php`
- `../apis/database/migrations/2026_05_11_220000_create_shop_ai_chat_tables.php`
- `../apis/database/migrations/2026_07_15_140000_create_shop_ai_point_tables.php`
- `../apis/database/migrations/2026_07_17_100000_create_ai_inspirations_table.php`
