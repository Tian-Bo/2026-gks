<template>
  <section class="activity-deep-summary">
    <div v-if="typedSummary" class="activity-deep-summary__text">{{ typedSummary }}</div>

    <div v-if="visiblePlanItems.length" class="activity-deep-summary__plan">
      <div v-for="item in visiblePlanItems" :key="item.label" class="activity-deep-summary__plan-row">
        <span class="activity-deep-summary__check iconfont icon-zhengque" aria-hidden="true" />
        <span class="activity-deep-summary__label">{{ item.label }}：</span>
        <strong>{{ item.value }}</strong>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

type ActivityDeepPlan = Record<string, any>

const props = withDefaults(defineProps<{
  summary?: string
  plan?: ActivityDeepPlan | null
  animate?: boolean
}>(), {
  summary: '',
  plan: null,
  animate: false,
})

const emit = defineEmits<{
  (event: 'revealComplete'): void
}>()

const typedSummary = ref('')
const visiblePlanCount = ref(0)
let summaryRevealTimer: ReturnType<typeof window.setInterval> | null = null
let planRevealTimer: ReturnType<typeof window.setInterval> | null = null
let revealDelayTimer: ReturnType<typeof window.setTimeout> | null = null

function pickPlanValue(plan: ActivityDeepPlan, keys: string[]) {
  for (const key of keys) {
    const value = plan[key]
    if (Array.isArray(value)) {
      const text = value
        .map(item => typeof item === 'object' && item ? item.title || item.name || item.label || item.value : item)
        .filter(Boolean)
        .join('，')
      if (text.trim())
        return text
    }

    if (value && typeof value === 'object') {
      const text = String(value.title || value.name || value.label || value.value || '').trim()
      if (text)
        return text
    }

    if (value !== undefined && value !== null && String(value).trim())
      return String(value).trim()
  }

  return ''
}

function getActivityModelPlanValue(plan: ActivityDeepPlan) {
  const value = pickPlanValue(plan, ['activity_model_title', 'activity_model'])
  // 兼容旧会话：auto 是工具栏“未指定”占位，不能作为活动玩法展示。
  return value.toLowerCase() === 'auto' ? '红包获客' : value
}

const planItems = computed(() => {
  const plan = props.plan || {}
  return [
    { label: '活动目标', value: pickPlanValue(plan, ['goal_title', 'goal_label', 'goal', 'activity_goal']) },
    { label: '活动时间', value: pickPlanValue(plan, ['duration_label', 'duration', 'date_range', 'time_range']) },
    { label: '活动玩法', value: getActivityModelPlanValue(plan) },
    { label: '活动名称', value: pickPlanValue(plan, ['configured_activity_title']) },
    { label: '顾客端文案', value: pickPlanValue(plan, ['customer_display_text']) },
    { label: '主推项目', value: pickPlanValue(plan, ['items', 'products', 'selected_items', 'selected_products', 'item_titles']) },
    { label: '商品配置', value: pickPlanValue(plan, ['item_summary']) },
    { label: '活动机制', value: pickPlanValue(plan, ['activity_mechanism']) },
    { label: '自动匹配风格', value: pickPlanValue(plan, ['style_title', 'style_label', 'style']) },
    { label: '核心活动信息', value: [pickPlanValue(plan, ['title', 'activity_title']), pickPlanValue(plan, ['key_copy', 'slogan', 'benefit'])].filter(Boolean).join('，') },
    { label: '分享标题', value: pickPlanValue(plan, ['share_title']) },
    { label: '分享描述', value: pickPlanValue(plan, ['share_description']) },
    { label: '补充说明', value: pickPlanValue(plan, ['notes', 'note', 'requirement', 'extra_requirement']) },
  ].filter((item): item is { label: string, value: string } => item.value.trim() !== '')
})

const visiblePlanItems = computed(() => planItems.value.slice(0, visiblePlanCount.value))

function clearRevealTimers() {
  if (summaryRevealTimer)
    window.clearInterval(summaryRevealTimer)
  if (planRevealTimer)
    window.clearInterval(planRevealTimer)
  if (revealDelayTimer)
    window.clearTimeout(revealDelayTimer)

  summaryRevealTimer = null
  planRevealTimer = null
  revealDelayTimer = null
}

function revealPlanItems() {
  if (!planItems.value.length) {
    emit('revealComplete')
    return
  }

  visiblePlanCount.value = 1
  planRevealTimer = window.setInterval(() => {
    visiblePlanCount.value += 1
    if (visiblePlanCount.value < planItems.value.length)
      return

    if (planRevealTimer)
      window.clearInterval(planRevealTimer)
    planRevealTimer = null
    emit('revealComplete')
  }, 120)
}

function revealImmediately() {
  clearRevealTimers()
  typedSummary.value = props.summary.trim()
  visiblePlanCount.value = planItems.value.length
  emit('revealComplete')
}

function startReveal() {
  clearRevealTimers()
  typedSummary.value = ''
  visiblePlanCount.value = 0

  const source = props.summary.trim()
  if (!source) {
    revealPlanItems()
    return
  }

  let cursor = 0
  summaryRevealTimer = window.setInterval(() => {
    const next = source.slice(cursor, cursor + 2)
    typedSummary.value += next
    cursor += next.length

    if (cursor < source.length)
      return

    if (summaryRevealTimer)
      window.clearInterval(summaryRevealTimer)
    summaryRevealTimer = null
    revealDelayTimer = window.setTimeout(revealPlanItems, 160)
  }, 28)
}

watch(
  () => `${props.summary}|${JSON.stringify(props.plan || {})}`,
  () => {
    if (props.animate)
      startReveal()
    else
      revealImmediately()
  },
)

onMounted(() => {
  if (props.animate)
    startReveal()
  else
    revealImmediately()
})
onBeforeUnmount(clearRevealTimers)
</script>

<style scoped>
.activity-deep-summary {
  width: 100%;
  color: #0f182a;
  font-size: 14px;
  line-height: 26px;
}

.activity-deep-summary__text {
  white-space: pre-wrap;
}

.activity-deep-summary__plan {
  display: grid;
  gap: 4px;
  margin-top: 18px;
  padding: 12px 14px;
  border: 1px solid rgba(218, 226, 242, 0.9);
  border-radius: 14px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.86), rgba(248, 251, 255, 0.92)),
    #f8fbff;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.86),
    0 8px 22px rgba(15, 24, 42, 0.04);
}

.activity-deep-summary__plan-row {
  display: grid;
  grid-template-columns: 18px 112px minmax(0, 1fr);
  align-items: start;
  gap: 8px;
  min-height: 34px;
  padding: 5px 0;
}

.activity-deep-summary__check {
  display: inline-flex;
  width: 18px;
  height: 18px;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  background: #2eb450;
  color: #ffffff;
  font-size: 12px;
  line-height: 1;
  transform: translateY(3px);
}

.activity-deep-summary__label {
  color: #64748b;
  white-space: nowrap;
}

.activity-deep-summary__plan-row strong {
  color: #0f182a;
  font-weight: 400;
  word-break: break-word;
}
</style>
