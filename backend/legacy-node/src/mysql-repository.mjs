import mysql from 'mysql2/promise'
import { readFile } from 'node:fs/promises'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { createId, now } from './store.mjs'

const root = dirname(dirname(fileURLToPath(import.meta.url)))
let pool
let configPromise

function parseEnv(content) {
  return Object.fromEntries(content.split(/\r?\n/).flatMap((line) => {
    const text = line.trim()
    if (!text || text.startsWith('#')) return []
    const index = text.indexOf('=')
    if (index < 1) return []
    const key = text.slice(0, index).trim()
    const value = text.slice(index + 1).trim().replace(/^(['"])(.*)\1$/, '$2')
    return [[key, value]]
  }))
}

async function readEnvFile(path) {
  try { return parseEnv(await readFile(path, 'utf8')) } catch { return {} }
}

async function databaseConfig() {
  if (configPromise) return configPromise
  configPromise = (async () => {
    const sourcePath = process.env.AI_DB_ENV_FILE || join(root, '..', '..', 'apis', '.env')
    const sourceEnv = await readEnvFile(sourcePath)
    const localEnv = await readEnvFile(join(root, '.env'))
    const env = { ...sourceEnv, ...localEnv, ...process.env }
    const host = env.AI_DB_HOST || env.DB_HOST
    const database = env.AI_DB_DATABASE || env.DB_DATABASE
    const driver = (env.AI_STORAGE_DRIVER || (host && database ? 'mysql' : 'file')).toLowerCase()
    return {
      driver,
      host,
      port: Number(env.AI_DB_PORT || env.DB_PORT || 3306),
      user: env.AI_DB_USERNAME || env.DB_USERNAME,
      password: env.AI_DB_PASSWORD || env.DB_PASSWORD,
      database,
      merchantId: Number(env.AI_MERCHANT_ID || 1),
    }
  })()
  return configPromise
}

export async function usingMysql() {
  const config = await databaseConfig()
  return config.driver === 'mysql' && Boolean(config.host && config.database && config.user)
}

async function db() {
  if (!(await usingMysql())) throw new Error('未配置 MySQL AI 数据库连接')
  if (!pool) {
    const config = await databaseConfig()
    pool = mysql.createPool({ host: config.host, port: config.port, user: config.user, password: config.password, database: config.database, waitForConnections: true, connectionLimit: 5, dateStrings: true, charset: 'utf8mb4' })
  }
  return pool
}

function asJson(value, fallback = {}) {
  if (!value) return fallback
  if (typeof value === 'object') return value
  try { return JSON.parse(value) } catch { return fallback }
}

function previewImage(meta) {
  return meta?.poster?.url || meta?.poster?.image_url || meta?.activity?.cover_img || meta?.activity?.image_url || null
}

function conversation(row) {
  const meta = asJson(row.meta)
  const preview = previewImage(meta)
  return { ...row, merchant_id: Number(row.merchant_id), shop_id: row.shop_id == null ? null : Number(row.shop_id), meta, preview_image: preview, preview_image_url: preview, current_selection: meta.current_selection || null }
}

function message(row) {
  const meta = asJson(row.meta)
  return { ...row, merchant_id: Number(row.merchant_id), shop_id: row.shop_id == null ? null : Number(row.shop_id), attachments: asJson(row.attachments, []), component_result: row.component_result ? asJson(row.component_result, null) : null, meta, components: meta.components || [], activity: meta.activity || null, poster: meta.poster || null, error_code: row.error_code || null, error_message: row.error_message || null }
}

export async function mysqlListConversations({ shopId, page, perPage }) {
  const connection = await db()
  const filters = ['merchant_id = ?']
  const values = [(await databaseConfig()).merchantId]
  if (shopId) { filters.push('shop_id = ?'); values.push(Number(shopId)) }
  const where = filters.join(' AND ')
  const [[count]] = await connection.query(`SELECT COUNT(*) AS total FROM shop_ai_conversations WHERE ${where}`, values)
  const [rows] = await connection.query(`SELECT * FROM shop_ai_conversations WHERE ${where} ORDER BY latest_message_at DESC, id DESC LIMIT ? OFFSET ?`, [...values, perPage, (page - 1) * perPage])
  return { items: rows.map(conversation), total: Number(count.total) }
}

export async function mysqlListMessages(conversationId, { page, perPage }) {
  const connection = await db()
  const config = await databaseConfig()
  const [conversations] = await connection.query('SELECT * FROM shop_ai_conversations WHERE conversation_id = ? AND merchant_id = ? LIMIT 1', [conversationId, config.merchantId])
  if (!conversations.length) return null
  const [[count]] = await connection.query('SELECT COUNT(*) AS total FROM shop_ai_messages WHERE conversation_id = ?', [conversationId])
  const [rows] = await connection.query('SELECT * FROM shop_ai_messages WHERE conversation_id = ? ORDER BY id ASC LIMIT ? OFFSET ?', [conversationId, perPage, (page - 1) * perPage])
  return { conversation: conversation(conversations[0]), items: rows.map(message), total: Number(count.total) }
}

export async function mysqlCreateTurn(payload) {
  const connection = await (await db()).getConnection()
  const config = await databaseConfig()
  const createdAt = now()
  const shopId = Number(payload.shop_id || 1)
  const isPoster = payload.scene === 'merchant_poster' || payload.component_result?.mode === 'poster'
  const selection = { style: payload.options?.style || null, aspect_ratio: payload.options?.aspect_ratio || null, activity_model: payload.options?.activity_model || null, image_model: payload.options?.image_model || null, thinking_mode: payload.options?.thinking_mode || payload.component_result?.think_mode || null }
  await connection.beginTransaction()
  try {
    let record
    if (payload.conversation_id) {
      const [rows] = await connection.query('SELECT * FROM shop_ai_conversations WHERE conversation_id = ? AND merchant_id = ? FOR UPDATE', [payload.conversation_id, config.merchantId])
      record = rows[0]
    }
    if (!record) {
      const conversationId = createId('conv')
      const meta = JSON.stringify({ mode: isPoster ? 'poster' : 'activity', current_selection: selection })
      const [insert] = await connection.query('INSERT INTO shop_ai_conversations (conversation_id, merchant_id, shop_id, scene, title, status, meta, latest_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [conversationId, config.merchantId, shopId, isPoster ? 'merchant_poster' : 'merchant_assistant', String(payload.content).slice(0, 120), 'active', meta, createdAt, createdAt, createdAt])
      const [rows] = await connection.query('SELECT * FROM shop_ai_conversations WHERE id = ?', [insert.insertId])
      record = rows[0]
    } else {
      const meta = { ...asJson(record.meta), current_selection: selection }
      await connection.query('UPDATE shop_ai_conversations SET meta = ?, latest_message_at = ?, updated_at = ? WHERE id = ?', [JSON.stringify(meta), createdAt, createdAt, record.id])
      record = { ...record, meta: JSON.stringify(meta), latest_message_at: createdAt, updated_at: createdAt }
    }
    const userId = payload.user_message_id || createId('user')
    const assistantId = createId('assistant')
    await connection.query('INSERT INTO shop_ai_messages (conversation_record_id, message_id, merchant_id, shop_id, conversation_id, role, status, content, attachments, component_result, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [record.id, userId, config.merchantId, record.shop_id, record.conversation_id, 'user', 'success', String(payload.content), JSON.stringify(payload.attachments || []), payload.component_result ? JSON.stringify(payload.component_result) : null, JSON.stringify({ options: payload.options || {} }), createdAt, createdAt])
    await connection.query('INSERT INTO shop_ai_messages (conversation_record_id, message_id, merchant_id, shop_id, conversation_id, role, status, content, attachments, component_result, meta, started_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [record.id, assistantId, config.merchantId, record.shop_id, record.conversation_id, 'assistant', 'pending', '', '[]', null, JSON.stringify({ mode: isPoster ? 'poster' : 'activity', components: [] }), createdAt, createdAt, createdAt])
    const [userRows] = await connection.query('SELECT * FROM shop_ai_messages WHERE message_id = ?', [userId])
    const [assistantRows] = await connection.query('SELECT * FROM shop_ai_messages WHERE message_id = ?', [assistantId])
    await connection.commit()
    return { conversation: conversation(record), userMessage: message(userRows[0]), assistantMessage: message(assistantRows[0]) }
  } catch (error) {
    await connection.rollback()
    throw error
  } finally {
    connection.release()
  }
}

export async function mysqlAssistantContext(messageId) {
  const connection = await db()
  const [messages] = await connection.query('SELECT * FROM shop_ai_messages WHERE message_id = ? LIMIT 1', [messageId])
  if (!messages.length) return null
  const assistant = message(messages[0])
  const [conversations] = await connection.query('SELECT * FROM shop_ai_conversations WHERE id = ? LIMIT 1', [messages[0].conversation_record_id])
  const [users] = await connection.query("SELECT * FROM shop_ai_messages WHERE conversation_id = ? AND role = 'user' ORDER BY id DESC LIMIT 1", [assistant.conversation_id])
  return { assistant, conversation: conversation(conversations[0]), userMessage: users.length ? message(users[0]) : null }
}

export async function mysqlAssistantStopped(messageId) {
  const connection = await db()
  const [rows] = await connection.query('SELECT status FROM shop_ai_messages WHERE message_id = ? LIMIT 1', [messageId])
  return rows[0]?.status === 'stopped'
}

export async function mysqlCompleteAssistant(messageId, { content, components, poster }) {
  const connection = await db()
  const completedAt = now()
  const [rows] = await connection.query('SELECT * FROM shop_ai_messages WHERE message_id = ? LIMIT 1', [messageId])
  if (!rows.length) return null
  if (rows[0].status === 'stopped') return { stopped: true, assistant: message(rows[0]) }
  const meta = { ...asJson(rows[0].meta), components, ...(poster ? { poster } : {}) }
  await connection.query('UPDATE shop_ai_messages SET status = ?, content = ?, meta = ?, completed_at = ?, updated_at = ? WHERE id = ?', ['completed', content, JSON.stringify(meta), completedAt, completedAt, rows[0].id])
  await connection.query('UPDATE shop_ai_conversations SET latest_message_at = ?, updated_at = ? WHERE id = ?', [completedAt, completedAt, rows[0].conversation_record_id])
  return { stopped: false, assistant: message({ ...rows[0], status: 'completed', content, meta: JSON.stringify(meta), completed_at: completedAt, updated_at: completedAt }) }
}

export async function mysqlStopAssistant(messageId) {
  const connection = await db()
  const stoppedAt = now()
  await connection.query("UPDATE shop_ai_messages SET status = 'stopped', stopped_at = ?, updated_at = ? WHERE message_id = ? AND status IN ('pending', 'streaming')", [stoppedAt, stoppedAt, messageId])
  const [rows] = await connection.query('SELECT * FROM shop_ai_messages WHERE message_id = ? LIMIT 1', [messageId])
  return rows.length ? message(rows[0]) : null
}

export async function mysqlPoints(shopId) {
  const connection = await db()
  const [rows] = await connection.query('SELECT balance, monthly_grant_remaining, trial_activity_remaining, trial_poster_remaining FROM shop_ai_point_accounts WHERE shop_id = ? LIMIT 1', [Number(shopId || 1)])
  const row = rows[0]
  return { balance: Number(row?.balance || 0), monthly_grant_remaining: Number(row?.monthly_grant_remaining || 0), trial: { activity_create_remaining: Number(row?.trial_activity_remaining || 0), poster_generate_remaining: Number(row?.trial_poster_remaining || 0) } }
}

export async function mysqlInspirations(type) {
  const connection = await db()
  const values = []
  let where = 'WHERE is_online = 1 AND deleted_at IS NULL'
  if (type && type !== 'all') { where += ' AND type = ?'; values.push(type) }
  const [rows] = await connection.query(`SELECT id, type, title, image_url, prompt, quick_prompt, activity_id, created_at FROM ai_inspirations ${where} ORDER BY sort ASC, id DESC LIMIT 50`, values)
  return rows.map(row => ({ ...row, image_url: row.image_url || null, cover_img: row.image_url || null, preview_image: row.image_url || null, like_count: 0, used_count: 0 }))
}

export async function mysqlHealthcheck() {
  const connection = await db()
  await connection.query('SELECT 1')
}
