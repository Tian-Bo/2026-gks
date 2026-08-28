<template>
  <div class="ai-history-page">
    <div class="ai-history-page__bg" />

    <main class="ai-history-page__main">
      <header class="ai-history-page__header">
        <button type="button" class="ai-history-page__back" @click="goBack">
          <i class="iconfont icon-youjiantou rotate-180" aria-hidden="true"></i>
          <span>返回</span>
        </button>
        <h1 class="ai-history-page__title">
          历史生成({{ historyTitleCount }})
        </h1>
      </header>

      <section class="ai-history-page__content">
        <div class="ai-history-toolbar">
          <nav class="ai-history-tabs" aria-label="历史生成类型">
            <button
              v-for="tab in historyTypeTabs"
              :key="tab.value"
              type="button"
              class="ai-history-tabs__item"
              :class="{ 'is-active': activeType === tab.value }"
              @click="activeType = tab.value"
            >
              {{ tab.label }}
            </button>
          </nav>

          <div class="ai-history-toolbar__actions">
            <KlSearchInput
              v-model="keyword"
              class="ai-history-search"
              variant="white"
              size="md"
              :width="240"
              placeholder="搜索历史"
              search-text="搜索"
            />

            <KlDropdown
              v-if="viewMode === 'list'"
              :menu="statusFilterMenu"
              placement="bottomRight"
              overlay-class-name="ai-history-filter-dropdown"
            >
              <button type="button" class="ai-history-filter" @click.stop>
                <span>{{ statusFilterLabel }}</span>
                <i class="iconfont icon-youjiantou ai-history-filter__arrow" aria-hidden="true"></i>
              </button>
            </KlDropdown>

            <KlViewToggle
              v-model="viewMode"
              :options="viewOptions"
              class="ai-history-view-toggle"
            />
          </div>
        </div>

        <template v-if="isHistoryLoading">
          <div v-if="viewMode === 'grid'" class="ai-history-grid ai-history-grid--skeleton" aria-hidden="true">
            <article
              v-for="index in pageSize"
              :key="`history-grid-skeleton-${index}`"
              class="history-card history-card--skeleton"
            >
              <div class="history-card__preview">
                <div class="history-skeleton history-skeleton--preview"></div>
              </div>
              <div class="history-card__body">
                <div class="history-skeleton history-skeleton--title"></div>
                <div class="history-skeleton history-skeleton--meta"></div>
              </div>
            </article>
          </div>

          <div v-else class="ai-history-list-panel ai-history-list-panel--skeleton" aria-hidden="true">
            <div class="ai-history-list">
              <div class="ai-history-list__head">
                <span>封面</span>
                <span>名称</span>
                <span>类型</span>
                <span>时间</span>
                <span></span>
              </div>

              <article
                v-for="index in pageSize"
                :key="`history-list-skeleton-${index}`"
                class="ai-history-list__row ai-history-list__row--skeleton"
              >
                <div class="ai-history-list__cover">
                  <div class="history-skeleton history-skeleton--list-cover"></div>
                </div>
                <div class="history-skeleton history-skeleton--list-title"></div>
                <div class="history-skeleton history-skeleton--list-type"></div>
                <div class="history-skeleton history-skeleton--list-time"></div>
                <div class="history-skeleton history-skeleton--list-action"></div>
              </article>
            </div>
          </div>
        </template>

        <template v-else-if="filteredHistoryList.length">
          <div v-if="viewMode === 'grid'" class="ai-history-grid">
            <article
              v-for="item in pagedHistoryList"
              :key="item.conversation_id"
              class="history-card"
              @click="openConversation(item)"
            >
              <div class="history-card__preview">
                <div class="history-card__badge">
                  {{ inferModeLabel(item) }}
                </div>

                <KlDropdown :menu="getHistoryActionMenu(item)" placement="bottomRight" overlay-class-name="ai-history-action-dropdown">
                  <button type="button" class="history-card__more" @click.stop aria-label="更多操作">
                    <i class="iconfont icon-gengduo" aria-hidden="true"></i>
                  </button>
                </KlDropdown>

                <div class="history-card__image-wrap">
                  <img
                    v-if="getHistoryPreviewImage(item)"
                    class="history-card__image"
                    :src="getHistoryPreviewImage(item)"
                    :alt="item.title || 'AI 生成封面'"
                  >
                  <div v-else class="history-card__placeholder" aria-hidden="true">
                    <i class="iconfont" :class="getHistoryPlaceholderIcon(item)"></i>
                  </div>
                </div>
              </div>

              <div class="history-card__body">
                <div class="history-card__title" :title="getHistoryTitle(item)">
                  {{ getHistoryTitle(item) }}
                </div>
                <div class="history-card__meta">
                  <HistoryGeneratingStatus v-if="isHistoryGenerating(item)" />
                  <span
                    v-if="isHistoryGenerating(item)"
                    class="history-card__meta-divider"
                    aria-hidden="true"
                  ></span>
                  <span class="history-card__time">
                    {{ formatUpdatedAt(getHistoryTime(item)) }}
                  </span>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="ai-history-list-panel">
            <div class="ai-history-list">
              <div class="ai-history-list__head">
                <span>封面</span>
                <span>名称</span>
                <span>类型</span>
                <span>时间</span>
                <span></span>
              </div>

              <article
                v-for="item in pagedHistoryList"
                :key="item.conversation_id"
                class="ai-history-list__row"
                @click="openConversation(item)"
              >
                <div class="ai-history-list__cover">
                  <img
                    v-if="getHistoryPreviewImage(item)"
                    :src="getHistoryPreviewImage(item)"
                    :alt="item.title || 'AI 生成封面'"
                  >
                  <div v-else class="ai-history-list__placeholder" aria-hidden="true">
                    <i class="iconfont" :class="getHistoryPlaceholderIcon(item)"></i>
                  </div>
                </div>

                <div class="ai-history-list__name" :title="getHistoryTitle(item)">
                  {{ getHistoryTitle(item) }}
                </div>
                <div class="ai-history-list__type">
                  {{ inferModeLabel(item) }}
                </div>
                <div class="ai-history-list__time" :class="{ 'is-generating': isHistoryGenerating(item) }">
                  <HistoryGeneratingStatus v-if="isHistoryGenerating(item)" />
                  <span>{{ formatUpdatedAt(getHistoryTime(item)) }}</span>
                </div>
                <div class="ai-history-list__actions">
                  <button type="button" class="ai-history-list__action" aria-label="删除历史生成" @click.stop="removeHistoryItem(item)">
                    <i class="iconfont icon-shanchu" aria-hidden="true"></i>
                  </button>
                  <button type="button" class="ai-history-list__action" aria-label="打开历史生成" @click.stop="openConversation(item)">
                    <i class="iconfont icon-bianji" aria-hidden="true"></i>
                  </button>
                </div>
              </article>
            </div>

            <footer class="ai-history-list-panel__footer">
              <span>共 {{ filteredHistoryList.length }} 条</span>
              <KlPagination
                v-model:current="page"
                :page-size="pageSize"
                :total="filteredHistoryList.length"
                :show-size-changer="false"
                class="ai-history-pagination"
              />
            </footer>
          </div>

          <footer v-if="viewMode === 'grid'" class="ai-history-grid-footer">
            <span>共 {{ filteredHistoryList.length }} 条</span>
            <KlPagination
              v-model:current="page"
              :page-size="pageSize"
              :total="filteredHistoryList.length"
              :show-size-changer="false"
              class="ai-history-pagination"
            />
          </footer>
        </template>

        <div v-else class="ai-history-empty">
          暂无历史生成
        </div>
      </section>
    </main>
    <KlLoginGuideModal v-model="loginGuideOpen" @authenticated="handleLoginAuthenticated" />
  </div>
</template>

<script setup lang="ts">
import type { AiConversation, AiMessage } from '../standalone/types'
import type { AiGenerationTask } from '../shared/generationTaskStatus'
import { computed, defineComponent, h, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api, { hasAiAccessToken } from '../standalone/api'
import KlDropdown from '../components/kl/KlDropdown.vue'
import KlLoginGuideModal from '../components/kl/KlLoginGuideModal.vue'
import KlPagination from '../components/kl/KlPagination.vue'
import KlSearchInput from '../components/kl/KlSearchInput.vue'
import KlViewToggle from '../components/kl/KlViewToggle.vue'
import { getStore } from '../standalone/storage'
import { klbMessage } from '../standalone/klbMessage'
import LottieStar from '../components/ai-chat/LottieStar.vue'
import {
  getAiGenerationTasks,
  subscribeAiGenerationTasks,
} from '../shared/generationTaskStatus'

type HistoryTypeFilter = 'all' | 'activity' | 'poster'
type HistoryStatusFilter = 'all' | 'generating' | 'completed'
type HistoryViewMode = 'grid' | 'list'

const HistoryGeneratingStatus = defineComponent({
  name: 'HistoryGeneratingStatus',
  setup() {
    return () => h('span', { class: 'history-generating-status' }, [
      h(LottieStar, {
        class: 'history-generating-status__star',
        size: 14,
        loop: true,
        autoplay: true,
      }),
      h('span', { class: 'history-generating-status__text' }, '正在生成中..'),
    ])
  },
})

const router = useRouter()
const historyList = ref<AiConversation[]>([])
const historyTotal = ref(0)
const generationTasks = ref<AiGenerationTask[]>([])
const remoteGeneratingIds = ref<Set<string>>(new Set())
const keyword = ref('')
const activeType = ref<HistoryTypeFilter>('all')
const statusFilter = ref<HistoryStatusFilter>('all')
const viewMode = ref<HistoryViewMode>('grid')
const page = ref(1)
const isHistoryLoading = ref(false)
const loginGuideOpen = ref(false)
let unsubscribeGenerationTasks: (() => void) | null = null

const viewOptions = [
  { value: 'list', icon: 'icon-liebiaoshitu1' },
  { value: 'grid', icon: 'icon-kapianshitu' },
]

const statusOptions: Array<{ value: HistoryStatusFilter, label: string }> = [
  { value: 'all', label: '显示全部' },
  { value: 'generating', label: '生成中' },
  { value: 'completed', label: '已完成' },
]

const pageSize = computed(() => 20)
const normalizedKeyword = computed(() => keyword.value.trim().toLowerCase())
const generationTaskIds = computed(() => new Set(generationTasks.value.map(task => task.conversationId)))
const historyTitleCount = computed(() => historyTotal.value || historyList.value.length)
const activityCount = computed(() => historyList.value.filter(item => inferMode(item) === 'activity').length)
const posterCount = computed(() => historyList.value.filter(item => inferMode(item) === 'poster').length)
const historyTypeTabs = computed<Array<{ value: HistoryTypeFilter, label: string }>>(() => [
  { value: 'all', label: '全部' },
  { value: 'activity', label: `活动(${activityCount.value})` },
  { value: 'poster', label: `海报(${posterCount.value})` },
])
const statusFilterLabel = computed(() =>
  statusOptions.find(option => option.value === statusFilter.value)?.label || statusOptions[0].label,
)
const statusFilterMenu = computed(() => ({
  items: statusOptions.map(option => ({
    key: option.value,
    label: option.label,
  })),
  onClick: ({ key }: { key: string }) => {
    statusFilter.value = key as HistoryStatusFilter
  },
}))
const filteredHistoryList = computed(() => {
  return historyList.value.filter((item) => {
    if (activeType.value !== 'all' && inferMode(item) !== activeType.value)
      return false

    const isGenerating = isHistoryGenerating(item)
    if (statusFilter.value === 'generating' && !isGenerating)
      return false
    if (statusFilter.value === 'completed' && isGenerating)
      return false

    if (!normalizedKeyword.value)
      return true

    return getHistorySearchText(item).includes(normalizedKeyword.value)
  })
})
const pagedHistoryList = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return filteredHistoryList.value.slice(start, start + pageSize.value)
})

watch([activeType, statusFilter, keyword, viewMode], () => {
  page.value = 1
})

watch([filteredHistoryList, pageSize], () => {
  const maxPage = Math.max(1, Math.ceil(filteredHistoryList.value.length / pageSize.value))
  if (page.value > maxPage)
    page.value = maxPage
})

onMounted(() => {
  refreshGenerationTasks()
  unsubscribeGenerationTasks = subscribeAiGenerationTasks(refreshGenerationTasks)
  if (!hasAiAccessToken()) {
    loginGuideOpen.value = true
    return
  }
  void fetchHistoryList()
})

onUnmounted(() => {
  unsubscribeGenerationTasks?.()
  unsubscribeGenerationTasks = null
})

function refreshGenerationTasks() {
  generationTasks.value = getAiGenerationTasks()
}

function handleLoginAuthenticated() {
  window.location.reload()
}

function getCurrentShopId() {
  const rawShopId = getStore('shop_id')
  if (rawShopId == null)
    return null

  const shopId = Number(rawShopId)
  return Number.isFinite(shopId) && shopId > 0 ? shopId : null
}

async function fetchHistoryList() {
  isHistoryLoading.value = true

  try {
    const result = await api.ai.getAiConversationList({
      shop_id: getCurrentShopId(),
      page: 1,
      per_page: 100,
    })

    historyList.value = result.items || []
    historyTotal.value = result.total || historyList.value.length
    void refreshRemoteGeneratingStates(historyList.value)
  }
  catch {
    historyList.value = []
    historyTotal.value = 0
    remoteGeneratingIds.value = new Set()
    klbMessage.error('历史生成加载失败，请稍后重试')
  }
  finally {
    isHistoryLoading.value = false
  }
}

async function refreshRemoteGeneratingStates(items: AiConversation[]) {
  const targetItems = items.slice(0, 20).filter(item => item.conversation_id)
  if (!targetItems.length) {
    remoteGeneratingIds.value = new Set()
    return
  }

  const generatingIds = new Set<string>()
  await Promise.all(targetItems.map(async (item) => {
    try {
      const result = await api.ai.getAiConversationMessages(item.conversation_id, {
        page: 1,
        per_page: 12,
      })
      if ((result.items || []).some((message: AiMessage) => isGeneratingMessage(message)))
        generatingIds.add(item.conversation_id)
    }
    catch {
      // 单条消息同步失败不影响历史页主体展示。
    }
  }))

  remoteGeneratingIds.value = generatingIds
}

function isGeneratingMessage(message: AiMessage) {
  return message.role === 'assistant' && ['pending', 'streaming'].includes(message.status || '')
}

function inferMode(item: AiConversation): 'activity' | 'poster' {
  const raw = [
    item.scene,
    item.title,
    item.meta?.mode,
    item.meta?.scene,
    item.meta?.type,
    item.meta?.generation_type,
  ].map(value => String(value || '').toLowerCase()).join(' ')

  return /poster|海报|主视觉|kv|image/.test(raw) ? 'poster' : 'activity'
}

function inferModeLabel(item: AiConversation) {
  return inferMode(item) === 'poster' ? '海报' : '活动'
}

function getHistoryTitle(item: AiConversation) {
  const title = String(item.title || '').trim()
  if (title)
    return title

  return inferMode(item) === 'poster' ? '未命名海报' : '未命名活动'
}

function getHistoryPreviewImage(item: AiConversation) {
  return String(item.preview_image || item.preview_image_url || '').trim()
}

function getHistoryPlaceholderIcon(item: AiConversation) {
  return inferMode(item) === 'poster' ? 'icon-tupian' : 'icon-huodongzhutu'
}

function getHistoryTime(item: AiConversation) {
  return item.updated_at || item.latest_message_at || item.created_at
}

function getHistorySearchText(item: AiConversation) {
  return [
    getHistoryTitle(item),
    inferModeLabel(item),
    formatUpdatedAt(getHistoryTime(item)),
    item.conversation_id,
  ].join(' ').toLowerCase()
}

function isHistoryGenerating(item: AiConversation) {
  if (generationTaskIds.value.has(item.conversation_id) || remoteGeneratingIds.value.has(item.conversation_id))
    return true

  const meta = item.meta || {}
  const candidates = [
    meta.status,
    meta.task_status,
    meta.generation_status,
    meta.generate_status,
    meta.poster_status,
    meta.activity_status,
    meta.message_status,
    meta.generation?.status,
    meta.task?.status,
  ]

  if (meta.is_generating === true || meta.generating === true)
    return true

  return candidates.some((value) => {
    const text = String(value || '').toLowerCase()
    return ['pending', 'streaming', 'generating', 'running', 'processing', 'queued'].includes(text)
  })
}

function formatUpdatedAt(value?: string | null) {
  const raw = String(value || '').trim()
  if (!raw)
    return '暂无更新时间'
  if (/^(更新于|刚刚|昨天|\d+\s*(秒|分钟|小时|天)前)/.test(raw))
    return raw.startsWith('更新于') ? raw : `更新于 ${raw}`

  const timestamp = parseDateTime(raw)
  if (!timestamp)
    return `更新于 ${raw}`

  const now = Date.now()
  const diff = Math.max(0, now - timestamp)
  const minute = 60 * 1000
  const hour = 60 * minute
  const day = 24 * hour

  if (diff < minute)
    return '更新于 刚刚'
  if (diff < hour)
    return `更新于 ${Math.floor(diff / minute)}分钟前`
  if (diff < day)
    return `更新于 ${Math.floor(diff / hour)}小时前`
  if (diff < 2 * day)
    return `更新于 昨天 ${formatClock(timestamp)}`

  const date = new Date(timestamp)
  const currentYear = new Date(now).getFullYear()
  const prefix = date.getFullYear() === currentYear
    ? `${date.getMonth() + 1}月${date.getDate()}日`
    : `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`
  return `更新于 ${prefix}`
}

function parseDateTime(value: string) {
  const directTime = new Date(value).getTime()
  if (Number.isFinite(directTime))
    return directTime

  const normalizedTime = new Date(value.replace(/-/g, '/')).getTime()
  return Number.isFinite(normalizedTime) ? normalizedTime : 0
}

function formatClock(timestamp: number) {
  const date = new Date(timestamp)
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

function goBack() {
  router.push('/')
}

function openConversation(item: AiConversation) {
  router.push({
    path: '/chat',
    query: {
      conversationId: item.conversation_id,
      from: 'history',
    },
  })
}

function getHistoryActionMenu(item: AiConversation) {
  return {
    items: [
      {
        key: 'edit',
        label: '编辑',
        icon: h('i', { class: 'iconfont icon-bianji' }),
      },
      {
        key: 'delete',
        label: '删除',
        icon: h('i', { class: 'iconfont icon-shanchu' }),
        danger: true,
      },
    ],
    onClick: ({ key }: { key: string }) => {
      if (key === 'edit') {
        openConversation(item)
        return
      }

      if (key === 'delete')
        removeHistoryItem(item)
    },
  }
}

function removeHistoryItem(item: AiConversation) {
  historyList.value = historyList.value.filter(history => history.conversation_id !== item.conversation_id)
  historyTotal.value = Math.max(0, historyTotal.value - 1)
  remoteGeneratingIds.value = new Set([...remoteGeneratingIds.value].filter(id => id !== item.conversation_id))
  klbMessage.info('后端暂未提供历史删除接口，已从当前列表移除')
}
</script>

<style scoped>
.ai-history-page {
  min-height: 100vh;
  min-width: 1440px;
  overflow-x: hidden;
  background: #f1f3f5;
  color: #0f182a;
}

.ai-history-page__bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  background-image: radial-gradient(rgba(148, 163, 184, 0.16) 0.8px, transparent 0.8px);
  background-size: 16px 16px;
}

.ai-history-page__main {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  padding-top: 72px;
}

.ai-history-page__header {
  position: fixed;
  inset-inline: 0;
  top: 0;
  z-index: 50;
  height: 72px;
  padding: 0 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  background: #f1f3f5;
  box-sizing: border-box;
}

.ai-history-page__header::before {
  position: absolute;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-image: radial-gradient(rgba(148, 163, 184, 0.16) 0.8px, transparent 0.8px);
  background-size: 16px 16px;
  content: '';
}

.ai-history-page__back {
  width: 62px;
  height: 36px;
  padding: 0;
  border: 0;
  border-radius: 12px;
  background: #ffffff;
  color: #0f182a;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  transition: box-shadow 0.18s ease, background-color 0.18s ease;
}

.ai-history-page__back:hover {
  background: #fff;
  box-shadow: 0 8px 20px rgba(15, 24, 42, 0.08);
}

.ai-history-page__back .iconfont {
  font-size: 18px;
  line-height: 1;
}

.ai-history-page__back span {
  color: #0f182a;
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
}

.ai-history-page__title {
  margin: 0;
  color: #0f182a;
  font-size: 18px;
  font-weight: 600;
  line-height: 25px;
}

.ai-history-page__content {
  width: 1200px;
  margin: 0 auto;
  padding-bottom: 36px;
}

.ai-history-toolbar {
  position: sticky;
  top: 72px;
  z-index: 20;
  height: 68px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #f1f3f5;
  background-image: radial-gradient(rgba(148, 163, 184, 0.16) 0.8px, transparent 0.8px);
  background-size: 16px 16px;
}

.ai-history-tabs {
  display: inline-flex;
  align-items: center;
  gap: 32px;
  height: 100%;
}

.ai-history-tabs__item {
  position: relative;
  height: 68px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  font-size: 16px;
  font-weight: 500;
  line-height: 25px;
  transition: color 0.18s ease;
}

.ai-history-tabs__item:hover,
.ai-history-tabs__item.is-active {
  color: #0f182a;
}

.ai-history-tabs__item.is-active {
  font-size: 18px;
  font-weight: 600;
}

.ai-history-tabs__item.is-active::after {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 1px;
  width: 36px;
  height: 4px;
  margin: auto;
  border-radius: 2px 2px 0 0;
  background: #0f182a;
  content: '';
}

.ai-history-toolbar__actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.ai-history-filter {
  height: 36px;
  min-width: 116px;
  padding: 0 12px;
  border: 0;
  border-radius: 8px;
  background: #fff;
  color: #0f182a;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  transition: background-color 0.18s ease, box-shadow 0.18s ease;
}

.ai-history-filter:hover {
  background: #fff;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
}

.ai-history-filter__arrow {
  display: inline-flex;
  font-size: 14px;
  line-height: 1;
  transform: rotate(90deg);
}

.ai-history-view-toggle {
  flex: 0 0 auto;
  gap: 4px;
  height: 36px;
  padding: 3px;
  border-radius: 8px;
  background: #e3e6eb;
}

.ai-history-grid {
  display: grid;
  grid-template-columns: repeat(5, 227px);
  gap: 16px;
  align-items: start;
}

.history-card {
  position: relative;
  width: 227px;
  height: 300px;
  padding: 10px;
  border-radius: 24px;
  background: #ffffff;
  cursor: pointer;
  box-sizing: border-box;
  overflow: hidden;
  transition: box-shadow 0.2s ease;
}

.history-card:hover {
  box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
}

.history-card__preview {
  position: relative;
}

.history-card__badge {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 2;
  height: 18px;
  min-width: 32px;
  padding: 2px 4px;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.5);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 400;
  line-height: 14px;
  box-sizing: border-box;
}

.history-card__more {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 3;
  width: 24px;
  height: 24px;
  padding: 0;
  border: 0;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.23);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.16s ease, visibility 0.16s ease, background-color 0.16s ease;
}

.history-card:hover .history-card__more {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}

.history-card__more:hover {
  background: rgba(0, 0, 0, 0.36);
}

.history-card__more .iconfont {
  font-size: 20px;
  line-height: 1;
  transform: rotate(90deg);
}

.history-card__image-wrap {
  position: relative;
  width: 207px;
  height: 207px;
  overflow: hidden;
  border-radius: 14px;
  background-color: #f1f3f5;
  box-sizing: border-box;
}

.history-card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.history-card__placeholder,
.ai-history-list__placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c8cdd2;
}

.history-card__placeholder .iconfont {
  font-size: 34px;
  line-height: 1;
}

.history-card--skeleton {
  cursor: default;
  pointer-events: none;
}

.history-card--skeleton:hover {
  box-shadow: none;
}

.history-card__body {
  min-width: 0;
  padding: 14px 6px 0;
}

.history-card__title {
  max-width: 100%;
  overflow: hidden;
  color: #0f182a;
  font-size: 16px;
  font-weight: 500;
  line-height: 22px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.history-card__meta {
  min-width: 0;
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
}

.history-card__meta-divider {
  width: 1px;
  height: 10px;
  border-radius: 1px;
  background: #f1f3f5;
  flex: 0 0 auto;
}

.history-card__time {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.history-generating-status {
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #7d52c5;
  font-size: 12px;
  font-weight: 500;
  line-height: 17px;
  white-space: nowrap;
}

.history-generating-status__star {
  width: 14px;
  height: 14px;
  filter: drop-shadow(0 0 6px rgba(125, 82, 197, 0.42));
  animation: history-generating-star-glow 1.8s ease-in-out infinite;
}

.history-generating-status__text {
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
  animation: history-generating-text-shine var(--ai-working-text-shine-duration) linear infinite;
  text-shadow: 0 0 12px rgba(144, 83, 205, 0.16);
}

@keyframes history-generating-text-shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: 0% 0;
  }
}

@keyframes history-generating-star-glow {
  0%,
  100% {
    opacity: 0.84;
    transform: scale(0.96);
  }

  50% {
    opacity: 1;
    transform: scale(1.06);
  }
}

.ai-history-list-panel {
  min-height: 824px;
  padding: 24px 24px 20px;
  border-radius: 24px;
  background: #ffffff;
  box-sizing: border-box;
}

.ai-history-list-panel--skeleton {
  pointer-events: none;
}

.ai-history-list {
  width: 100%;
}

.ai-history-list__head,
.ai-history-list__row {
  display: grid;
  grid-template-columns: 118px minmax(0, 1fr) 230px 250px 96px;
  align-items: center;
  column-gap: 0;
}

.ai-history-list__head {
  height: 44px;
  padding: 0 16px;
  border-radius: 10px;
  background: #f1f3f5;
  color: #0f182a;
  font-size: 14px;
  font-weight: 600;
  line-height: 20px;
  box-sizing: border-box;
}

.ai-history-list__row {
  min-height: 112px;
  padding: 20px 16px;
  border-bottom: 1px solid #e3e9f1;
  cursor: pointer;
  box-sizing: border-box;
  transition: background-color 0.18s ease;
}

.ai-history-list__row:hover {
  background: #fbfcfd;
}

.ai-history-list__row--skeleton:hover {
  background: transparent;
}

.ai-history-list__cover {
  width: 72px;
  height: 72px;
  border-radius: 8px;
  background: #f1f3f5;
  overflow: hidden;
}

.ai-history-list__cover img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.ai-history-list__placeholder .iconfont {
  font-size: 28px;
  line-height: 1;
}

.ai-history-list__name {
  min-width: 0;
  overflow: hidden;
  color: #0f182a;
  font-size: 16px;
  font-weight: 600;
  line-height: 22px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ai-history-list__type {
  color: #0f182a;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
}

.ai-history-list__time {
  display: flex;
  align-items: center;
  gap: 0;
  color: #0f182a;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
}

.ai-history-list__time.is-generating {
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.ai-history-list__actions {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 20px;
}

.ai-history-list__action {
  width: 24px;
  height: 24px;
  padding: 0;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #0f182a;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.16s ease, color 0.16s ease;
}

.ai-history-list__action:hover {
  background: #f1f3f5;
  color: #0f182a;
}

.ai-history-list__action .iconfont {
  font-size: 20px;
  line-height: 1;
}

.ai-history-list-panel__footer,
.ai-history-grid-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
}

.ai-history-list-panel__footer {
  margin-top: 20px;
}

.ai-history-grid-footer {
  margin-top: 24px;
}

.ai-history-empty {
  display: flex;
  min-height: 360px;
  align-items: center;
  justify-content: center;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.84);
  color: #64748b;
  font-size: 14px;
}

.history-skeleton {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  background: #eef2f6;
}

.history-skeleton::after {
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.78) 48%, transparent 100%);
  animation: history-skeleton-shimmer 1.4s ease-in-out infinite;
  content: '';
}

.history-skeleton--preview {
  width: 207px;
  height: 207px;
  border-radius: 14px;
}

.history-skeleton--title {
  width: 152px;
  height: 20px;
}

.history-skeleton--meta {
  width: 96px;
  height: 16px;
  margin-top: 10px;
}

.history-skeleton--list-cover {
  width: 72px;
  height: 72px;
}

.history-skeleton--list-title {
  width: 260px;
  height: 20px;
}

.history-skeleton--list-type {
  width: 56px;
  height: 18px;
}

.history-skeleton--list-time {
  width: 116px;
  height: 18px;
}

.history-skeleton--list-action {
  width: 68px;
  height: 24px;
  margin-left: auto;
}

@keyframes history-skeleton-shimmer {
  100% {
    transform: translateX(100%);
  }
}

:deep(.ai-history-search.kl-search-input--white .ant-input-wrapper.ant-input-group) {
  border-color: transparent;
  background: #fff;
}

:deep(.ai-history-view-toggle .kl-view-toggle__item) {
  color: #0f182a;
}

:deep(.ai-history-view-toggle .kl-view-toggle__item.is-active) {
  background: #ffffff;
}

:deep(.ai-history-pagination .ant-pagination) {
  display: flex;
  align-items: center;
  gap: 18px;
  color: #99a7bb;
}

:deep(.ai-history-pagination .ant-pagination-item) {
  min-width: 30px;
  height: 30px;
  margin-inline: 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  line-height: 30px;
}

:deep(.ai-history-pagination .ant-pagination-item a) {
  color: #99a7bb;
  font-size: 14px;
}

:deep(.ai-history-pagination .ant-pagination-item-active) {
  background: #0f182a;
}

:deep(.ai-history-pagination .ant-pagination-item-active a) {
  color: #ffffff;
}

:deep(.ai-history-pagination .ant-pagination-prev),
:deep(.ai-history-pagination .ant-pagination-next) {
  min-width: 24px;
  height: 30px;
  margin-inline: 0;
}

:deep(.ai-history-pagination .ant-pagination-prev .ant-pagination-item-link),
:deep(.ai-history-pagination .ant-pagination-next .ant-pagination-item-link) {
  border: 0;
  color: #99a7bb;
  background: transparent;
}

:global(.ai-history-action-dropdown .ant-dropdown-menu-item),
:global(.ai-history-filter-dropdown .ant-dropdown-menu-item) {
  min-width: 96px;
  display: flex;
  align-items: center;
  gap: 8px;
}

:global(.ai-history-action-dropdown .ant-dropdown-menu-item-icon) {
  display: inline-flex;
  width: 18px;
  flex: 0 0 18px;
  align-items: center;
  justify-content: center;
  margin-inline-end: 0;
  font-size: 18px;
  line-height: 1;
}

:global(.ai-history-action-dropdown .ant-dropdown-menu-title-content),
:global(.ai-history-filter-dropdown .ant-dropdown-menu-title-content) {
  display: inline-flex;
  align-items: center;
  line-height: 20px;
}

:global(.ai-history-action-dropdown .ant-dropdown-menu-title-content .iconfont) {
  display: inline-flex;
  width: 18px;
  flex: 0 0 18px;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  line-height: 1;
}
</style>
