# AI 活动对话前端

从商家后台的 AI 活动对话页抽取出的独立 Vue 3 应用，复用原有 Ant Design Vue、快灵按钮、日期组件和图标字体。

## 运行

```bash
npm install
npm run dev
```

默认使用本地演示适配器，可独立运行。设置 `VITE_AI_ACTIVITY_API_URL` 后，`生成活动草稿` 将向该地址发送真实请求。

## 模块接口

| 接口 | 输入 | 输出 |
| --- | --- | --- |
| `生成活动草稿` | `messages`、`storeId`、`productIds`、`objective`、`schedule`、`style` | `content`、`summary`、`plan` |

## 字段映射

| 底座字段/用户输入 | AI 活动草稿 |
| --- | --- |
| `storeId` | 门店上下文 |
| `productIds` | 商品上下文 |
| `objective` | 活动目标 |
| `schedule` | 活动周期 |
| `style` | 页面风格 |
| `plan.title/subtitle/incentive/rule` | 活动预览内容 |
