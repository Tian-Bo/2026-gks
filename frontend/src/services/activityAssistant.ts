export type ActivityGenerationRequest = {
  messages: string[]
  storeId: string
  productIds: string[]
  objective: string
  schedule: { start: string, end: string, label: string }
  style: string
}

export type ActivityGenerationResult = {
  content: string
  summary: string[]
  plan: {
    title: string
    subtitle: string
    incentive: string
    rule: string
  }
}

const configuredApiUrl = import.meta.env.VITE_AI_ACTIVITY_API_URL?.trim()

export async function generateActivityPlan(request: ActivityGenerationRequest): Promise<ActivityGenerationResult> {
  if (configuredApiUrl) {
    const response = await fetch(configuredApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request),
    })
    if (!response.ok)
      throw new Error('活动生成服务暂不可用')
    return response.json() as Promise<ActivityGenerationResult>
  }

  await new Promise(resolve => window.setTimeout(resolve, 1200))
  const productLabel = request.productIds.length ? `${request.productIds.length} 个主推商品` : '店铺主推商品'
  return {
    content: `活动方案已生成。我已围绕${request.objective || '拉新获客'}，结合${productLabel}与${request.style || '通用'}风格，整理出一条可直接发布的活动主链路。`,
    summary: [
      `目标：${request.objective || '拉新获客'}`,
      `周期：${request.schedule.label || '近期活动'}`,
      `风格：${request.style || '通用风格'}`,
    ],
    plan: {
      title: '到店即领新人礼',
      subtitle: '邀请好友同行，额外解锁限时福利',
      incentive: '新人到店立减 30 元，邀请 1 位好友再得加赠券',
      rule: '活动规则已完成基础校验，可进入预览确认。',
    },
  }
}
