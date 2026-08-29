# 快灵 AI Laravel 服务

这是快灵项目的 Laravel 8 。 AI 服务负责对话、文本模型、生图模型、提示词、队列、图片资产和活动生成结果。

## 技术栈

| 分类      | 技术                              | 用途                      |
| ------- | ------------------------------- | ----------------------- |
| 运行环境    | PHP 8.0、Laravel 8               | 保持与现有服务器 PHP 版本兼容。      |
| HTTP 转发 | Laravel HTTP Client、Guzzle、cURL | 透传常规请求、文件上传与 SSE 流。     |
| 跨域与会话   | Fruitcake CORS、HTTP Cookie      | 支持独立前端跨域调用和后台登录态同步。     |
| 部署      | Nginx、PHP-FPM                   | 为独立域名提供 Laravel Web 入口。 |

## 启动

本机和线上需要 PHP 8.0+、Composer，并启用 `curl`、`pdo_mysql`、`mbstring`、`openssl`、`json` 与 `fileinfo` 扩展。

> 本项目为比赛演示使用 Laravel 8 以兼容现有 PHP 8.0 环境。Laravel 8 已停止安全维护，不应将该版本作为长期公网业务服务。

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
composer serve
```

服务地址为 `http://127.0.0.1:4311`，健康检查为 `GET /merchant/v1/health`。

## PHP 8.0 部署

代码已锁定为 Laravel `8.83.29`，`composer.json` 也以 PHP `8.0.0` 解析依赖，因此不要求服务器升级到 PHP 8.2。网关模式不读取本地商户、令牌、活动或 AI 数据库；部署时没有可用 MySQL 也能启动。Web 根目录必须指向 `backend/public`，并执行：

```bash
cd backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan config:cache
php artisan route:cache
```

PHP 需启用 `curl`、`pdo_mysql`、`mbstring`、`openssl`、`json`、`fileinfo` 和 `dom` 扩展。为让浏览器可访问前端域名，生产环境在 `.env` 设置 `CORS_ALLOWED_ORIGINS=https://你的前端域名`；多个域名以逗号分隔。

若需从快灵 AI 免登录跳转至裂变快商户后台，还需设置：

```dotenv
MERCHANT_ADMIN_COOKIE_DOMAIN=.liebiankuai.com
MERCHANT_ADMIN_COOKIE_SECURE=true
```

前端域名应加入 `CORS_ALLOWED_ORIGINS`。跳转前会调用 `POST /merchant/v1/sso/admin/session`，该接口使用当前店铺 `access_token` 校验身份，并写入裂变快后台读取的登录 Cookie；令牌不会出现在跳转链接中。

Nginx 可直接使用 `deploy/nginx-laravel8.conf`：将 `server_name`、`root` 与 PHP 8.0-FPM 的 socket 路径替换为服务器实际值，然后重载 Nginx 和 PHP-FPM。部署后的自检顺序如下：

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
curl http://你的后端域名/merchant/v1/health
```

预期健康检查返回 `{"status":"ok","service":"kl-ai-laravel","mode":"upstream-gateway"}`。如返回 502，检查 PHP-FPM socket；如返回 403/404，检查 Nginx 的 `root` 是否精确指向 `backend/public`；如返回 500，检查 `storage` 和 `bootstrap/cache` 是否对 PHP-FPM 运行用户可写。网关健康检查不依赖本地数据库或 `APP_KEY`，因此可先用它确认 Nginx 和 PHP-FPM 已正确接入。

默认使用 `AI_UPSTREAM_API_BASE_URL=http://klapis.liebiankuai.com`。AI 相关请求会转发至该地址，登录后由服务签发店铺级 `access_token`，用于限定商户和店铺范围。

## 接口

| 方法       | 地址                                                             | 用途             |
| -------- | -------------------------------------------------------------- | -------------- |
| GET      | `/merchant/v1/health`                                          | 本地网关健康检查       |
| POST     | `/common/v1/sendCode`                                          | 线上登录/注册验证码     |
| POST     | `/merchant/v1/merchants`                                       | 线上商户注册         |
| POST     | `/merchant/v1/merchants/login`                                 | 线上商户密码登录       |
| POST     | `/merchant/v1/merchants/login/by/phone`                        | 线上商户验证码登录      |
| GET      | `/merchant/v1/shops?order=desc`                                | 当前商户店铺列表       |
| POST     | `/merchant/v1/patch/shops/{shop}/current`                      | 选择店铺并换发店铺令牌    |
| GET      | `/merchant/v1/shop/ai/config`                                  | 对话页风格、尺寸、模型配置  |
| GET      | `/merchant/v1/shop/ai/prompt-tips?type=activity\|poster`       | AI 提示技巧        |
| GET      | `/merchant/v1/shop/ai/conversations`                           | 历史会话分页         |
| GET      | `/merchant/v1/shop/ai/conversations/{conversationId}/messages` | 单会话消息分页        |
| POST     | `/merchant/v1/shop/ai/messages`                                | 创建用户消息和待生成助手消息 |
| GET      | `/merchant/v1/shop/ai/messages/{messageId}/stream`             | SSE 流式生成       |
| POST     | `/merchant/v1/shop/ai/messages/{messageId}/stop`               | 停止生成           |
| GET      | `/merchant/v1/shop/ai/points`                                  | 灵点余额与体验次数      |
| GET      | `/merchant/v1/shop/ai/points/ledgers`                          | 灵点流水分页         |
| GET      | `/merchant/v1/shop/ai/inspirations`                            | AI 灵感推荐分页      |
| GET      | `/merchant/v1/shop/ai/inspirations/{id}`                       | 灵感详情           |
| GET      | `/merchant/v1/items`                                           | AI 选品卡片数据      |
| GET/POST | `/merchant/v1/activities/{id}`、`/patch/activities/{id}`        | 生成活动详情与主题保存    |
| POST     | `/merchant/v1/patch/activity/release/{id}`                     | 发布生成活动         |
| POST     | `/common/v1/upload`                                            | 对话附件上传         |
| GET      | `/common/v1/domain`                                            | 返回独立活动预览域名     |

除 `GET /merchant/v1/health` 外，上表接口均由网关透明转发。请求字段、响应字段、错误状态和模型行为以线上服务为准；`POST /messages` 的 `content`、`conversation_id`、`user_message_id`、`shop_id`、`scene`、`attachments[]`、`component_result{}` 和 `options{style,aspect_ratio,activity_model,image_model,thinking_mode}` 不在网关中转换。

SSE 事件与原接口一致：`connected`、`message_start`、`thinking_delta`、`message_delta`、`message_card`、`message_completed`、`done`。

## 字段映射

| API 字段                           | 服务字段        | 网关处理             |
| -------------------------------- | ----------- | ---------------- |
| `access_token`                   | 店铺令牌        | 原样透传，不重新签发       |
| `shop_id`                        | 店铺 ID       | 原样透传，不重映射        |
| `conversation_id`、`message_id`   | AI 会话与消息 ID | 原样透传             |
| `component_result`、`options`     | AI 工作流输入    | 原样透传给规划器和模型服务    |
| `components`、`poster`、`activity` | AI 结果卡片     | 原样返回，包含实际模型和图片地址 |
| `file`                           | 上传服务        | 以 multipart 原样转发 |

## 前端接入

本地验证时，在 `frontend/.env.development` 配置：

```dotenv
VITE_AI_API_BASE_URL=http://127.0.0.1:4311
```

前端所有 API 统一通过该地址调用；登录后 `Admin-Token` 对应店铺级令牌。不要在版本库中提交真实数据库、短信或生图密钥。
