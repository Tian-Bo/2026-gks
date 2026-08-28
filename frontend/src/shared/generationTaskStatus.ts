export type AiGenerationTask = {
  conversationId: string
  assistantMessageId?: string
  mode?: 'activity' | 'poster' | string
  title?: string
  updatedAt: number
}

const AI_GENERATION_TASK_STORAGE_KEY = 'KLB_AI_GENERATION_TASKS'
const AI_GENERATION_TASK_EVENT = 'kl-ai-generation-tasks-change'
const AI_GENERATION_TASK_MAX_AGE = 30 * 60 * 1000

function canUseWindow() {
  return typeof window !== 'undefined'
}

function normalizeTask(raw: any): AiGenerationTask | null {
  const conversationId = String(raw?.conversationId || '').trim()
  if (!conversationId)
    return null

  return {
    conversationId,
    assistantMessageId: String(raw?.assistantMessageId || '').trim() || undefined,
    mode: String(raw?.mode || '').trim() || undefined,
    title: String(raw?.title || '').trim() || undefined,
    updatedAt: Number(raw?.updatedAt || 0) || Date.now(),
  }
}

function readRawTasks() {
  if (!canUseWindow())
    return []

  try {
    const raw = window.localStorage.getItem(AI_GENERATION_TASK_STORAGE_KEY)
    const value = raw ? JSON.parse(raw) : []
    return Array.isArray(value) ? value : []
  }
  catch {
    return []
  }
}

function writeTasks(tasks: AiGenerationTask[]) {
  if (!canUseWindow())
    return

  if (tasks.length)
    window.localStorage.setItem(AI_GENERATION_TASK_STORAGE_KEY, JSON.stringify(tasks))
  else
    window.localStorage.removeItem(AI_GENERATION_TASK_STORAGE_KEY)

  window.dispatchEvent(new CustomEvent(AI_GENERATION_TASK_EVENT))
}

export function getAiGenerationTasks() {
  const now = Date.now()
  const rawTasks = readRawTasks()
  const tasks = rawTasks
    .map(normalizeTask)
    .filter((task): task is AiGenerationTask => !!task && now - task.updatedAt < AI_GENERATION_TASK_MAX_AGE)

  if (tasks.length !== rawTasks.length)
    writeTasks(tasks)

  return tasks
}

export function getAiGenerationTaskCount() {
  return getAiGenerationTasks().length
}

export function upsertAiGenerationTask(task: Omit<AiGenerationTask, 'updatedAt'> & { updatedAt?: number }) {
  const normalized = normalizeTask({
    ...task,
    updatedAt: task.updatedAt || Date.now(),
  })

  if (!normalized)
    return

  const tasks = getAiGenerationTasks().filter((item) => {
    if (normalized.assistantMessageId && item.assistantMessageId)
      return item.assistantMessageId !== normalized.assistantMessageId
    return item.conversationId !== normalized.conversationId
  })

  writeTasks([normalized, ...tasks])
}

export function removeAiGenerationTask(match: { conversationId?: string, assistantMessageId?: string }) {
  const conversationId = String(match.conversationId || '').trim()
  const assistantMessageId = String(match.assistantMessageId || '').trim()

  if (!conversationId && !assistantMessageId)
    return

  const tasks = getAiGenerationTasks().filter((task) => {
    if (assistantMessageId && task.assistantMessageId === assistantMessageId)
      return false
    if (conversationId && task.conversationId === conversationId)
      return false
    return true
  })

  writeTasks(tasks)
}

export function replaceAiGenerationTasks(tasks: Array<Omit<AiGenerationTask, 'updatedAt'> & { updatedAt?: number }>) {
  const normalizedTasks = tasks
    .map(task => normalizeTask({ ...task, updatedAt: task.updatedAt || Date.now() }))
    .filter((task): task is AiGenerationTask => !!task)

  writeTasks(normalizedTasks)
}

export function subscribeAiGenerationTasks(callback: () => void) {
  if (!canUseWindow())
    return () => {}

  const handleStorage = (event: StorageEvent) => {
    if (event.key === AI_GENERATION_TASK_STORAGE_KEY)
      callback()
  }

  window.addEventListener(AI_GENERATION_TASK_EVENT, callback)
  window.addEventListener('storage', handleStorage)

  return () => {
    window.removeEventListener(AI_GENERATION_TASK_EVENT, callback)
    window.removeEventListener('storage', handleStorage)
  }
}
