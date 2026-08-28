const apiBaseUrl = String(import.meta.env.VITE_AI_API_BASE_URL || 'https://apis.kuailiebian.cn').replace(/\/+$/, '')

type QueryValue = string | number | boolean | null | undefined
type Query = Record<string, QueryValue>

function getAccessToken() {
  const configured = String(import.meta.env.VITE_AI_ACCESS_TOKEN || '').trim()
  if (configured)
    return configured

  const cookie = document.cookie.match(/(?:^|;\s*)Admin-Token=([^;]*)/)
  return cookie ? decodeURIComponent(cookie[1]) : ''
}

function withAccessToken(query: Query = {}) {
  if (query.access_token)
    return query

  const token = getAccessToken()
  return token ? { ...query, access_token: token } : query
}

function buildUrl(path: string, query: Query = {}) {
  const url = new URL(path, `${apiBaseUrl}/`)
  Object.entries(withAccessToken(query)).forEach(([key, value]) => {
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

function normalizeActivityStage(params: Query) {
  const { stage, ...rest } = params
  // `ai_activity_theme` was introduced by the extracted page only. The
  // original merchant API returns the full component set when stage is absent.
  return stage === 'ai_activity_theme' ? rest : params
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
    buildAiStreamUrl: (streamUrl: string) => buildUrl(streamUrl),
    toggleContentReaction: () => unsupported('内容点赞'),
  },
  goods: {
    getUnifiedItemList: (params: Query = {}) => request('/merchant/v1/items', {}, params),
  },
  activity: {
    getActivityDetail: (activityId: number, params: Query = {}) => request(`/merchant/v1/activities/${activityId}`, {}, normalizeActivityStage(params)),
    updateActivity: (activityId: number, data: Record<string, unknown>, params: Query = {}) => request(`/merchant/v1/patch/activities/${activityId}`, {
      method: 'POST',
      body: JSON.stringify(data),
    }, normalizeActivityStage(params)),
    releaseActivity: (activityId: number, data: Record<string, unknown> = { is_create: 1 }) => request(`/merchant/v1/patch/activity/release/${activityId}`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  },
}

export default api
