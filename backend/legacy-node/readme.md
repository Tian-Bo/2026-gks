# 快灵 AI Node 服务

这是提供 AI 首页、历史生成和对话页接口的 Node 服务。

## 运行

要求 Node.js 18 或更高版本，无需安装第三方依赖。

```bash
npm start
```

默认监听 `http://127.0.0.1:4311`。开发时可运行 `npm run dev`。

## 数据库连接

默认的 `mysql` 模式会连接 MySQL 数据库，并直接读写 AI 相关表：`shop_ai_conversations`、`shop_ai_messages`、`shop_ai_point_accounts`、`ai_inspirations`。

本机开发时，服务从环境变量读取数据库配置。部署时请基于 `.env.example` 创建 `backend/.env`，或使用 `AI_DB_*` 环境变量注入凭据。

若只需要离线演示，可设置 `AI_STORAGE_DRIVER=file`，服务会使用 `data/ai-store.json`。该文件由服务自动创建。

## 方案

| 层 | 实现 | 说明 |
| --- | --- | --- |
| 路由层 | Node HTTP 服务 | AI 路径和 JSON/SSE 响应形式 |
| 会话层 | `src/ai-service.mjs` | 会话、用户消息、助理消息、停止生成和历史分页 |
| 生成层 | 同一服务中的流式生成器 | 保留 `thinking_delta`、`message_delta`、`message_card`、`message_completed`、`done` 事件 |
| 配置层 | `src/catalog.mjs` | 活动模型、风格、画幅、提示词和灵感卡片结构 |
| 数据层 | `src/mysql-repository.mjs` | 直接读写 AI 表；`src/store.mjs` 仅作为离线回退 |

## 接口

| 方法 | 路径 | 作用 |
| --- | --- | --- |
| `GET` | `/health` | 服务健康检查 |
| `GET` | `/merchant/v1/shop/ai/config` | 获取活动模型、风格、画幅和图像模型 |
| `GET` | `/merchant/v1/shop/ai/prompt-tips?type=activity` | 获取活动或海报提示词 |
| `GET` | `/merchant/v1/shop/ai/inspirations?type=all` | 获取首页灵感卡片和快捷提示词 |
| `GET` | `/merchant/v1/shop/ai/points` | 获取 AI 积分余额 |
| `GET` | `/merchant/v1/shop/ai/conversations` | 获取历史生成会话 |
| `GET` | `/merchant/v1/shop/ai/conversations/{conversationId}/messages` | 获取对话消息 |
| `POST` | `/merchant/v1/shop/ai/messages` | 创建或续写会话 |
| `GET` | `/merchant/v1/shop/ai/messages/{assistantMessageId}/stream` | 接收 SSE 生成事件 |
| `POST` | `/merchant/v1/shop/ai/messages/{assistantMessageId}/stop` | 停止生成 |
| `POST` | `/merchant/v1/content/reactions/toggle` | 点赞灵感卡片 |

## 字段映射

| 前端字段 | 接口字段 | 持久化位置 | 用途 |
| --- | --- | --- | --- |
| 门店上下文 | `shop_id` | `conversations[].shop_id` | 对话归属和历史筛选 |
| 活动/海报模式 | `scene`、`component_result.mode` | `conversations[].scene`、`meta.mode` | 选择活动卡或海报预览流 |
| 用户诉求 | `content` | `messages[].content` | 生成输入与会话标题 |
| 工具栏选择 | `options.style`、`activity_model`、`image_model`、`thinking_mode` | `conversations[].meta.current_selection` | 恢复对话页的选择状态 |
| 图片附件 | `attachments[]` | `messages[].attachments` | 保留输入图片引用 |
| 卡片确认 | `component_result` | `messages[].component_result` | 记录目标、时间、商品和风格确认 |
| 生成卡片 | SSE `message_card.card` | `messages[].meta.components` | 驱动活动步骤卡或海报预览卡 |

## 与前端对接

接入此服务时，将前端 AI 适配层的请求基址配置为 `http://127.0.0.1:4311`，并使用 `/merchant/v1/shop/ai/*` 路径。
