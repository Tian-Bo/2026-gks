<template>
  <span ref="containerRef" class="ai-lottie-star" :style="starStyle" aria-hidden="true"></span>
</template>

<script setup lang="ts">
import lottie from 'lottie-web/build/player/lottie_light'
import starAnimation from '@/assets/lottie/ai-star.json'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = withDefaults(defineProps<{
  size?: number
  width?: number
  height?: number
  loop?: boolean
  autoplay?: boolean
  initialFrame?: number
}>(), {
  size: 20,
  loop: true,
  autoplay: true,
  initialFrame: 0,
})

const containerRef = ref<HTMLSpanElement | null>(null)
let animation: ReturnType<typeof lottie.loadAnimation> | null = null
let reducedMotionQuery: MediaQueryList | null = null

const starStyle = computed(() => ({
  width: `${props.width ?? props.size}px`,
  height: `${props.height ?? props.size}px`,
}))

function renderAnimation() {
  if (!containerRef.value)
    return

  const shouldPlay = props.autoplay && !reducedMotionQuery?.matches
  animation?.destroy()
  animation = lottie.loadAnimation({
    container: containerRef.value,
    renderer: 'svg',
    loop: shouldPlay && props.loop,
    autoplay: shouldPlay,
    animationData: starAnimation,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid meet',
    },
  })

  if (!shouldPlay) {
    const showInitialFrame = () => animation?.goToAndStop(props.initialFrame, true)
    animation.addEventListener('DOMLoaded', showInitialFrame)
    showInitialFrame()
  }
}

onMounted(() => {
  reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  reducedMotionQuery.addEventListener('change', renderAnimation)
  renderAnimation()
})

watch(() => [props.loop, props.autoplay, props.initialFrame], renderAnimation)

onBeforeUnmount(() => {
  reducedMotionQuery?.removeEventListener('change', renderAnimation)
  reducedMotionQuery = null
  animation?.destroy()
  animation = null
})
</script>

<style scoped>
.ai-lottie-star {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  line-height: 0;
  overflow: hidden;
  pointer-events: none;
  vertical-align: middle;
}

.ai-lottie-star :deep(svg) {
  display: block;
  width: 100%;
  height: 100%;
  overflow: hidden;
}
</style>
