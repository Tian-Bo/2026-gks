<template>
  <div class="chat-composer-shell shrink-0 px-4 py-3">
    <div class="chat-composer-meta mb-2 flex items-center justify-between text-[11px] text-[#98a2b3]">
      <div class="flex items-center gap-2">
        <img class="ml-[16px] h-[34px] w-[42px] translate-y-[8px]"
          src="https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778685375_ECnJgJflHi.png">
        <span class="c-[#99A7BB] pt-[10px] c-[#99A7BB]">{{ composerSummaryText }}</span>
      </div>
      <div v-if="generationTimingText" class="pr-[12px]">
        <span class="c-[#0F182A]">{{ generationTimingText?.actual || '00:00' }}</span>
        <span class="c-[#64748B]"> / {{ generationTimingText?.estimated || '2分钟' }}</span>
      </div>
    </div>

    <div ref="composerContainerRef"
      class="relative flex cursor-text flex-col rounded-[20px] bg-white p-[16px] transition-[box-shadow] duration-200 ease-out"
      :class="isComposerActive
        ? 'shadow-[inset_0_0_0_1.5px_#0F182A,0_10px_24px_rgba(15,24,42,0.10)]'
        : 'shadow-[inset_0_0_0_1px_rgba(15,23,42,0.04)]'" @click="handleComposerInteract">
      <input ref="uploadInputRef" type="file" accept="image/*" multiple class="hidden"
        @change="emit('uploadChange', $event)">

      <div v-if="pastedImages.length" class="mb-[12px] flex flex-wrap gap-[12px]">
        <div v-for="image in pastedImages" :key="image.id"
          class="group relative h-[59px] w-[59px] overflow-hidden rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC]">
          <img :src="image.url" :alt="image.name"
            class="block h-full w-full cursor-pointer object-cover"
            @click="emit('previewImage', image.url)">
          <div
            class="absolute right-[4px] top-[4px] inline-flex h-[20px] w-[20px] cursor-pointer items-center justify-center rounded-full bg-[rgba(15,24,42,0.72)] text-white opacity-0 transition-opacity duration-200 ease-out group-hover:opacity-100"
            @click.stop="emit('removeImage', image.id)">
            <i class="iconfont icon-guanbi text-[12px]"></i>
          </div>
        </div>
      </div>

      <textarea ref="composerTextareaRef" :value="draftMessage"
        class="ai-composer-textarea mb-[16px] min-h-[24px] appearance-none resize-none overflow-y-hidden border-none bg-transparent p-0 text-[13px] leading-6 text-[#111827] shadow-none outline-none ring-0 placeholder:text-[#CBD5E1] focus:outline-none"
        :placeholder="placeholder" @input="handleDraftInput" @paste="emit('promptPaste', $event)"
        @keydown="handleTextareaKeydown" />

      <div class="flex items-center">
        <div class="flex flex-wrap items-center gap-[8px]">
          <div
            class="relative inline-flex h-[36px] w-[36px] cursor-pointer items-center justify-center rounded-full bg-[#F2F5FA] text-[#0F182A]"
            title="上传图片" aria-label="上传图片" @click="openImageUpload">
            <i class="iconfont icon-fujian text-[20px]"></i>
            <span class="ai-thinking-tooltip">上传图片</span>
          </div>
          <div class="relative flex gap-[6px] rounded-full bg-[#F2F5FA] p-[4px]">
            <div
              class="pointer-events-none absolute top-[4px] h-[28px] w-[28px] rounded-full bg-[#fff] shadow-[0_1px_2px_rgba(15,24,42,0.08)] transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
              :class="selectedThinkingMode === 'deep' ? 'left-[4px]' : 'left-[38px]'">
            </div>
            <div
              class="relative z-[1] inline-flex h-[28px] w-[28px] cursor-pointer items-center justify-center rounded-full text-[#0F182A] transition-all duration-200 ease-out"
              :class="selectedThinkingMode === 'deep' ? 'text-[#0F182A] scale-100' : 'text-[#64748B] scale-[0.96]'"
              title="专家模式" aria-label="深度思考" @click="emit('selectThinkingMode', 'deep')">
              <i class="iconfont icon-sikao text-[20px]"></i>
              <span class="ai-thinking-tooltip">专家模式</span>
            </div>
            <div
              class="relative z-[1] inline-flex h-[28px] w-[28px] cursor-pointer items-center justify-center rounded-full text-[#0F182A] transition-all duration-200 ease-out"
              :class="selectedThinkingMode === 'quick' ? 'text-[#0F182A] scale-100' : 'text-[#64748B] scale-[0.96]'"
              title="快捷模式" aria-label="快速思考" @click="emit('selectThinkingMode', 'quick')">
              <i class="iconfont icon-kuaisu text-[20px]"></i>
              <span class="ai-thinking-tooltip">快捷模式</span>
            </div>
          </div>
          <KlDropdown v-for="option in currentPromptOptions" :key="option.key"
            :overlay-width="getPromptOptionOverlayWidth(option.key)" placement="bottomLeft"
            overlay-class-name="ai-selector-dropdown">
            <div
              class="relative flex h-[36px] cursor-pointer items-center gap-[6px] rounded-full bg-[#F2F5FA] pl-[8px] pr-[16px] transition-colors"
              :aria-label="getPromptOptionTooltip(option.key)"
            >
              <span
                class="ai-composer-selected-option-icon"
                :class="{ 'has-image': shouldShowPromptOptionImage(option.key, getPromptOptionSelectedItem(option.key)) }"
              >
                <img
                  v-if="shouldShowPromptOptionImage(option.key, getPromptOptionSelectedItem(option.key))"
                  :src="getPromptOptionSelectedItem(option.key)?.image"
                  :alt="getPromptOptionSelectedItem(option.key)?.label || option.label"
                  class="ai-composer-selected-option-icon__image"
                  @error="handlePromptOptionImageError(option.key, getPromptOptionSelectedItem(option.key))"
                >
                <i
                  v-else
                  class="iconfont text-[20px]"
                  :class="getPromptOptionSelectedIconClass(option.key)"
                ></i>
              </span>
              <span class="max-w-[96px] truncate text-[14px] text-[#0F182A]">{{ getPromptOptionDisplayLabel(option.key) }}</span>
              <span class="ai-thinking-tooltip">{{ getPromptOptionTooltip(option.key) }}</span>
            </div>
            <template #overlay>
              <div class="ai-selector-panel" :class="`ai-selector-panel--${option.key}`">
                <div class="ai-selector-panel__title">{{ getPromptOptionTitle(option.key) }}</div>
                <button
                  v-for="item in getPromptOptionItems(option.key)"
                  :key="item.value"
                  type="button"
                  class="ai-selector-option"
                  :class="{ 'is-active': isPromptOptionSelected(option.key, item.value) }"
                  @click="emit('selectSetting', option.key, item.value)"
                >
                  <span class="ai-selector-option__icon" :class="{ 'has-image': shouldShowPromptOptionImage(option.key, item) }">
                    <img
                      v-if="shouldShowPromptOptionImage(option.key, item)"
                      :src="item.image"
                      :alt="item.label"
                      class="ai-selector-option__image"
                      @error="handlePromptOptionImageError(option.key, item)"
                    />
                    <i class="iconfont" :class="getPromptOptionItemIconClass(option.key, item)"></i>
                  </span>
                  <span class="ai-selector-option__text">
                    <span class="ai-selector-option__label">{{ item.label }}</span>
                    <span class="ai-selector-option__desc">{{ item.desc }}</span>
                  </span>
                  <i
                    v-if="isPromptOptionSelected(option.key, item.value)"
                    class="iconfont icon-chenggong ai-selector-option__check"
                    aria-hidden="true"
                  ></i>
                </button>
              </div>
            </template>
          </KlDropdown>
          <KlDropdown v-if="showAiModelSelector" :overlay-width="300" placement="bottomLeft" overlay-class-name="ai-selector-dropdown">
            <div
              class="relative flex h-[36px] cursor-pointer items-center gap-[6px] rounded-full bg-[#F2F5FA] pl-[8px] pr-[16px] transition-colors"
              aria-label="选择模型"
            >
              <i class="iconfont icon-Vector ai-composer-model-icon"></i>
              <span class="max-w-[112px] truncate text-[14px] text-[#0F182A]">{{ currentModel }}</span>
              <span class="ai-thinking-tooltip">选择模型</span>
            </div>
            <template #overlay>
              <div class="ai-selector-panel ai-selector-panel--ai-model">
                <div class="ai-selector-panel__title">选择AI模型</div>
                <button
                  v-for="item in imageModelOptions"
                  :key="item.value"
                  type="button"
                  class="ai-selector-option"
                  :class="{ 'is-active': isCurrentImageModel(item) }"
                  @click="emit('selectModel', item.value)"
                >
                  <span class="ai-selector-option__icon ai-selector-option__icon--ai-model">
                    <i class="iconfont icon-Vector"></i>
                  </span>
                  <span class="ai-selector-option__text">
                    <span class="ai-selector-option__label">{{ item.label }}</span>
                    <span class="ai-selector-option__desc">{{ item.desc }}</span>
                  </span>
                  <i
                    v-if="isCurrentImageModel(item)"
                    class="iconfont icon-chenggong ai-selector-option__check"
                    aria-hidden="true"
                  ></i>
                </button>
              </div>
            </template>
          </KlDropdown>
        </div>

        <template v-if="sendButtonState === 'working'">
          <div
            class="ai-working-send-button__glow pointer-events-none absolute bottom-[8px] right-[14px] h-[18px] w-[112px] rounded-full">
          </div>
          <div
            class="ai-working-send-button absolute bottom-[12px] right-[12px] inline-flex h-[40px] min-w-[118px] cursor-pointer items-center justify-between rounded-full bg-[#071633] pl-[16px] pr-[6px] text-white shadow-[0_10px_20px_rgba(7,22,51,0.18)]"
            @click="emit('stop')">
            <span class="text-[14px] font-500 leading-none tracking-[0.2px]">工作中...</span>
            <span
              class="ai-working-send-button__stop inline-flex h-[28px] w-[28px] items-center justify-center rounded-full bg-[rgba(255,255,255,0.14)]">
              <span class="ai-working-send-button__stop-icon h-[10px] w-[10px] rounded-[3px] bg-white"></span>
            </span>
          </div>
        </template>
        <template v-else-if="sendButtonState === 'waiting'">
          <div
            class="ai-send-button-wrap ai-send-button-wrap--generating absolute bottom-[16px] right-[16px] h-[36px] w-[36px]">
            <span class="ai-send-button-glow" aria-hidden="true"></span>
            <button
              type="button"
              class="ai-send-button inline-flex h-[36px] w-[36px] cursor-default items-center justify-center rounded-full bg-[#0F182A] text-[#fff] outline-none ring-0 focus:outline-none focus:ring-0"
              disabled>
              <i class="iconfont icon-jiazaizhong ai-send-icon--loading text-[20px]"></i>
            </button>
          </div>
        </template>
        <template v-else>
          <div
            class="ai-send-button-wrap absolute bottom-[16px] right-[16px] h-[36px] w-[36px]">
            <span class="ai-send-button-glow" aria-hidden="true"></span>
            <button
              type="button"
              class="ai-send-button inline-flex h-[36px] w-[36px] cursor-pointer items-center justify-center rounded-full bg-[#0F182A] text-[#fff] outline-none ring-0 focus:outline-none focus:ring-0"
              @mousedown.prevent @click="emit('send')">
              <i class="iconfont icon-fasong text-[20px]"></i>
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { PropType } from 'vue'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import type { PromptOption, PromptOptionKey, SelectorItem } from '../../shared/composerOptions'
import { getPosterSizeIconClass, getPromptOptionTooltip } from '../../shared/composerOptions'

type PastedImage = {
  id: string
  url: string
  name: string
}

const props = defineProps({
  activeMode: {
    type: String as PropType<'activity' | 'poster'>,
    required: true,
  },
  currentModeLabel: {
    type: String,
    required: true,
  },
  draftMessage: {
    type: String,
    required: true,
  },
  pastedImages: {
    type: Array as PropType<PastedImage[]>,
    required: true,
  },
  selectedThinkingMode: {
    type: String as PropType<'deep' | 'quick'>,
    required: true,
  },
  currentPromptOptions: {
    type: Array as PropType<PromptOption[]>,
    required: true,
  },
  currentModel: {
    type: String,
    required: true,
  },
  currentModelValue: {
    type: String,
    default: '',
  },
  composerSummaryText: {
    type: String,
    required: true,
  },
  imageModelOptions: {
    type: Array as PropType<SelectorItem[]>,
    required: true,
  },
  isMessageWorking: {
    type: Boolean,
    required: true,
  },
  generationTimingText: {
    type: Object as PropType<{ actual: string, estimated?: string } | null>,
    default: null,
  },
  sendState: {
    type: String as PropType<'ready' | 'waiting' | 'working'>,
    default: undefined,
  },
  getPromptOptionItems: {
    type: Function as PropType<(key: PromptOptionKey) => SelectorItem[]>,
    required: true,
  },
  getPromptOptionDisplayLabel: {
    type: Function as PropType<(key: PromptOptionKey) => string>,
    required: true,
  },
  getPromptOptionTitle: {
    type: Function as PropType<(key: PromptOptionKey) => string>,
    required: true,
  },
  getPromptOptionOverlayWidth: {
    type: Function as PropType<(key: PromptOptionKey) => number>,
    required: true,
  },
  getPromptOptionIconClass: {
    type: Function as PropType<(key: PromptOptionKey) => string>,
    required: true,
  },
  getPromptOptionSelectedItem: {
    type: Function as PropType<(key: PromptOptionKey) => SelectorItem | undefined>,
    required: true,
  },
  isPromptOptionSelected: {
    type: Function as PropType<(key: PromptOptionKey, value: string) => boolean>,
    required: true,
  },
})

const showAiModelSelector = false

const emit = defineEmits<{
  (e: 'update:draftMessage', value: string): void
  (e: 'uploadChange', event: Event): void
  (e: 'promptPaste', event: ClipboardEvent): void
  (e: 'previewImage', url: string): void
  (e: 'removeImage', id: string): void
  (e: 'selectThinkingMode', value: 'deep' | 'quick'): void
  (e: 'selectSetting', key: PromptOptionKey, value: string): void
  (e: 'selectModel', value: string): void
  (e: 'send'): void
  (e: 'stop'): void
}>()

const uploadInputRef = ref<HTMLInputElement | null>(null)
const composerContainerRef = ref<HTMLElement | null>(null)
const composerTextareaRef = ref<HTMLTextAreaElement | null>(null)
const isComposerActive = ref(false)
const failedOptionImageValues = ref<Set<string>>(new Set())
const composerTextareaMaxRows = 8

const placeholder = computed(() =>
  props.activeMode === 'poster'
    ? '继续补充海报风格、主标题、配色或行业特点'
    : '继续补充活动玩法、时间、门槛或目标人群',
)
const sendButtonState = computed(() => props.sendState || (props.isMessageWorking ? 'working' : 'ready'))

onMounted(() => {
  document.addEventListener('pointerdown', handleComposerOutsidePointerDown)
  void nextTick(() => resizeComposerTextarea())
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', handleComposerOutsidePointerDown)
})

function openImageUpload() {
  uploadInputRef.value?.click()
}

function handleDraftInput(event: Event) {
  const target = event.target as HTMLTextAreaElement | null
  emit('update:draftMessage', target?.value || '')
  resizeComposerTextarea(target)
}

function handleTextareaKeydown(event: KeyboardEvent) {
  if (event.key !== 'Enter')
    return
  if (event.shiftKey)
    return
  if (event.isComposing || event.keyCode === 229)
    return

  event.preventDefault()
  emit('send')
}

function getOptionImageKey(key: PromptOptionKey, value: string) {
  return `${key}:${value}`
}

function shouldShowPromptOptionImage(key: PromptOptionKey, item: SelectorItem | undefined) {
  if (!item?.image)
    return false
  if (key === 'posterSize')
    return false
  if (key === 'tone' && (item.value === '通用风格' || item.label === '通用风格'))
    return false
  if (key === 'activityModel' && (item.isDefault || item.value === 'auto' || item.label === '活动模型'))
    return false
  return !failedOptionImageValues.value.has(getOptionImageKey(key, item.value))
}

function getPromptOptionItemIconClass(key: PromptOptionKey, item: SelectorItem | undefined) {
  if (key === 'posterSize' && item)
    return getPosterSizeIconClass(item)
  return item?.iconClass || props.getPromptOptionIconClass(key)
}

function getPromptOptionSelectedIconClass(key: PromptOptionKey) {
  return getPromptOptionItemIconClass(key, props.getPromptOptionSelectedItem(key))
}

function isCurrentImageModel(item: SelectorItem) {
  return props.currentModelValue
    ? props.currentModelValue === item.value
    : props.currentModel === item.label
}

function handlePromptOptionImageError(key: PromptOptionKey, item: SelectorItem | undefined) {
  if (!item)
    return

  failedOptionImageValues.value = new Set([
    ...failedOptionImageValues.value,
    getOptionImageKey(key, item.value),
  ])
}

function handleComposerInteract(event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (!target)
    return

  isComposerActive.value = true

  if (target.closest('button, input, textarea, a, [role="button"], [data-no-composer-focus="true"]'))
    return

  if (target.closest('.cursor-pointer'))
    return

  composerTextareaRef.value?.focus()
}

function handleComposerOutsidePointerDown(event: PointerEvent) {
  const target = event.target as Node | null
  if (!target)
    return

  if (composerContainerRef.value?.contains(target))
    return

  isComposerActive.value = false
}

function resizeComposerTextarea(target = composerTextareaRef.value) {
  if (!target)
    return

  const style = window.getComputedStyle(target)
  const lineHeight = Number.parseFloat(style.lineHeight) || 24
  const paddingTop = Number.parseFloat(style.paddingTop) || 0
  const paddingBottom = Number.parseFloat(style.paddingBottom) || 0
  const maxHeight = Math.ceil(lineHeight * composerTextareaMaxRows + paddingTop + paddingBottom)

  target.style.height = 'auto'
  const nextHeight = Math.min(target.scrollHeight, maxHeight)
  target.style.height = `${nextHeight}px`
  target.style.overflowY = target.scrollHeight > maxHeight ? 'auto' : 'hidden'
}

watch(() => props.draftMessage, () => {
  void nextTick(() => resizeComposerTextarea())
})
</script>

<style scoped>
.ai-thinking-tooltip {
  position: absolute;
  left: 50%;
  bottom: calc(100% + 8px);
  z-index: 6;
  height: 28px;
  padding: 0 10px;
  border-radius: 6px;
  background: #0f182a;
  color: #ffffff;
  font-size: 12px;
  line-height: 28px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transform: translateX(-50%);
  transition:
    opacity 0.18s ease;
}

.ai-thinking-tooltip::after {
  content: "";
  position: absolute;
  left: 50%;
  bottom: -4px;
  width: 8px;
  height: 8px;
  background: #0f182a;
  transform: translateX(-50%) rotate(45deg);
}

.relative:hover > .ai-thinking-tooltip {
  opacity: 1;
}

:global(.ai-selector-dropdown) {
  padding-top: 8px;
}

:global(.ai-selector-dropdown .kl-dropdown-overlay__content) {
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 4px 12px 4px rgba(47, 48, 49, 0.1);
  overflow: hidden;
}

:global(.ai-selector-panel) {
  width: 100%;
  max-height: min(360px, calc(100vh - 160px));
  overflow-y: auto;
  background: #ffffff;
  scrollbar-width: none;
}

:global(.ai-selector-panel--tone) {
  max-height: min(372px, calc(100vh - 160px));
}

:global(.ai-selector-panel--activityModel) {
  max-height: min(328px, calc(100vh - 160px));
}

:global(.ai-selector-panel--ai-model),
:global(.ai-selector-panel--posterSize) {
  max-height: min(292px, calc(100vh - 160px));
}

:global(.ai-selector-panel::-webkit-scrollbar) {
  display: none;
}

:global(.ai-selector-panel__title) {
  height: 30px;
  padding: 9px 12px 0;
  box-sizing: border-box;
  overflow: hidden;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:global(.ai-selector-option) {
  position: relative;
  width: 100%;
  height: 64px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 44px 12px 12px;
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
  box-sizing: border-box;
  transition: background-color 0.18s ease;
}

:global(.ai-selector-option:hover),
:global(.ai-selector-option.is-active) {
  background: #f5f6f7;
}

:global(.ai-selector-option__icon) {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 4.465px;
  background: #f5f6f7;
  color: #0f182a;
}

:global(.ai-selector-option:hover .ai-selector-option__icon),
:global(.ai-selector-option.is-active .ai-selector-option__icon) {
  background: #ffffff;
}

:global(.ai-selector-option__icon .iconfont) {
  font-size: 24px;
  line-height: 1;
}

:global(.ai-selector-option__icon--ai-model .iconfont) {
  font-size: 20px;
}

:global(.ai-selector-option__icon.has-image) {
  overflow: hidden;
  background: #ffffff;
}

:global(.ai-selector-option__icon.has-image .iconfont) {
  display: none;
}

:global(.ai-selector-option__image) {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

:global(.ai-selector-option__text) {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1 1 auto;
}

:global(.ai-selector-option__label) {
  overflow: hidden;
  color: #0f182a;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:global(.ai-selector-option__desc) {
  overflow: hidden;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
  white-space: nowrap;
  text-overflow: ellipsis;
}

:global(.ai-selector-option__check) {
  position: absolute;
  top: 19px;
  right: 14px;
  color: #0f182a;
  font-size: 18px;
}

.ai-composer-selected-option-icon {
  display: inline-flex;
  width: 20px;
  height: 20px;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  color: #0f182a;
}

.ai-composer-selected-option-icon.has-image {
  border-radius: 50%;
}

.ai-composer-selected-option-icon__image {
  display: block;
  width: 20px;
  height: 20px;
  object-fit: cover;
}

.ai-composer-model-icon {
  position: relative;
  top: 1px;
  font-size: 15px !important;
  line-height: 1;
}

.ai-send-icon--loading {
  display: inline-block;
  animation: ai-send-loading-spin 0.85s linear infinite;
}

.ai-send-button-wrap {
  isolation: isolate;
  overflow: visible;
}

.ai-send-button {
  position: relative;
  z-index: 1;
  border: 0;
}

.ai-send-button-glow {
  position: absolute;
  left: 3px;
  right: 3px;
  bottom: -4px;
  z-index: 0;
  height: 26px;
  border-radius: 26px;
  background: linear-gradient(
    270deg,
    #bee2c7 0%,
    #d3baeb 9.76%,
    #953aef 19.67%,
    #6e18c3 28.75%,
    #e62222 62.58%,
    #f7a589 83.94%,
    #f3c394 88.43%,
    #eeeea4 93.39%,
    #6de08a 100%
  );
  background-size: 260% 100%;
  filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  opacity: 0;
  pointer-events: none;
  transform: scaleX(0.9);
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
  animation: ai-gradient-blur-flow 3.2s ease-in-out infinite;
  will-change: background-position, filter, opacity, transform;
}

.ai-send-button-wrap:hover .ai-send-button-glow,
.ai-send-button-wrap:focus-within .ai-send-button-glow,
.ai-send-button-wrap--generating .ai-send-button-glow {
  opacity: 0.56;
  transform: scaleX(1.06);
}

@property --ai-working-border-angle {
  syntax: "<angle>";
  inherits: false;
  initial-value: 300deg;
}

.ai-working-send-button {
  --ai-working-border-angle: 300deg;
  isolation: isolate;
  overflow: visible;
  border: 0;
  background: #071633;
  animation: ai-working-send-button-pulse 2.4s ease-in-out infinite;
}

.ai-working-send-button::before {
  position: absolute;
  inset: -1.5px;
  z-index: 0;
  padding: 1.5px;
  border-radius: 9999px;
  background: conic-gradient(
    from var(--ai-working-border-angle) at 50% 50%,
    #ffffff 0deg,
    #ffffff 46deg,
    #f8fff3 62deg,
    #bee2c7 82deg,
    #d3baeb 116deg,
    #953aef 154deg,
    #6e18c3 188deg,
    #e62222 268deg,
    #f7a589 314deg,
    #f3c394 330deg,
    #eeeea4 344deg,
    #ffffff 360deg
  );
  content: "";
  pointer-events: none;
  animation: ai-working-send-button-border-flow 2.2s linear infinite;
  mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  mask-composite: exclude;
  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  -webkit-mask-composite: xor;
}

.ai-working-send-button::after {
  position: absolute;
  inset: 0;
  z-index: 0;
  border-radius: inherit;
  background: #071633;
  content: "";
  pointer-events: none;
}

.ai-working-send-button > * {
  position: relative;
  z-index: 1;
}

.ai-working-send-button__glow {
  background: linear-gradient(
    270deg,
    #bee2c7 0%,
    #d3baeb 9.76%,
    #953aef 19.67%,
    #6e18c3 28.75%,
    #e62222 62.58%,
    #f7a589 83.94%,
    #f3c394 88.43%,
    #eeeea4 93.39%,
    #6de08a 100%
  );
  background-size: 240% 100%;
  filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  opacity: 0.42;
  transform-origin: center;
  animation: ai-working-send-button-glow-flow 3s ease-in-out infinite;
  will-change: background-position, opacity, transform;
}

.ai-working-send-button__stop {
  animation: ai-working-send-button-stop-glow 1.9s ease-in-out infinite;
}

.ai-working-send-button__stop-icon {
  transform-origin: center;
  animation: ai-working-send-button-stop-icon 1.6s ease-in-out infinite;
}

@keyframes ai-working-send-button-pulse {
  0%,
  100% {
    box-shadow: 0 10px 20px rgba(7, 22, 51, 0.18);
  }

  50% {
    box-shadow: 0 14px 26px rgba(7, 22, 51, 0.24);
  }
}

@keyframes ai-working-send-button-stop-glow {
  0%,
  100% {
    background: rgba(255, 255, 255, 0.14);
  }

  50% {
    background: rgba(255, 255, 255, 0.24);
  }
}

@keyframes ai-working-send-button-stop-icon {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }

  50% {
    transform: scale(0.9);
    opacity: 0.82;
  }
}

@keyframes ai-working-send-button-border-flow {
  to {
    --ai-working-border-angle: 660deg;
  }
}

@keyframes ai-working-send-button-glow-flow {
  0% {
    background-position: 0% 50%;
    filter: blur(6px) saturate(1.08) hue-rotate(0deg);
    opacity: 0.3;
    transform: translateX(-2px) scaleX(0.98);
  }

  50% {
    background-position: 100% 50%;
    filter: blur(7px) saturate(1.22) hue-rotate(-18deg);
    opacity: 0.52;
    transform: translateX(2px) scaleX(1.05);
  }

  100% {
    background-position: 0% 50%;
    filter: blur(6px) saturate(1.08) hue-rotate(0deg);
    opacity: 0.3;
    transform: translateX(-2px) scaleX(0.98);
  }
}

@keyframes ai-gradient-blur-flow {
  0% {
    background-position: 0% 50%;
    filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  }

  50% {
    background-position: 100% 50%;
    filter: blur(7px) saturate(1.22) hue-rotate(-18deg);
  }

  100% {
    background-position: 0% 50%;
    filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  }
}

@keyframes ai-send-loading-spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .ai-working-send-button,
  .ai-working-send-button::before,
  .ai-working-send-button__glow,
  .ai-send-button-glow,
  .ai-working-send-button__stop,
  .ai-working-send-button__stop-icon {
    animation: none;
  }
}
</style>
