<template>
  <section class="poster-deep-thinking">
    <button type="button" class="poster-deep-thinking__header" @click="expanded = !expanded">
      <span :class="{ 'poster-deep-thinking__shine': status === 'thinking' }">{{ statusText }}</span>
      <i class="iconfont icon-xiala" :class="[{ 'is-expanded': expanded }, status === 'thinking' ? 'poster-deep-thinking__shine' : '']" />
    </button>

    <div v-if="expanded && displayedThinking" class="poster-deep-thinking__content">
      {{ displayedThinking }}
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = withDefaults(defineProps<{
  status?: 'thinking' | 'completed'
  thinking?: string
  animate?: boolean
}>(), {
  status: 'completed',
  thinking: '',
  animate: false,
})

const expanded = ref(props.status === 'thinking')
const displayedThinking = ref('')
const statusText = computed(() => props.status === 'thinking' ? '思考中' : '思考完成')
let typewriterTimer: ReturnType<typeof window.setTimeout> | null = null

function clearTypewriterTimer() {
  if (typewriterTimer) {
    window.clearTimeout(typewriterTimer)
    typewriterTimer = null
  }
}

function syncDisplayedThinking() {
  clearTypewriterTimer()

  const fullText = props.thinking || ''
  if (!props.animate) {
    displayedThinking.value = fullText
    return
  }

  if (!fullText.startsWith(displayedThinking.value))
    displayedThinking.value = ''

  const typeNextChunk = () => {
    const remaining = fullText.slice(displayedThinking.value.length)
    if (!remaining)
      return

    // 思考流仍在持续时保持可感知的逐字效果；收到完整结果后快速追上，避免阻塞确认卡。
    const chunkSize = props.status === 'completed' ? 4 : (fullText.length > 360 ? 3 : 2)
    displayedThinking.value += remaining.slice(0, chunkSize)
    typewriterTimer = window.setTimeout(typeNextChunk, props.status === 'completed' ? 8 : 18)
  }

  typeNextChunk()
}

watch(() => props.status, (status) => {
  expanded.value = status === 'thinking'
})

watch(
  () => [props.thinking, props.animate, props.status],
  syncDisplayedThinking,
  { immediate: true },
)

onBeforeUnmount(clearTypewriterTimer)
</script>

<style scoped>
.poster-deep-thinking {
  width: min(100%, 672px);
  overflow: visible;
  color: #8a9bb4;
}

.poster-deep-thinking.ai-component-reveal-block {
  clip-path: none;
  animation-name: ai-poster-deep-thinking-reveal;
}

.poster-deep-thinking__header {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border: 0;
  background: transparent;
  padding: 0;
  color: #99a7bb;
  cursor: pointer;
  font-size: 12px;
  font-weight: 400;
  line-height: 18px;
}

.poster-deep-thinking__header .iconfont {
  font-size: 12px;
  transition: transform 0.18s ease;
}

.poster-deep-thinking__header .iconfont.is-expanded {
  transform: rotate(180deg);
}

.poster-deep-thinking__content {
  margin-top: 12px;
  white-space: pre-wrap;
  color: #99a7bb;
  font-size: 12px;
  font-weight: 400;
  line-height: 22px;
  overflow: visible;
  word-break: break-word;
}

.poster-deep-thinking__shine {
  display: inline-block;
  padding: 0 1px;
  margin: 0 -1px;
  background: linear-gradient(90deg, #8a9bb4 0%, #8a9bb4 34%, #ffffff 50%, #8a9bb4 66%, #8a9bb4 100%);
  background-size: 220% 100%;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
  animation: poster-deep-thinking-shine 5.6s ease-in-out infinite;
}

@keyframes poster-deep-thinking-shine {
  0% {
    background-position: 120% 0;
  }

  100% {
    background-position: -120% 0;
  }
}

@keyframes ai-poster-deep-thinking-reveal {
  0% {
    opacity: 0;
    transform: translateY(14px) scale(0.985);
  }

  45% {
    opacity: 1;
  }

  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
