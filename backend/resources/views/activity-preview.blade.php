<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>{{ $activity?->title ?: '活动预览' }}</title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; background: {{ $activity?->background_color ?: '#fff6e5' }}; color: #182033; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", sans-serif; }
    .hero { min-height: 210px; padding: 30px 24px; color: #fff; background: #182033 center/cover no-repeat; background-image: linear-gradient(180deg, rgba(15,24,42,.14), rgba(15,24,42,.66)), url('{{ $activity?->cover_img }}'); }
    .hero__tag { display: inline-block; padding: 5px 9px; border-radius: 4px; background: rgba(255,255,255,.2); font-size: 12px; }
    .hero h1 { margin: 76px 0 0; font-size: 26px; line-height: 1.3; }
    .content { padding: 20px 16px 42px; }
    .section { margin-bottom: 14px; padding: 18px; border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(15,24,42,.06); }
    .section h2 { margin: 0 0 10px; font-size: 17px; }
    .section p { margin: 0; color: #526071; font-size: 14px; line-height: 1.75; white-space: pre-line; }
    .button { width: 100%; margin-top: 12px; padding: 14px; border: 0; border-radius: 8px; color: #fff; background: #e62222; font-size: 16px; font-weight: 600; }
  </style>
</head>
<body>
  <section class="hero"><span class="hero__tag">快灵 AI 活动</span><h1>{{ $activity?->title ?: '活动正在生成中' }}</h1></section>
  <main class="content">
    <section class="section"><h2>活动亮点</h2><p>{{ data_get($activity?->meta, 'draft.goal.label', '为门店设计更高效的营销活动') }}</p></section>
    <section class="section"><h2>活动周期</h2><p>{{ data_get($activity?->meta, 'draft.duration.label', '活动时间以门店最终发布配置为准') }}</p></section>
    <section class="section"><h2>主推项目</h2><p>{{ data_get($activity?->meta, 'draft.item_requirement', '精选门店主推项目，限时参与') }}</p><button class="button" type="button">立即参与</button></section>
  </main>
</body>
</html>
