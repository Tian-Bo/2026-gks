const apiBaseUrl = String(import.meta.env.VITE_AI_API_BASE_URL || 'https://apis.liebiankuai.com').replace(/\/+$/, '')
const previewQueryKeys = [
  'activity_preview_url',
  'activityPreviewUrl',
  'activity_url',
  'activityUrl',
  'preview_activity_url',
  'previewActivityUrl',
  'preview_url',
  'previewUrl',
  'mock_url',
  'mockUrl',
  'local_url',
  'localUrl',
  'url',
]

let previewOrigin = ''
let previewOriginRequest: Promise<string> | null = null

function normalizePreviewOrigin(value: unknown) {
  const raw = String(value || '').trim()
  if (!raw)
    return ''

  const text = /^[a-z][a-z\d+.-]*:\/\//i.test(raw) ? raw : `https://${raw}`
  try {
    return new URL(text).origin
  }
  catch {
    return raw.replace(/\/+$/, '')
  }
}

function getMockPreviewOrigin() {
  const params = new URLSearchParams(window.location.search)
  for (const key of previewQueryKeys) {
    const value = params.get(key)
    if (value?.trim())
      return normalizePreviewOrigin(value)
  }
  return ''
}

function createPreviewUrl(origin: string, activityId?: number, activityModelId?: number) {
  if (!origin || !activityId)
    return ''

  const params = new URLSearchParams({
    aid: String(activityId),
    type: 'preview',
    preview_type: 'activity',
    _t: String(Date.now()),
  })
  if (activityModelId)
    params.set('mid', String(activityModelId))
  return `${origin}?${params.toString()}`
}

async function resolvePreviewOrigin() {
  const mockOrigin = new URLSearchParams(window.location.search).get('mock') === '1'
    ? getMockPreviewOrigin()
    : ''
  if (mockOrigin)
    return mockOrigin
  if (previewOrigin)
    return previewOrigin
  if (previewOriginRequest)
    return previewOriginRequest

  previewOriginRequest = fetch(new URL('/common/v1/domain', `${apiBaseUrl}/`), {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
    .then(async (response) => response.ok ? response.json() : null)
    .then((payload) => {
      const domains = payload?.domains || payload?.data || payload || {}
      const preview = Array.isArray(domains)
        ? domains.find((item: Record<string, unknown>) => item?.key === 'preview')
        : domains.preview
      previewOrigin = normalizePreviewOrigin(preview?.url || preview)
      return previewOrigin
    })
    .catch(() => '')
    .finally(() => {
      previewOriginRequest = null
    })
  return previewOriginRequest
}

export function buildActivityPreviewUrlSync(activityId?: number, activityModelId?: number) {
  const mockOrigin = new URLSearchParams(window.location.search).get('mock') === '1'
    ? getMockPreviewOrigin()
    : ''
  return createPreviewUrl(mockOrigin || previewOrigin, activityId, activityModelId)
}

export async function buildActivityPreviewUrl(activityId?: number, activityModelId?: number) {
  return createPreviewUrl(await resolvePreviewOrigin(), activityId, activityModelId)
}
