import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { dirname, join } from 'node:path'
import { randomUUID } from 'node:crypto'
import { fileURLToPath } from 'node:url'
import { sampleImages } from './catalog.mjs'

const root = dirname(dirname(fileURLToPath(import.meta.url)))
const storePath = join(root, 'data', 'ai-store.json')

function now() {
  return new Date().toISOString().replace('T', ' ').slice(0, 19)
}

function initialStore() {
  const createdAt = now()
  const conversationId = 'demo-activity-001'
  return {
    points: { balance: 1280, monthly_grant_remaining: 720 },
    reactions: {},
    conversations: [{
      conversation_id: conversationId,
      merchant_id: 1,
      shop_id: 1,
      scene: 'merchant_assistant',
      title: '夏日新客爆款体验活动',
      status: 'active',
      preview_image: sampleImages.activityImage,
      meta: { mode: 'activity', current_selection: { style: 'general', activity_model: 'auto', image_model: 'kl-image', thinking_mode: 'deep' } },
      latest_message_at: createdAt,
      created_at: createdAt,
      updated_at: createdAt,
    }],
    messages: [{
      message_id: 'demo-message-001',
      conversation_id: conversationId,
      merchant_id: 1,
      shop_id: 1,
      role: 'assistant',
      status: 'completed',
      content: '欢迎使用快灵。告诉我本次活动的目标、商品和时间，我会为你组织活动方案。',
      attachments: [],
      component_result: null,
      meta: { components: [] },
      created_at: createdAt,
      updated_at: createdAt,
    }],
  }
}

async function ensureStore() {
  try {
    return JSON.parse(await readFile(storePath, 'utf8'))
  } catch {
    const value = initialStore()
    await mkdir(dirname(storePath), { recursive: true })
    await writeFile(storePath, JSON.stringify(value, null, 2))
    return value
  }
}

async function saveStore(store) {
  await mkdir(dirname(storePath), { recursive: true })
  await writeFile(storePath, JSON.stringify(store, null, 2))
}

export async function readStore() {
  return ensureStore()
}

export async function changeStore(mutator) {
  const store = await ensureStore()
  const result = await mutator(store)
  await saveStore(store)
  return result
}

export function createId(prefix) {
  return `${prefix}_${randomUUID().replaceAll('-', '').slice(0, 20)}`
}

export { now }
