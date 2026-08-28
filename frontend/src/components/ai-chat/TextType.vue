<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

interface TextTypeProps {
  className?: string
  showCursor?: boolean
  hideCursorWhileTyping?: boolean
  cursorCharacter?: string
  cursorBlinkDuration?: number
  cursorClassName?: string
  text: string | string[]
  as?: string
  typingSpeed?: number
  initialDelay?: number
  pauseDuration?: number
  deletingSpeed?: number
  loop?: boolean
  random?: boolean
  textColors?: string[]
  variableSpeed?: { min: number, max: number }
  onSentenceComplete?: (sentence: string, index: number) => void
  startOnVisible?: boolean
  reverseMode?: boolean
}

const props = withDefaults(defineProps<TextTypeProps>(), {
  as: 'span',
  typingSpeed: 50,
  initialDelay: 0,
  pauseDuration: 2000,
  deletingSpeed: 30,
  loop: true,
  random: false,
  className: '',
  showCursor: true,
  hideCursorWhileTyping: false,
  cursorCharacter: '|',
  cursorBlinkDuration: 0.5,
  textColors: () => [],
  startOnVisible: false,
  reverseMode: false,
})

const displayedText = ref('')
const currentCharIndex = ref(0)
const isDeleting = ref(false)
const currentTextIndex = ref(0)
const isVisible = ref(!props.startOnVisible)
const cursorRef = ref<HTMLElement | null>(null)
const containerRef = ref<HTMLElement | null>(null)

let timeout: ReturnType<typeof setTimeout> | null = null
let observer: IntersectionObserver | null = null
let cursorTween: { kill: () => void } | null = null

const textArray = computed(() => {
  const values = Array.isArray(props.text) ? props.text : [props.text]
  return values.map(item => String(item || '').trim()).filter(Boolean)
})

const currentProcessedText = computed(() => {
  const text = textArray.value[currentTextIndex.value] || ''
  return props.reverseMode ? text.split('').reverse().join('') : text
})

const currentTextStyle = computed(() => {
  if (!props.textColors.length)
    return undefined

  return {
    color: props.textColors[currentTextIndex.value % props.textColors.length],
  }
})

const shouldHideCursor = computed(() =>
  props.hideCursorWhileTyping
  && (currentCharIndex.value < currentProcessedText.value.length || isDeleting.value),
)

function clearTypingTimer() {
  if (timeout) {
    clearTimeout(timeout)
    timeout = null
  }
}

function getRandomSpeed() {
  if (!props.variableSpeed)
    return props.typingSpeed

  const min = Math.max(0, props.variableSpeed.min)
  const max = Math.max(min, props.variableSpeed.max)
  return Math.random() * (max - min) + min
}

function getNextTextIndex(length: number) {
  if (length <= 1)
    return props.loop ? 0 : -1

  if (!props.random)
    return (currentTextIndex.value + 1) % length

  let nextIndex = currentTextIndex.value
  for (let attempt = 0; attempt < 8 && nextIndex === currentTextIndex.value; attempt += 1)
    nextIndex = Math.floor(Math.random() * length)

  return nextIndex === currentTextIndex.value
    ? (currentTextIndex.value + 1) % length
    : nextIndex
}

function schedule(callback: () => void, delay: number) {
  clearTypingTimer()
  timeout = setTimeout(callback, Math.max(0, delay))
}

function typeNextFrame() {
  if (!isVisible.value)
    return

  const texts = textArray.value
  if (!texts.length) {
    displayedText.value = ''
    return
  }

  if (currentTextIndex.value >= texts.length)
    currentTextIndex.value = 0

  const currentSentence = texts[currentTextIndex.value]
  const processedText = currentProcessedText.value

  if (isDeleting.value) {
    if (displayedText.value.length > 0) {
      schedule(() => {
        displayedText.value = displayedText.value.slice(0, -1)
        typeNextFrame()
      }, props.deletingSpeed)
      return
    }

    props.onSentenceComplete?.(currentSentence, currentTextIndex.value)
    const nextIndex = getNextTextIndex(texts.length)
    if (nextIndex === -1)
      return

    currentTextIndex.value = nextIndex
    currentCharIndex.value = 0
    isDeleting.value = false
    schedule(typeNextFrame, 0)
    return
  }

  if (currentCharIndex.value < processedText.length) {
    schedule(() => {
      displayedText.value += processedText.charAt(currentCharIndex.value)
      currentCharIndex.value += 1
      typeNextFrame()
    }, props.variableSpeed ? getRandomSpeed() : props.typingSpeed)
    return
  }

  if (texts.length > 1 || props.loop) {
    schedule(() => {
      isDeleting.value = true
      typeNextFrame()
    }, props.pauseDuration)
  }
}

function resetTyping() {
  clearTypingTimer()
  const texts = textArray.value
  displayedText.value = ''
  currentCharIndex.value = 0
  isDeleting.value = false
  currentTextIndex.value = props.random && texts.length > 1
    ? Math.floor(Math.random() * texts.length)
    : 0

  if (isVisible.value)
    schedule(typeNextFrame, props.initialDelay)
}

async function startCursorBlink() {
  if (!props.showCursor || !cursorRef.value)
    return

  cursorTween?.kill()
  cursorTween = null

  const { gsap } = await import('gsap')
  if (cursorRef.value) {
    gsap.set(cursorRef.value, { opacity: 1 })
    cursorTween = gsap.to(cursorRef.value, {
      opacity: 0,
      duration: props.cursorBlinkDuration,
      repeat: -1,
      yoyo: true,
      ease: 'power2.inOut',
    })
  }
}

function stopCursorBlink() {
  cursorTween?.kill()
  cursorTween = null
  if (cursorRef.value)
    cursorRef.value.style.opacity = ''
}

onMounted(() => {
  void startCursorBlink()

  if (props.startOnVisible && containerRef.value) {
    observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting)
          isVisible.value = true
      })
    }, { threshold: 0.1 })
    observer.observe(containerRef.value)
  }

  resetTyping()
})

onBeforeUnmount(() => {
  clearTypingTimer()
  stopCursorBlink()
  observer?.disconnect()
  observer = null
})

watch(
  () => [
    textArray.value.join('\u0000'),
    props.loop,
    props.random,
    props.reverseMode,
    props.initialDelay,
    props.pauseDuration,
    props.typingSpeed,
    props.deletingSpeed,
  ],
  () => resetTyping(),
)

watch(isVisible, (visible) => {
  if (visible)
    resetTyping()
  else
    clearTypingTimer()
})

watch(
  () => props.showCursor && Boolean(displayedText.value) && !shouldHideCursor.value,
  async (visible) => {
    await nextTick()
    if (visible)
      void startCursorBlink()
    else
      stopCursorBlink()
  },
  { flush: 'post' },
)
</script>

<template>
  <component
    :is="as"
    ref="containerRef"
    :class="['inline-block whitespace-pre-wrap tracking-tight', className]"
    :style="currentTextStyle"
    v-bind="$attrs"
  >
    {{ displayedText }}<span
      v-if="showCursor && displayedText"
      ref="cursorRef"
      :class="['text-type-cursor', shouldHideCursor ? 'hidden' : '', cursorClassName]"
    >{{ cursorCharacter }}</span>
  </component>
</template>

<style scoped>
.text-type-cursor {
  display: inline-flex;
  margin-left: 2px;
  color: #7c3aed;
  line-height: 1;
  opacity: 1;
  transform-origin: center;
  -webkit-text-fill-color: currentColor;
}

.text-type-underline-cursor {
  position: relative;
  width: 0.58em;
  height: 1em;
  color: transparent;
  text-align: center;
  transform: translateY(1px);
  -webkit-text-fill-color: transparent;
}

.text-type-underline-cursor::after {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0.1em;
  height: 2px;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    rgba(15, 24, 42, 0) 0%,
    #7d52c5 48%,
    #9053cd 100%
  );
  content: "";
}
</style>
