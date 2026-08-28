const apiBaseUrl = String(import.meta.env.VITE_AI_API_BASE_URL || 'http://127.0.0.1:4311').replace(/\/+$/, '')

type QueryValue = string | number | boolean | null | undefined
type Query = Record<string, QueryValue>

function buildUrl(path: string, query: Query = {}) {
  const url = new URL(path, `${apiBaseUrl}/`)
  Object.entries(query).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '')
      url.searchParams.set(key, String(value))
  })
  return url.toString()
}

function toApiError(status: number, body: unknown) {
  const message = typeof body === 'object' && body !== null && 'message' in body
    ? String((body as { message?: unknown }).message || '')
    : ''
  return new Error(message || `AI 服务请求失败（${status}）`)
}

async function request<T>(path: string, init: RequestInit = {}, query: Query = {}): Promise<T> {
  const response = await fetch(buildUrl(path, query), {
    ...init,
    headers: {
      Accept: 'application/json',
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
      ...init.headers,
    },
  })
  const body = await response.json().catch(() => null)
  if (!response.ok)
    throw toApiError(response.status, body)
  return body as T
}

function unsupported(module: string): never {
  throw new Error(`${module} 不属于独立 AI 服务范围`)
}

const api = {
  ai: {
    getAiPageConfig: () => request('/merchant/v1/shop/ai/config'),
    getAiPoints: (params: Query = {}) => request('/merchant/v1/shop/ai/points', {}, params),
    getAiPromptTips: (params: Query = {}) => request('/merchant/v1/shop/ai/prompt-tips', {}, params),
    getAiConversationList: (params: Query = {}) => request('/merchant/v1/shop/ai/conversations', {}, params),
    getAiConversationMessages: (conversationId: string, params: Query = {}) => request(`/merchant/v1/shop/ai/conversations/${encodeURIComponent(conversationId)}/messages`, {}, params),
    getAiInspirations: (params: Query = {}) => request('/merchant/v1/shop/ai/inspirations', {}, params),
    getAiInspirationDetail: (id: number) => request(`/merchant/v1/shop/ai/inspirations/${id}`),
    sendAiMessage: (data: Record<string, unknown>) => request('/merchant/v1/shop/ai/messages', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
    stopAiMessage: (assistantMessageId: string) => request(`/merchant/v1/shop/ai/messages/${encodeURIComponent(assistantMessageId)}/stop`, {
      method: 'POST',
      body: JSON.stringify({}),
    }),
    buildAiMessageStreamUrl: (assistantMessageId: string) => buildUrl(`/merchant/v1/shop/ai/messages/${encodeURIComponent(assistantMessageId)}/stream`),
    buildAiStreamUrl: (streamUrl: string) => /^https?:\/\//i.test(streamUrl)
      ? streamUrl
      : buildUrl(streamUrl),
    toggleContentReaction: () => unsupported('内容点赞'),
  },
  goods: {
    getUnifiedItemList: async () => ({ items: [], total: 0 }),
  },
  activity: {
    getActivityDetail: () => unsupported('活动详情'),
    updateActivity: () => unsupported('活动编辑'),
    releaseActivity: () => unsupported('活动发布'),
  },
}

export default api
