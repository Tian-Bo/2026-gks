# AI 活动对话前端

快灵提供从活动需求输入、AI 对话生成到活动预览的完整工作流，包含首页、历史生成、对话页和预览组件。

## 技术栈

| 分类    | 技术                                | 用途                     |
| ----- | --------------------------------- | ---------------------- |
| 应用框架  | Vue 3、TypeScript、Vue Router       | 组件化页面、路由与类型约束。         |
| 构建与样式 | Vite、UnoCSS、Sass                  | 开发服务、生产构建和样式组织。        |
| 组件与交互 | Ant Design Vue、VueUse、GSAP、Lottie | 弹窗、选择器、常用交互和状态反馈。      |
| 实时通信  | `EventSource`（SSE）                | 实时接收 AI 思考、文本、图片和活动结果。 |

## 实现思路

前端只承担对话与结果呈现，不直接处理商家业务数据：登录后读取“选择店铺”接口返回的店铺级令牌；所有 AI 请求携带该令牌，由服务端按当前店铺限定数据范围。提交消息后，前端用 `stream_url` 建立 SSE 连接，将不同事件更新为思考过程、文本消息、图片卡片和活动预览。

生成结果确认后，前端先调用登录态同步接口，再在新窗口打开既有商家后台，并携带 `shop_id` 和 `activity_id`。因此 AI 工作台可独立部署，同时仍能进入正确店铺完成活动编辑和发布。

## 运行

```bash
npm install
npm run dev
```

默认地址打开 AI 首页；从首页提交后进入对话页，顶部历史入口进入历史生成页。开发环境通过 `VITE_AI_API_BASE_URL` 配置 AI 服务地址。`?mock=1` 仅用于静态流程演示。

| 路径         | 页面      | 进入方式              |
| ---------- | ------- | ----------------- |
| `/`        | AI 首页   | 默认入口，选择活动/海报并输入需求 |
| `/chat`    | AI 对话生成 | 首页提交或从历史记录继续编辑    |
| `/history` | 历史生成    | 首页、对话页右上角的历史入口    |

## 项目结构

页面所需的接口、存储与预览能力集中在 `src/standalone/`：

| 模块             | 位置                                                 | 说明           |
| -------------- | -------------------------------------------------- | ------------ |
| AI 会话与提示词接口 | `standalone/api.ts`                                | 统一封装 AI 服务请求 |
| 上传接口           | `standalone/request.ts`                            | 图片上传请求适配     |
| 活动预览地址         | `standalone/activityPreviewUrl.ts`                 | 活动预览页地址解析    |
| 商户存储与提示消息      | `standalone/storage.ts`、`standalone/klbMessage.ts` | 本地状态与提示消息    |

## 接口与字段映射

| 页面接口                    | 关键输入字段                                                       | 页面使用字段                                                                 | 服务处理          |
| ----------------------- | ------------------------------------------------------------ | ---------------------------------------------------------------------- | ------------- |
| `getAiPageConfig`       | 无                                                            | `styles`、`activityModels`、`sizes`、`models`、`defaults`                  | 返回相同配置结构      |
| `getAiInspirations`     | `type`、`page`、`per_page`                                     | `items[]`、`quick_prompts[]`                                            | 首页灵感卡片和快捷输入   |
| `getAiConversationList` | `shop_id`、`page`、`per_page`                                  | `items[].conversation_id`、`title`、`scene`、`preview_image`、`updated_at` | 历史生成列表        |
| `getAiPromptTips`       | `type`                                                       | `items[].title`、`items[].content`、`items[].cover`                      | 按活动/海报类型返回提示词 |
| `sendAiMessage`         | `conversation_id`、`content`、`attachments`、`component_result` | `message_id`、`content`、`components`、`meta`                             | 创建或续写对话       |
| `getUnifiedItemList`    | 商品筛选条件                                                       | `id`、`title`、`cover`、`base_price`、`stock`                              | 提供可选商品        |

| 业务字段  | 对话字段                                   | 生成结果字段     |
| ----- | -------------------------------------- | ---------- |
| 门店    | `shop_id`                              | 生成上下文      |
| 目标和时间 | `component_result`                     | 活动方案约束     |
| 商品    | `item_id`、`product_ids`                | 商品卡片与活动卖点  |
| 风格和模型 | `style`、`activity_model`、`image_model` | 页面风格、模板与封面 |
