<template>
  <section class="poster-deep-summary">
    <div v-if="typedSummary" class="poster-deep-summary__text">{{ typedSummary }}</div>

    <div v-if="visiblePlanItems.length" class="poster-deep-summary__plan">
      <div v-for="item in visiblePlanItems" :key="item.label" class="poster-deep-summary__plan-row">
        <span class="poster-deep-summary__check iconfont icon-zhengque" aria-hidden="true" />
        <span class="poster-deep-summary__label">{{ item.label }}：</span>
        <strong>{{ item.value }}</strong>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { getImageModelDisplayName } from '../../shared/composerOptions'

type PosterDeepPlan = {
  style?: string | null
  style_title?: string | null
  aspect_ratio?: string | null
  image_model?: string | null
  image_model_title?: string | null
  title?: string | null
  key_copy?: string | null
  notes?: string | null
}

const props = withDefaults(defineProps<{
  summary?: string
  plan?: PosterDeepPlan | null
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

const planItems = computed(() => {
  const plan = props.plan || {}
  return [
    { label: '自动匹配风格', value: plan.style_title || plan.style },
    { label: '默认尺寸', value: plan.aspect_ratio ? `${plan.aspect_ratio} 活动海报竖版` : '' },
    { label: '核心海报信息', value: [plan.title, plan.key_copy].filter(Boolean).join('，') },
    { label: '生图模型', value: getImageModelDisplayName(plan.image_model_title || plan.image_model) },
    { label: '补充说明', value: plan.notes },
  ].filter((item): item is { label: string, value: string } => typeof item.value === 'string' && item.value.trim() !== '')
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
.poster-deep-summary {
  width: min(100%, 672px);
  color: #0f182a;
  font-size: 14px;
  line-height: 26px;
}

.poster-deep-summary__text {
  white-space: pre-wrap;
}

.poster-deep-summary__plan {
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

.poster-deep-summary__plan-row {
  display: grid;
  grid-template-columns: 18px 112px minmax(0, 1fr);
  align-items: start;
  gap: 8px;
  min-height: 34px;
  padding: 5px 0;
}

.poster-deep-summary__check {
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

.poster-deep-summary__label {
  color: #64748b;
  white-space: nowrap;
}

.poster-deep-summary__plan-row strong {
  color: #0f182a;
  font-weight: 400;
  word-break: break-word;
}
</style>
