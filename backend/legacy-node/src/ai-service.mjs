import { createId, changeStore, now, readStore } from './store.mjs'
import { posterScene, sampleImages } from './catalog.mjs'
import {
  mysqlAssistantContext,
  mysqlAssistantStopped,
  mysqlCompleteAssistant,
  mysqlCreateTurn,
  mysqlListConversations,
  mysqlListMessages,
  mysqlStopAssistant,
  usingMysql,
} from './mysql-repository.mjs'

export function conversationResource(conversation) {
  return {
    ...conversation,
    preview_image_url: conversation.preview_image || null,
    current_selection: conversation.meta?.current_selection || null,
  }
}

export function messageResource(message) {
  return {
    ...message,
    attachments: message.attachments || [],
    components: message.meta?.components || [],
    activity: message.meta?.activity || null,
    poster: message.meta?.poster || null,
    error_code: message.error_code || null,
    error_message: message.error_message || null,
    started_at: message.started_at || null,
    completed_at: message.completed_at || null,
    stopped_at: message.stopped_at || null,
  }
}

export async function listConversations({ shopId, page, perPage }) {
  if (await usingMysql()) return mysqlListConversations({ shopId, page, perPage })
  const store = await readStore()
  const filtered = store.conversations
    .filter(item => !shopId || Number(item.shop_id) === Number(shopId))
    .sort((a, b) => String(b.updated_at).localeCompare(String(a.updated_at)))
  const start = (page - 1) * perPage
  return { items: filtered.slice(start, start + perPage).map(conversationResource), total: filtered.length }
}

export async function listMessages(conversationId, { page, perPage }) {
  if (await usingMysql()) return mysqlListMessages(conversationId, { page, perPage })
  const store = await readStore()
  const conversation = store.conversations.find(item => item.conversation_id === conversationId)
  if (!conversation) return null
  const messages = store.messages.filter(item => item.conversation_id === conversationId)
  const start = (page - 1) * perPage
  return { conversation: conversationResource(conversation), items: messages.slice(start, start + perPage).map(messageResource), total: messages.length }
}

export async function createTurn(payload) {
  if (await usingMysql()) return mysqlCreateTurn(payload)
  return changeStore(store => {
    const time = now()
    const isPoster = payload.scene === posterScene || payload.component_result?.mode === 'poster'
    const selection = {
      style: payload.options?.style || null,
      aspect_ratio: payload.options?.aspect_ratio || null,
      activity_model: payload.options?.activity_model || null,
      image_model: payload.options?.image_model || null,
      thinking_mode: payload.options?.thinking_mode || payload.component_result?.think_mode || null,
    }
    let conversation = payload.conversation_id && store.conversations.find(item => item.conversation_id === payload.conversation_id)
    if (!conversation) {
      conversation = {
        conversation_id: createId('conv'), merchant_id: 1, shop_id: Number(payload.shop_id || 1),
        scene: isPoster ? posterScene : 'merchant_assistant', title: String(payload.content).slice(0, 24), status: 'active',
        preview_image: isPoster ? sampleImages.posterImage : sampleImages.activityImage,
        meta: { mode: isPoster ? 'poster' : 'activity', current_selection: selection },
        latest_message_at: time, created_at: time, updated_at: time,
      }
      store.conversations.push(conversation)
    } else {
      conversation.meta = { ...(conversation.meta || {}), current_selection: selection }
      conversation.latest_message_at = time
      conversation.updated_at = time
    }
    const userMessage = {
      message_id: payload.user_message_id || createId('user'), conversation_id: conversation.conversation_id, merchant_id: 1, shop_id: conversation.shop_id,
      role: 'user', status: 'success', content: String(payload.content), attachments: payload.attachments || [], component_result: payload.component_result || null,
      meta: { options: payload.options || {} }, created_at: time, updated_at: time,
    }
    const assistantMessage = {
      message_id: createId('assistant'), conversation_id: conversation.conversation_id, merchant_id: 1, shop_id: conversation.shop_id,
      role: 'assistant', status: 'pending', content: '', attachments: [], component_result: null,
      meta: { mode: isPoster ? 'poster' : 'activity', components: [] }, started_at: time, created_at: time, updated_at: time,
    }
    store.messages.push(userMessage, assistantMessage)
    return { conversation: conversationResource(conversation), userMessage: messageResource(userMessage), assistantMessage: messageResource(assistantMessage) }
  })
}

function buildReply(conversation, userMessage) {
  const isPoster = conversation.scene === posterScene || conversation.meta?.mode === 'poster'
  const text = isPoster
    ? '我已拆解海报主题、目标人群和画面风格，正在生成主视觉方案。'
    : '我已理解你的活动诉求，接下来先确认活动目标和时间，再为你生成活动方案。'
  const components = isPoster
    ? [{ card_id: createId('poster'), type: 'poster_image_preview', status: 'completed', title: 'AI 海报预览', image_url: sampleImages.posterImage, poster: { url: sampleImages.posterImage } }]
    : [{ card_id: createId('goal'), type: 'activity_goal_duration_selector', title: '先确认活动目标和时间', step_key: 'activity_goal_duration', sections: [{ section_key: 'goal', title: '本次活动的核心目标是什么？', options: [{ value: '拉新获客', label: '拉新获客' }, { value: '老客复购', label: '老客复购' }, { value: '会员储值', label: '会员储值' }] }, { section_key: 'duration', title: '活动计划的起止时间是？', options: [{ value: '最近10天', label: '最近 10 天' }, { value: 'custom_range', label: '自定义时间', action: 'open_date_picker' }] }] }]
  return { text, components, isPoster }
}

export async function completeAssistantMessage(messageId, emit) {
  if (await usingMysql()) return completeMysqlAssistantMessage(messageId, emit)
  const store = await readStore()
  const assistant = store.messages.find(item => item.message_id === messageId)
  if (!assistant) return { error: '消息不存在' }
  const conversation = store.conversations.find(item => item.conversation_id === assistant.conversation_id)
  const userMessages = store.messages.filter(item => item.conversation_id === assistant.conversation_id && item.role === 'user')
  const userMessage = userMessages.at(-1)
  if (assistant.status === 'stopped') return { stopped: true, assistant: messageResource(assistant), conversation }
  const { text, components, isPoster } = buildReply(conversation, userMessage)
  let seq = 1
  const base = () => ({ conversation_id: conversation.conversation_id, assistant_message_id: assistant.message_id, seq: seq++, created_at: now() })
  const isStopped = async () => {
    const latest = await readStore()
    return latest.messages.find(item => item.message_id === messageId)?.status === 'stopped'
  }
  emit('connected', base())
  emit('message_start', base())
  emit('thinking_delta', { ...base(), delta: isPoster ? '正在构思画面结构和视觉风格...' : '正在拆解目标、商品与活动周期...' })
  for (const delta of text.match(/.{1,16}/g) || []) {
    if (await isStopped()) {
      emit('done', { ...base(), finish_reason: 'stopped' })
      return { stopped: true, assistant, conversation }
    }
    emit('message_delta', { ...base(), delta })
    await new Promise(resolve => setTimeout(resolve, 80))
  }
  if (await isStopped()) {
    emit('done', { ...base(), finish_reason: 'stopped' })
    return { stopped: true, assistant, conversation }
  }
  components.forEach(card => emit('message_card', { ...base(), card }))
  const completed = await changeStore(nextStore => {
    const target = nextStore.messages.find(item => item.message_id === messageId)
    const targetConversation = nextStore.conversations.find(item => item.conversation_id === target.conversation_id)
    if (target.status === 'stopped') return { stopped: true, target, targetConversation }
    target.status = 'completed'
    target.content = text
    target.completed_at = now()
    target.updated_at = target.completed_at
    target.meta = { ...(target.meta || {}), components, ...(isPoster ? { poster: { url: sampleImages.posterImage } } : {}) }
    targetConversation.latest_message_at = target.completed_at
    targetConversation.updated_at = target.completed_at
    return { target, targetConversation }
  })
  if (completed.stopped) {
    emit('done', { ...base(), finish_reason: 'stopped' })
    return { stopped: true, assistant: messageResource(completed.target), conversation: completed.targetConversation }
  }
  emit('message_completed', { ...base(), content: completed.target.content, status: 'completed', components, poster: completed.target.meta.poster || null })
  emit('done', { ...base(), finish_reason: 'completed' })
  return { assistant: messageResource(completed.target), conversation: completed.targetConversation }
}

export async function stopAssistantMessage(messageId) {
  if (await usingMysql()) return mysqlStopAssistant(messageId)
  return changeStore(store => {
    const message = store.messages.find(item => item.message_id === messageId)
    if (!message) return null
    message.status = 'stopped'
    message.stopped_at = now()
    message.updated_at = message.stopped_at
    return messageResource(message)
  })
}

async function completeMysqlAssistantMessage(messageId, emit) {
  const context = await mysqlAssistantContext(messageId)
  if (!context) return { error: '消息不存在' }
  const { assistant, conversation, userMessage } = context
  if (assistant.status === 'stopped') return { stopped: true, assistant, conversation }
  const { text, components, isPoster } = buildReply(conversation, userMessage)
  let seq = 1
  const base = () => ({ conversation_id: conversation.conversation_id, assistant_message_id: assistant.message_id, seq: seq++, created_at: now() })
  emit('connected', base())
  emit('message_start', base())
  emit('thinking_delta', { ...base(), delta: isPoster ? '正在构思画面结构和视觉风格...' : '正在拆解目标、商品与活动周期...' })
  for (const delta of text.match(/.{1,16}/g) || []) {
    if (await mysqlAssistantStopped(messageId)) {
      emit('done', { ...base(), finish_reason: 'stopped' })
      return { stopped: true, assistant, conversation }
    }
    emit('message_delta', { ...base(), delta })
    await new Promise(resolve => setTimeout(resolve, 80))
  }
  if (await mysqlAssistantStopped(messageId)) {
    emit('done', { ...base(), finish_reason: 'stopped' })
    return { stopped: true, assistant, conversation }
  }
  components.forEach(card => emit('message_card', { ...base(), card }))
  const completed = await mysqlCompleteAssistant(messageId, { content: text, components, poster: isPoster ? { url: sampleImages.posterImage } : null })
  if (!completed || completed.stopped) {
    emit('done', { ...base(), finish_reason: 'stopped' })
    return { stopped: true, assistant, conversation }
  }
  emit('message_completed', { ...base(), content: text, status: 'completed', components, poster: completed.assistant.poster || null })
  emit('done', { ...base(), finish_reason: 'completed' })
  return { assistant: completed.assistant, conversation }
}
