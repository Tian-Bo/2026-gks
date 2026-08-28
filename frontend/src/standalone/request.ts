type Query = Record<string, string | number | boolean | null | undefined>

const apiBaseUrl = String(import.meta.env.VITE_AI_API_BASE_URL || 'https://apis.liebiankuai.com').replace(/\/+$/, '')

function accessToken() {
  const cookie = document.cookie.match(/(?:^|;\s*)Admin-Token=([^;]*)/)
  return cookie ? decodeURIComponent(cookie[1]) : String(import.meta.env.VITE_AI_ACCESS_TOKEN || '').trim()
}

function buildUrl(path: string, query: Query = {}) {
  const url = new URL(path, `${apiBaseUrl}/`)
  const token = accessToken()
  if (token)
    url.searchParams.set('access_token', token)
  Object.entries(query).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '')
      url.searchParams.set(key, String(value))
  })
  return url.toString()
}

async function request<T = unknown>(method: string, path: string, data?: unknown, query?: Query): Promise<T> {
  const isFormData = data instanceof FormData
  const response = await fetch(buildUrl(path, query), {
    method,
    credentials: 'include',
    headers: isFormData || data === undefined ? { Accept: 'application/json' } : {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: data === undefined ? undefined : isFormData ? data : JSON.stringify(data),
  })
  const body = await response.json().catch(() => null)
  if (!response.ok) {
    const message = body && typeof body === 'object' && 'message' in body ? String(body.message || '') : ''
    throw new Error(message || `请求失败（${response.status}）`)
  }
  return body as T
}

export default {
  get: <R = unknown>(url: string, query?: Query) => request<R>('GET', url, undefined, query),
  post: <T = unknown, R = unknown>(url: string, data?: T) => request<R>('POST', url, data),
  put: <T = unknown, R = unknown>(url: string, data?: T) => request<R>('PUT', url, data),
  delete: <R = unknown>(url: string) => request<R>('DELETE', url),
}
