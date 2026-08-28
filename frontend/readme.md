# AI 活动对话前端

这是从商家后台直接迁移出的 AI 工作流：首页、历史生成、对话页、预览组件、样式、图标字体和原有 mock 会话数据均复用原始实现。它不是按截图重新搭建的页面。

## 运行

```bash
npm install
npm run dev
```

默认地址打开 AI 首页；从首页提交后进入对话页，顶部历史入口进入历史生成页。`?mock=0` 预留给后续接入真实服务；在未实现适配器前，不应作为演示入口。

| 路径 | 页面 | 进入方式 |
| --- | --- | --- |
| `/` | AI 首页 | 默认入口，选择活动/海报并输入需求 |
| `/chat` | AI 对话生成 | 首页提交或从历史记录继续编辑 |
| `/history` | 历史生成 | 首页、对话页右上角的历史入口 |

## 独立化方案

页面层保持商家后台的原逻辑，商家系统特有依赖集中在 `src/standalone/`：

| 原依赖 | 独立项目处理 | 说明 |
| --- | --- | --- |
| AI 会话、积分、提示词接口 | `standalone/api.ts` | 当前由原页面的 mock 会话展示；后续在此接入新服务 |
| 上传接口 | `standalone/request.ts` | 预留图片上传适配 |
| 活动预览地址 | `standalone/activityPreviewUrl.ts` | 预留新项目预览页地址 |
| 商户存储与提示消息 | `standalone/storage.ts`、`standalone/klbMessage.ts` | 独立运行所需的最小替代 |

## 接口与字段映射

| 原页面接口 | 关键输入字段 | 页面使用字段 | 独立服务建议 |
| --- | --- | --- | --- |
| `getAiPageConfig` | 无 | `styles`、`activityModels`、`sizes`、`models`、`defaults` | 返回相同配置结构 |
| `getAiInspirations` | `type`、`page`、`per_page` | `items[]`、`quick_prompts[]` | 首页灵感卡片和快捷输入 |
| `getAiConversationList` | `shop_id`、`page`、`per_page` | `items[].conversation_id`、`title`、`scene`、`preview_image`、`updated_at` | 历史生成列表 |
| `getAiPromptTips` | `type` | `items[].title`、`items[].content`、`items[].cover` | 按活动/海报类型返回提示词 |
| `sendAiMessage` | `conversation_id`、`content`、`attachments`、`component_result` | `message_id`、`content`、`components`、`meta` | 创建或续写对话 |
| `getUnifiedItemList` | 商品筛选条件 | `id`、`title`、`cover`、`base_price`、`stock` | 提供可选商品 |

| 业务字段 | 对话字段 | 生成结果字段 |
| --- | --- | --- |
| 门店 | `shop_id` | 生成上下文 |
| 目标和时间 | `component_result` | 活动方案约束 |
| 商品 | `item_id`、`product_ids` | 商品卡片与活动卖点 |
| 风格和模型 | `style`、`activity_model`、`image_model` | 页面风格、模板与封面 |
