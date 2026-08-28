<template>
  <section class="thinking-process-card">
    <button type="button" class="thinking-process-card__header" @click="expanded = !expanded">
      <span :class="{ 'thinking-process-card__shine': status === 'thinking' }">{{ statusText }}</span>
      <i
        class="iconfont icon-xiala"
        :class="[
          { 'is-expanded': expanded },
          status === 'thinking' ? 'thinking-process-card__shine' : '',
        ]"
      />
    </button>

    <ul v-if="expanded && normalizedSummaryItems.length" class="thinking-process-card__content">
      <li v-for="(item, index) in normalizedSummaryItems" :key="`${index}-${item}`">
        {{ item }}
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = defineProps<{
  status: 'thinking' | 'completed'
  summaryItems: string[]
}>()

const expanded = ref(props.status === 'thinking')

const statusText = computed(() => props.status === 'thinking' ? '思考中' : '思考完成')
const normalizedSummaryItems = computed(() =>
  props.summaryItems
    .map(item => String(item || '').trim())
    .filter(Boolean),
)

watch(() => props.status, (status) => {
  expanded.value = status === 'thinking'
})
</script>

<style scoped>
.thinking-process-card {
  width: min(100%, 672px);
  overflow: visible;
  color: #99a7bb;
}

.thinking-process-card.ai-component-reveal-block {
  clip-path: none;
  animation-name: ai-thinking-process-card-reveal;
}

.thinking-process-card__header {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  border: 0;
  background: transparent;
  padding: 0;
  color: #99a7bb;
  cursor: pointer;
  font-size: 12px;
  font-weight: 400;
  line-height: 18px;
}

.thinking-process-card__header .iconfont {
  font-size: 12px;
  line-height: 1;
  transition: transform 0.18s ease;
}

.thinking-process-card__header .iconfont.is-expanded {
  transform: rotate(180deg);
}

.thinking-process-card__content {
  margin: 12px 0 0;
  padding-left: 18px;
  color: #99a7bb;
  font-size: 12px;
  font-weight: 400;
  line-height: 22px;
  list-style: disc;
  overflow: visible;
  word-break: break-word;
}

.thinking-process-card__content li + li {
  margin-top: 8px;
}

.thinking-process-card__shine {
  display: inline-block;
  padding: 0 1px;
  margin: 0 -1px;
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
  animation: ai-thinking-shine var(--ai-working-text-shine-duration) linear infinite;
}

.thinking-process-card__shine .iconfont {
  color: transparent;
}

@keyframes ai-thinking-shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: 0% 0;
  }
}

@keyframes ai-thinking-process-card-reveal {
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
