// Keep the standalone page on the same production API used by the original merchant app.
const apiBaseUrl = String(import.meta.env.VITE_AI_API_BASE_URL || 'https://apis.liebiankuai.com').replace(/\/+$/, '')

type QueryValue = string | number | boolean | null | undefined
type Query = Record<string, QueryValue>
type AuthResult = { access_token: string; shop_id?: number; default_shop_id?: number }
type ShopListResult = { current_shop_id?: number; items?: Array<{ id: number }> }
let redirectingForExpiredToken = false
const forcedLogoutStorageKey = 'kl_ai_forced_logout'

function getSelectedShopToken() {
  if (window.sessionStorage.getItem(forcedLogoutStorageKey) === '1')
    return ''

  // The merchant app replaces this cookie with the shop-scoped token returned
  // by POST /merchant/v1/patch/shops/{shopId}/current.
  const cookie = document.cookie.match(/(?:^|;\s*)Admin-Token=([^;]*)/)
  if (cookie)
    return decodeURIComponent(cookie[1])

  // Reserved for standalone deployments that cannot share the merchant cookie.
  return String(import.meta.env.VITE_AI_ACCESS_TOKEN || '').trim()
}

export function hasAiAccessToken() {
  return Boolean(getSelectedShopToken())
}

export function saveSelectedShopToken(token: string) {
  window.sessionStorage.removeItem(forcedLogoutStorageKey)
  document.cookie = `Admin-Token=${encodeURIComponent(token)}; Path=/; SameSite=Lax`
}

export function clearSelectedShopToken() {
  document.cookie = 'Admin-Token=; Path=/; Max-Age=0; SameSite=Lax'

  const hostname = window.location.hostname.toLowerCase()
  const sharedDomain = hostname === 'liebiankuai.com' || hostname.endsWith('.liebiankuai.com')
    ? '.liebiankuai.com'
    : hostname === 'kuailiebian.cn' || hostname.endsWith('.kuailiebian.cn')
      ? '.kuailiebian.cn'
      : ''
  if (sharedDomain)
    document.cookie = `Admin-Token=; Path=/; Domain=${sharedDomain}; Max-Age=0; SameSite=Lax`
}

export function redirectToAiHomeForExpiredToken() {
  clearSelectedShopToken()
  window.localStorage.removeItem('shop_id')
  window.sessionStorage.setItem(forcedLogoutStorageKey, '1')

  if (redirectingForExpiredToken)
    return

  redirectingForExpiredToken = true
  const homeUrl = new URL('/', window.location.origin)
  homeUrl.searchParams.set('login', '1')
  if (window.location.pathname === '/' && window.location.search === homeUrl.search)
    return
  window.location.replace(homeUrl.toString())
}

export function getMerchantLoginUrl() {
  const configured = String(import.meta.env.VITE_MERCHANT_LOGIN_URL || '').trim()
  if (configured)
    return configured

  const hostname = window.location.hostname
  return hostname === '127.0.0.1' || hostname === 'localhost'
    ? `${window.location.protocol}//${hostname}:5173/login`
    : ''
}

function withAccessToken(query: Query = {}) {
  if (query.access_token)
    return query

  const token = getSelectedShopToken()
  return token ? { ...query, access_token: token } : query
}

function buildUrl(path: string, query: Query = {}, includeAccessToken = true) {
  const url = new URL(path, `${apiBaseUrl}/`)
  const resolvedQuery = includeAccessToken ? withAccessToken(query) : query
  Object.entries(resolvedQuery).forEach(([key, value]) => {
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

async function request<T>(path: string, init: RequestInit = {}, query: Query = {}, includeAccessToken = true): Promise<T> {
  const response = await fetch(buildUrl(path, query, includeAccessToken), {
    ...init,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
      ...init.headers,
    },
  })
  const body = await response.json().catch(() => null)
  if (!response.ok) {
    if (response.status === 401 && includeAccessToken)
      redirectToAiHomeForExpiredToken()
    throw toApiError(response.status, body)
  }
  return body as T
}

function unsupported(module: string): never {
  throw new Error(`${module} 不属于独立 AI 服务范围`)
}

function normalizeActivityStage(params: Query) {
  const { stage, ...rest } = params
  // The API returns the full component set when stage is absent.
  return stage === 'ai_activity_theme' ? rest : params
}

const api = {
  auth: {
    loginByPassword: (data: { phone: string; password: string }) => request<AuthResult>('/merchant/v1/merchants/login', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
    getShops: (accessToken: string) => request<ShopListResult>('/merchant/v1/shops', {}, {
      access_token: accessToken,
      order: 'desc',
    }),
    selectShop: (shopId: number, accessToken: string) => request<AuthResult>(`/merchant/v1/patch/shops/${shopId}/current`, {
      method: 'POST',
      body: JSON.stringify({}),
    }, { access_token: accessToken }),
    syncMerchantAdminSession: () => {
      const accessToken = getSelectedShopToken()
      if (!accessToken)
        return Promise.reject(new Error('请先登录'))

      return request<{ message?: string }>('/merchant/v1/sso/admin/session', {
        method: 'POST',
        body: JSON.stringify({}),
        headers: { Authorization: `Bearer ${accessToken}` },
      }, {}, false)
    },
  },
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
