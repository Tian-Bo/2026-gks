# 快灵 AI

快灵 AI 是一个面向商家的 AI 活动生成工具。商家通过对话描述活动需求、选择商品和图片风格，系统生成活动方案、宣传图片和活动预览；确认后可进入商家后台完成发布。

## 本项目能力

- AI 对话生成活动方案和宣传素材
- AI 生成图片、活动预览和结果卡片
- 灵感推荐、生成记录和继续编辑
- 商品选择、图片上传和生成参数选择
- 登录引导、店铺识别和令牌失效处理
- 采用活动或发布活动，并跳转至对应店铺的商家后台

## 技术栈

| 部分   | 技术                                                     |
| ---- | ------------------------------------------------------ |
| 前端   | Vue 3、TypeScript、Vite、Vue Router、UnoCSS、Ant Design Vue |
| 实时生成 | SSE（流式返回 AI 对话和生成进度）                                   |
| 后端   | PHP 8.0、Laravel 8、Guzzle、cURL                          |
| 部署   | Nginx、PHP-FPM                                          |

## 项目边界

### 48 小时内完成

- 独立的快灵 AI 前端页面和 Laravel 接口
- AI 对话、流式生成、图片展示、活动预览和生成记录
- 登录弹窗、店铺令牌处理和未登录引导
- 与商家后台的登录态同步、采用活动和发布活动跳转

### 复用已有业务能力

- 商家登录、店铺选择和商品数据
- 活动编辑、发布和运营后台
- 员工、订单、核销等业务能力

这些已有能力只作为本项目的跳转承接；AI 对话、生成、预览和工作台页面属于本项目实现。

## 核心接口与字段

| 功能    | 接口                                                      | 关键字段                                                |
| ----- | ------------------------------------------------------- | --------------------------------------------------- |
| 选择店铺  | `POST /merchant/v1/patch/shops/{shopId}/current`        | 返回店铺级 `access_token` 与 `shop_id`                    |
| AI 对话 | `POST /merchant/v1/shop/ai/messages`                    | `content`、`conversation_id`、`attachments`、`options` |
| 流式生成  | `GET /merchant/v1/shop/ai/messages/{messageId}/stream`  | 返回文本、图片和活动结果                                        |
| 生成记录  | `GET /merchant/v1/shop/ai/conversations`                | `shop_id`、`conversation_id`                         |
| 发布活动  | `POST /merchant/v1/patch/activity/release/{activityId}` | `activity_id`、`shop_id`                             |

字段关系：`access_token` 和 `shop_id` 用于确认当前店铺；`content` 是商家的对话输入；`attachments` 是已选商品和图片；`options` 包含风格、尺寸和图片模型；`activity_id` 用于预览和发布活动。

## 目录

| 目录         | 说明         |
| ---------- | ---------- |
| `frontend` | 快灵 AI 前端页面 |
| `backend`  | 快灵 AI 接口网关 |

详细说明见 [frontend/readme.md](frontend/readme.md) 与 [backend/readme.md](backend/readme.md)。
