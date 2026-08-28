import { createServer } from 'node:http'
import { URL } from 'node:url'
import { inspirations, pageConfig, promptTips } from './catalog.mjs'
import { completeAssistantMessage, createTurn, listConversations, listMessages, stopAssistantMessage } from './ai-service.mjs'
import { changeStore, readStore } from './store.mjs'
import { mysqlHealthcheck, mysqlInspirations, mysqlPoints, usingMysql } from './mysql-repository.mjs'

const port = Number(process.env.PORT || 4311)
const host = process.env.HOST || '0.0.0.0'

function cors(response) {
  response.setHeader('Access-Control-Allow-Origin', process.env.CORS_ORIGIN || '*')
  response.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
  response.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
}

function json(response, status, payload) {
  cors(response)
  response.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8' })
  response.end(JSON.stringify(payload))
}

async function body(request) {
  const chunks = []
  for await (const chunk of request) chunks.push(chunk)
  if (!chunks.length) return {}
  try { return JSON.parse(Buffer.concat(chunks).toString('utf8')) } catch { return null }
}

function pageParams(searchParams) {
  return { page: Math.max(1, Number(searchParams.get('page')) || 1), perPage: Math.min(100, Math.max(1, Number(searchParams.get('per_page')) || 20)) }
}

function sse(response, event, payload) {
  response.write(`event: ${event}\n`)
  response.write(`data: ${JSON.stringify(payload)}\n\n`)
}

async function handleRequest(request, response) {
  const url = new URL(request.url || '/', `http://${request.headers.host || 'localhost'}`)
  const path = url.pathname.replace(/\/$/, '') || '/'
  if (request.method === 'OPTIONS') { cors(response); response.writeHead(204); response.end(); return }
  if (request.method === 'GET' && path === '/health') {
    const database = await usingMysql() ? 'mysql' : 'file'
    if (database === 'mysql') await mysqlHealthcheck()
    json(response, 200, { status: 'ok', service: 'kl-ai-service', database })
    return
  }

  if (request.method === 'GET' && path === '/merchant/v1/shop/ai/config') { json(response, 200, pageConfig); return }
  if (request.method === 'GET' && path === '/merchant/v1/shop/ai/prompt-tips') {
    const type = url.searchParams.get('type') || ''
    if (type && !promptTips[type]) { json(response, 422, { message: 'type 仅支持 activity 或 poster' }); return }
    json(response, 200, { ...(type ? { type } : {}), items: type ? promptTips[type] : Object.values(promptTips).flat() }); return
  }
  if (request.method === 'GET' && path === '/merchant/v1/shop/ai/inspirations') {
    const type = url.searchParams.get('type') || 'all'
    const items = await usingMysql()
      ? await mysqlInspirations(type)
      : type === 'all' ? inspirations : inspirations.filter(item => item.type === type)
    json(response, 200, { items, quick_prompts: items.slice(0, 2).map(item => ({ id: item.id, type: item.type, content: item.quick_prompt, prompt: item.prompt })), total: items.length, ...pageParams(url.searchParams) }); return
  }
  const inspirationMatch = path.match(/^\/merchant\/v1\/shop\/ai\/inspirations\/(\d+)$/)
  if (request.method === 'GET' && inspirationMatch) { json(response, 200, inspirations.find(item => item.id === Number(inspirationMatch[1])) || { message: '灵感不存在' }); return }

  if (request.method === 'GET' && path === '/merchant/v1/shop/ai/points') {
    const points = await usingMysql() ? await mysqlPoints(url.searchParams.get('shop_id')) : (await readStore()).points
    json(response, 200, points)
    return
  }
  if (request.method === 'GET' && path === '/merchant/v1/shop/ai/conversations') {
    const result = await listConversations({ shopId: url.searchParams.get('shop_id'), ...pageParams(url.searchParams) })
    json(response, 200, { ...result, current_page: pageParams(url.searchParams).page, per_page: pageParams(url.searchParams).perPage }); return
  }
  const messagesMatch = path.match(/^\/merchant\/v1\/shop\/ai\/conversations\/([^/]+)\/messages$/)
  if (request.method === 'GET' && messagesMatch) {
    const result = await listMessages(messagesMatch[1], pageParams(url.searchParams))
    if (!result) { json(response, 404, { message: '会话不存在' }); return }
    json(response, 200, { ...result, current_page: pageParams(url.searchParams).page, per_page: pageParams(url.searchParams).perPage }); return
  }
  if (request.method === 'POST' && path === '/merchant/v1/shop/ai/messages') {
    const payload = await body(request)
    if (!payload || !String(payload.content || '').trim()) { json(response, 422, { message: 'content 不能为空' }); return }
    const result = await createTurn(payload)
    json(response, 200, { conversation: result.conversation, user_message: result.userMessage, assistant_message: result.assistantMessage, stream_url: `/merchant/v1/shop/ai/messages/${result.assistantMessage.message_id}/stream`, assistant_status: 'pending' }); return
  }
  const streamMatch = path.match(/^\/merchant\/v1\/shop\/ai\/messages\/([^/]+)\/stream$/)
  if (request.method === 'GET' && streamMatch) {
    cors(response)
    response.writeHead(200, { 'Content-Type': 'text/event-stream; charset=utf-8', 'Cache-Control': 'no-cache, no-transform', Connection: 'keep-alive', 'X-Accel-Buffering': 'no' })
    await completeAssistantMessage(streamMatch[1], (event, payload) => sse(response, event, payload))
    response.end()
    return
  }
  const stopMatch = path.match(/^\/merchant\/v1\/shop\/ai\/messages\/([^/]+)\/stop$/)
  if (request.method === 'POST' && stopMatch) {
    const message = await stopAssistantMessage(stopMatch[1])
    if (!message) { json(response, 404, { message: '消息不存在' }); return }
    json(response, 200, { message }); return
  }
  if (request.method === 'POST' && path === '/merchant/v1/content/reactions/toggle') {
    const payload = await body(request)
    const key = `${payload?.target_type || 'ai_inspiration'}:${payload?.target_id || ''}`
    const reaction = await changeStore(store => { const active = !store.reactions[key]; store.reactions[key] = active; return active })
    json(response, 200, { target_type: payload?.target_type, target_id: Number(payload?.target_id || 0), reaction_type: payload?.reaction_type || 'like', is_active: reaction ? 1 : 0, count: reaction ? 1 : 0 }); return
  }
  json(response, 404, { message: 'AI 接口不存在', path })
}

const server = createServer((request, response) => {
  void handleRequest(request, response).catch((error) => {
    console.error('AI service request failed:', error.message)
    if (!response.headersSent) json(response, 503, { message: 'AI 数据库不可用，请检查数据库连接配置' })
    else response.end()
  })
})

server.listen(port, host, () => console.log(`KL AI service listening on http://${host}:${port}`))
