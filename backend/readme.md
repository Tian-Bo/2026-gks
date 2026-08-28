# 快灵 AI Laravel 服务

这是参赛项目的独立 Laravel 12 后端。它承载独立前端 AI 工作流需要的全部接口：AI 对话、真实生图、积分、灵感、登录和店铺选择、商品选择、活动保存发布、上传与活动预览。员工、核销、订单、门店运营后台等非 AI 工作流能力不在本项目范围内。

## 方案

```text
frontend (Vue, :4173)
        |
        | /merchant/v1/shop/ai/*
        v
backend (Laravel, :4311)
        |
        v
独立 MySQL / SQLite
AI 会话、积分、灵感、商户、店铺、商品、活动
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

独立部署使用自己的数据库即可。`AI_MERCHANT_ID` 与 `AI_SHOP_ID` 仅是兼容历史 AI 数据的兜底上下文；正常使用时由登录接口签发的店铺级 `access_token` 决定商户和店铺范围。

为便于与原 AI 数据表并存，独立注册的商户与店铺使用高位 ID，并避开现有 AI 会话和积分表中的 ID 范围；不会读取或覆盖原系统的商户数据。

原 MySQL 在本机未启动时，可临时设置 `DB_CONNECTION=sqlite`，并将 `DB_DATABASE` 指向 `backend/database/ai.sqlite`，随后执行迁移创建本地 AI 演示数据表。该回退库仅用于独立开发和演示，不会同步原商户系统的数据。

## 本地 MySQL

当前开发环境已创建隔离的本地 MySQL 实例：`127.0.0.1:3307`，数据库 `kl_ai_local`，应用用户 `kl_ai`。连接密码仅保存在忽略的 `backend/.env`；MySQL 二进制、数据目录和 root 凭据也在忽略的 `backend/.runtime/`，不会进入 Git。

```bash
cd backend
./scripts/mysql-local.sh status
./scripts/mysql-local.sh start
./scripts/mysql-local.sh stop
```

Laravel 的 `DB_CONNECTION=mysql`、`DB_HOST=127.0.0.1`、`DB_PORT=3307` 已指向该实例。它与原项目的 `hdt_prod` 完全隔离；切回原库时可恢复忽略的 `backend/.env.mysql-unavailable.backup`，或重新填写原库的 `DB_*` 字段。

> 原库已有 AI 表时，**不要执行** `php artisan migrate`。本目录的三个迁移文件仅用于全新、独立的空数据库。

## 接口

| 方法 | 地址 | 用途 |
| --- | --- | --- |
| GET | `/merchant/v1/health` | 服务与原 MySQL 连接检查 |
| POST | `/common/v1/sendCode` | 登录/注册验证码（本地使用 `AI_DEMO_SMS_CODE`） |
| POST | `/merchant/v1/merchants` | 注册商户与默认店铺 |
| POST | `/merchant/v1/merchants/login` | 密码登录 |
| POST | `/merchant/v1/merchants/login/by/phone` | 验证码登录 |
| GET | `/merchant/v1/shops?order=desc` | 当前商户店铺列表 |
| POST | `/merchant/v1/patch/shops/{shop}/current` | 选择店铺并换发店铺令牌 |
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
| GET | `/merchant/v1/items` | AI 选品卡片数据 |
| GET/POST | `/merchant/v1/activities/{id}`、`/patch/activities/{id}` | 生成活动详情与主题保存 |
| POST | `/merchant/v1/patch/activity/release/{id}` | 发布生成活动 |
| POST | `/common/v1/upload` | 对话附件上传 |
| GET | `/common/v1/domain` | 返回独立活动预览域名 |

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
| `access_token` | `ai_merchant_access_tokens.token_hash` | 仅存令牌哈希，绑定商户和当前店铺 |
| 商品 `id/title/type/base_price` | `ai_items` 同名字段 | 活动选品数据 |
| 活动 `activity_id/cover_img/background_color` | `ai_activities.id/cover_img/background_color` | AI 生成活动与主题色 |
| 上传 `file` | `storage/app/ai-uploads/*` | 返回 `/common/v1/uploads/*` 可渲染地址 |

## 迁移来源

- `../apis/app/Http/Controllers/v1/Merchant/ShopAiChatsController.php`
- `../apis/app/Http/Controllers/v1/Merchant/ShopAiPointsController.php`
- `../apis/app/Http/Controllers/v1/Merchant/AiInspirationsController.php`
- `../apis/database/migrations/2026_05_11_220000_create_shop_ai_chat_tables.php`
- `../apis/database/migrations/2026_07_15_140000_create_shop_ai_point_tables.php`
- `../apis/database/migrations/2026_07_17_100000_create_ai_inspirations_table.php`

## 前端接入

本地验证时，在 `frontend/.env.development` 配置：

```dotenv
VITE_AI_API_BASE_URL=http://127.0.0.1:4311
```

前端所有 API 统一通过该地址调用；登录后 `Admin-Token` 对应店铺级令牌。不要在版本库中提交真实数据库、短信或生图密钥。
